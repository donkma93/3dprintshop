<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ChatConversationResource;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\FcmPushService;
use App\Support\ChatIdleCloser;
use App\Support\ChatProductShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ChatController extends ApiController
{
    private const TYPING_TTL_SECONDS = 6;

    public function show(Request $request): JsonResponse
    {
        $token = (string) $request->query('token', '');
        if ($token === '') {
            return $this->ok([
                'conversation' => null,
                'messages' => [],
                'typing' => ['admin' => false, 'guest' => false],
            ]);
        }

        $conversation = ChatConversation::where('guest_token', $token)->first();
        if (! $conversation) {
            return $this->ok([
                'conversation' => null,
                'messages' => [],
                'typing' => ['admin' => false, 'guest' => false],
            ]);
        }

        ChatIdleCloser::closeIfIdle($conversation);
        $conversation->refresh();

        $afterId = (int) $request->query('after_id', 0);
        $markRead = $request->boolean('mark_read', $afterId === 0);

        $messages = $conversation->messages()
            ->with(['admin:id,name', 'product'])
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->get();

        if ($markRead && ($messages->isNotEmpty() || $afterId === 0)) {
            $conversation->guest_last_read_at = now();
            $conversation->save();
        }

        $conversation->loadMissing('lastAdmin:id,name');

        return $this->ok([
            'conversation' => new ChatConversationResource($conversation),
            'messages' => ChatMessageResource::collection($messages),
            'typing' => [
                'admin' => $this->isTyping($conversation->id, 'admin'),
                'guest' => $this->isTyping($conversation->id, 'guest'),
                'admin_name' => $this->typingAdminName($conversation->id),
            ],
            'staff' => [
                'last_admin_name' => $conversation->lastAdmin?->name,
                'typing_admin_name' => $this->typingAdminName($conversation->id),
            ],
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $key = 'api-chat-start:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return $this->fail('Bạn thao tác quá nhanh. Vui lòng thử lại sau.', 429);
        }
        RateLimiter::hit($key, 60);

        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'guest_email' => ['nullable', 'email', 'max:150'],
            'message' => ['nullable', 'string', 'max:2000'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        // Chỉ bắt buộc tên để xưng hô; SĐT/email là tuỳ chọn.
        $guestName = trim((string) $data['guest_name']);
        if ($guestName === '') {
            return $this->fail('Vui lòng nhập tên để shop tiện xưng hô.', 422);
        }

        [$productId, $productSnapshot] = ChatProductShare::resolveFromRequest(
            isset($data['product_id']) ? (int) $data['product_id'] : null
        );

        $conversation = ChatConversation::create([
            'guest_token' => ChatConversation::newGuestToken(),
            'guest_name' => $guestName,
            'guest_phone' => $data['guest_phone'] ?? null,
            'guest_email' => $data['guest_email'] ?? null,
            'status' => 'open',
            'last_message_at' => now(),
            'guest_last_read_at' => now(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250),
            'ip_address' => $request->ip(),
        ]);

        $siteName = \App\Models\SiteSetting::getValue('site_name', 'Shop3DPrinting');
        $welcome = "Xin chào {$conversation->guest_name}! 👋\n"
            ."Mình là trợ lý ảo của {$siteName}.\n"
            ."Bạn hỏi gì về sản phẩm, báo giá hay thời gian in cũng được.\n"
            .'Nhân viên sẽ phản hồi sớm nhất có thể.';

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender' => 'bot',
            'body' => $welcome,
        ]);

        $guestBody = trim((string) ($data['message'] ?? ''));
        if ($guestBody === '') {
            $guestBody = $productSnapshot['message_template']
                ?? 'Khách vừa bắt đầu chat.';
        }

        $guestMessage = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender' => 'guest',
            'body' => $guestBody,
            'product_id' => $productId,
            'product_snapshot' => $productSnapshot,
        ]);

        $conversation->last_message_at = now();
        $conversation->save();

        app(FcmPushService::class)->notifyGuestChatMessage($guestMessage);

        $messages = $conversation->messages()->with(['admin:id,name', 'product'])->orderBy('id')->get();

        return $this->created([
            'token' => $conversation->guest_token,
            'conversation' => new ChatConversationResource($conversation),
            'messages' => ChatMessageResource::collection($messages),
        ], 'Đã bắt đầu hội thoại.');
    }

    public function send(Request $request): JsonResponse
    {
        $key = 'api-chat-send:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 40)) {
            return $this->fail('Gửi tin quá nhanh. Thử lại sau ít phút.', 429);
        }
        RateLimiter::hit($key, 60);

        $data = $request->validate([
            'token' => ['required', 'string', 'max:64'],
            'message' => ['nullable', 'string', 'max:2000'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $conversation = ChatConversation::where('guest_token', $data['token'])->first();
        if (! $conversation) {
            return $this->fail('Không tìm thấy cuộc trò chuyện.', 404, ['can_restart' => true]);
        }

        if (ChatIdleCloser::closeIfIdle($conversation)) {
            $conversation->refresh();
        }

        if ($conversation->status === 'closed') {
            return $this->fail(ChatIdleCloser::CLOSE_MESSAGE, 422, [
                'can_restart' => true,
                'conversation' => (new ChatConversationResource($conversation))->resolve(),
            ]);
        }

        [$productId, $productSnapshot] = ChatProductShare::resolveFromRequest(
            isset($data['product_id']) ? (int) $data['product_id'] : null
        );

        $body = trim((string) ($data['message'] ?? ''));
        if ($body === '' && $productSnapshot) {
            $body = (string) ($productSnapshot['message_template'] ?? '');
        }
        if ($body === '') {
            return $this->fail('Vui lòng nhập tin nhắn hoặc chọn sản phẩm.', 422);
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender' => 'guest',
            'body' => $body,
            'product_id' => $productId,
            'product_snapshot' => $productSnapshot,
        ]);

        $this->setTyping($conversation->id, 'guest', false);

        $conversation->last_message_at = now();
        $conversation->guest_last_read_at = now();
        $conversation->save();

        app(FcmPushService::class)->notifyGuestChatMessage($message);

        $guestCount = $conversation->messages()->where('sender', 'guest')->count();
        if ($guestCount <= 2) {
            $bot = $this->autoReply($body);
            if ($bot) {
                ChatMessage::create([
                    'conversation_id' => $conversation->id,
                    'sender' => 'bot',
                    'body' => $bot,
                ]);
                $conversation->last_message_at = now();
                $conversation->save();
            }
        }

        $messages = $conversation->messages()
            ->with(['admin:id,name', 'product'])
            ->where('id', '>=', $message->id)
            ->orderBy('id')
            ->get();

        $conversation = $conversation->fresh(['lastAdmin:id,name']);

        return $this->ok([
            'conversation' => new ChatConversationResource($conversation),
            'messages' => ChatMessageResource::collection($messages),
            'typing' => [
                'admin' => $this->isTyping($conversation->id, 'admin'),
                'guest' => false,
                'admin_name' => $this->typingAdminName($conversation->id),
            ],
            'staff' => [
                'last_admin_name' => $conversation->lastAdmin?->name,
                'typing_admin_name' => $this->typingAdminName($conversation->id),
            ],
        ]);
    }

    public function typing(Request $request): JsonResponse
    {
        $key = 'api-chat-typing:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 120)) {
            return $this->fail('Too many requests', 429);
        }
        RateLimiter::hit($key, 60);

        $data = $request->validate([
            'token' => ['required', 'string', 'max:64'],
            'typing' => ['required', 'boolean'],
        ]);

        $conversation = ChatConversation::where('guest_token', $data['token'])->first();
        if (! $conversation) {
            return $this->fail('Không tìm thấy hội thoại.', 404);
        }

        if (ChatIdleCloser::closeIfIdle($conversation)) {
            $conversation->refresh();
        }

        if ($conversation->status === 'closed') {
            return $this->fail(ChatIdleCloser::CLOSE_MESSAGE, 422, ['closed' => true]);
        }

        $this->setTyping($conversation->id, 'guest', (bool) $data['typing']);

        return $this->ok([
            'typing' => [
                'admin' => $this->isTyping($conversation->id, 'admin'),
                'guest' => $this->isTyping($conversation->id, 'guest'),
                'admin_name' => $this->typingAdminName($conversation->id),
            ],
        ]);
    }

    private function typingAdminName(int $conversationId): ?string
    {
        if (! $this->isTyping($conversationId, 'admin')) {
            return null;
        }

        $userId = (int) Cache::get("chat.typing.admin_user.{$conversationId}", 0);
        if ($userId <= 0) {
            return null;
        }

        return \App\Models\User::where('id', $userId)->value('name');
    }

    private function autoReply(string $text): ?string
    {
        $t = mb_strtolower($text);

        if (preg_match('/giá|bao nhiêu|báo giá|price/u', $t)) {
            return "Bạn có thể xem giá từng mẫu tại mục Sản phẩm.\n"
                .'Nếu cần in theo file STL/STEP, gửi mô tả (kích thước, vật liệu PLA/PETG/Resin) — nhân viên sẽ báo giá chi tiết.';
        }
        if (preg_match('/thời gian|bao lâu|giao hàng|lead/u', $t)) {
            return "Thời gian in trung bình 1–3 ngày tùy độ phức tạp và số lượng.\n"
                .'Đơn gấp cứ nhắn thêm chi tiết — shop sẽ ưu tiên phản hồi.';
        }
        if (preg_match('/vật liệu|pla|petg|resin|abs/u', $t)) {
            return "Shop hỗ trợ PLA, PETG, ABS và Resin.\n"
                ."- PLA/Resin: mô hình trang trí, chi tiết mịn\n"
                .'- PETG/ABS: phụ kiện chịu lực, linh kiện kỹ thuật';
        }

        return "Cảm ơn bạn đã nhắn tin! Tin nhắn đã chuyển tới nhân viên tư vấn.\n"
            .'Bạn có thể tiếp tục hỏi — hoặc gọi hotline nếu cần gấp.';
    }

    private function typingCacheKey(int $conversationId, string $side): string
    {
        return "chat.typing.{$side}.{$conversationId}";
    }

    private function setTyping(int $conversationId, string $side, bool $typing): void
    {
        $key = $this->typingCacheKey($conversationId, $side);
        if ($typing) {
            Cache::put($key, true, now()->addSeconds(self::TYPING_TTL_SECONDS));
        } else {
            Cache::forget($key);
        }
    }

    private function isTyping(int $conversationId, string $side): bool
    {
        return (bool) Cache::get($this->typingCacheKey($conversationId, $side), false);
    }
}
