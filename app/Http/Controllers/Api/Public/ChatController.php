<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ChatConversationResource;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ChatController extends ApiController
{
    private const TYPING_TTL_SECONDS = 4;

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

        $afterId = (int) $request->query('after_id', 0);
        $markRead = $request->boolean('mark_read', $afterId === 0);

        $messages = $conversation->messages()
            ->with('admin:id,name')
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
        ]);

        if (empty($data['guest_phone']) && empty($data['guest_email'])) {
            return $this->fail('Vui lòng để lại số điện thoại hoặc email để chúng tôi liên hệ lại.', 422);
        }

        $conversation = ChatConversation::create([
            'guest_token' => ChatConversation::newGuestToken(),
            'guest_name' => $data['guest_name'],
            'guest_phone' => $data['guest_phone'] ?? null,
            'guest_email' => $data['guest_email'] ?? null,
            'status' => 'open',
            'last_message_at' => now(),
            'guest_last_read_at' => now(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250),
            'ip_address' => $request->ip(),
        ]);

        $welcome = "Xin chào {$conversation->guest_name}! 👋\n"
            ."Mình là trợ lý ảo của cửa hàng in 3D.\n"
            ."Bạn có thể gửi câu hỏi về sản phẩm, báo giá hoặc thời gian in.\n"
            .'Nhân viên sẽ phản hồi sớm nhất (thường trong giờ hành chính).';

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender' => 'bot',
            'body' => $welcome,
        ]);

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender' => 'guest',
            'body' => ! empty($data['message'])
                ? $data['message']
                : 'Khách vừa bắt đầu chat và để lại thông tin liên hệ.',
        ]);

        $conversation->last_message_at = now();
        $conversation->save();

        $messages = $conversation->messages()->orderBy('id')->get();

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
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $conversation = ChatConversation::where('guest_token', $data['token'])->first();
        if (! $conversation) {
            return $this->fail('Không tìm thấy cuộc trò chuyện.', 404, ['can_restart' => true]);
        }
        if ($conversation->status === 'closed') {
            return $this->fail('Hội thoại đã đóng. Vui lòng mở hội thoại mới để tiếp tục.', 422, [
                'can_restart' => true,
                'conversation' => (new ChatConversationResource($conversation))->resolve(),
            ]);
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender' => 'guest',
            'body' => trim($data['message']),
        ]);

        $this->setTyping($conversation->id, 'guest', false);

        $conversation->last_message_at = now();
        $conversation->guest_last_read_at = now();
        $conversation->save();

        $guestCount = $conversation->messages()->where('sender', 'guest')->count();
        if ($guestCount <= 2) {
            $bot = $this->autoReply(trim($data['message']));
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
            ->with('admin:id,name')
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
        if (! $conversation || $conversation->status === 'closed') {
            return $this->fail('Không tìm thấy hội thoại.', 404);
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
                .'Đơn gấp vui lòng để lại SĐT, shop sẽ ưu tiên phản hồi.';
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
