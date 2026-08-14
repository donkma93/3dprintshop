<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Product;
use App\Support\ChatIdleCloser;
use App\Support\ChatProductShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /** Keep slightly above poll interval (2–3s) so indicators survive network jitter. */
    private const TYPING_TTL_SECONDS = 6;

    /**
     * Product picker for chat @-mentions (guest widget + staff).
     */
    public function products(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = Product::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        $items = $query->limit(12)->get()
            ->map(fn (Product $p) => ChatProductShare::snapshot($p))
            ->filter()
            ->values();

        return response()->json(['data' => $items]);
    }

    public function show(Request $request): JsonResponse
    {
        $token = (string) $request->query('token', '');
        if ($token === '') {
            return response()->json(['conversation' => null, 'messages' => []]);
        }

        $conversation = ChatConversation::where('guest_token', $token)->first();
        if (! $conversation) {
            return response()->json(['conversation' => null, 'messages' => []]);
        }

        ChatIdleCloser::closeIfIdle($conversation);
        $conversation->refresh();

        $afterId = (int) $request->query('after_id', 0);
        // mark_read=1 khi khách đang mở widget; poll nền (widget đóng) không đánh dấu đã đọc
        $markRead = $request->boolean('mark_read', $afterId === 0);

        $messages = $conversation->messages()
            ->with(['admin:id,name', 'product'])
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->get()
            ->map(fn (ChatMessage $m) => $this->formatMessage($m));

        $unreadFromStaff = $this->unreadStaffCount($conversation);

        if ($markRead && ($messages->isNotEmpty() || $afterId === 0)) {
            $conversation->guest_last_read_at = now();
            $conversation->save();
            $unreadFromStaff = 0;
        }

        return response()->json([
            'conversation' => $this->formatConversation($conversation),
            'messages' => $messages,
            'typing' => [
                'admin' => $this->isTyping($conversation->id, 'admin'),
                'guest' => $this->isTyping($conversation->id, 'guest'),
                'admin_name' => $this->typingAdminName($conversation->id),
            ],
            'staff' => $this->staffPayload($conversation),
            'unread_from_staff' => $unreadFromStaff,
        ]);
    }

    public function typing(Request $request): JsonResponse
    {
        $key = 'chat-typing:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 120)) {
            return response()->json(['ok' => false], 429);
        }
        RateLimiter::hit($key, 60);

        $data = $request->validate([
            'token' => ['required', 'string', 'max:64'],
            'typing' => ['required', 'boolean'],
        ]);

        $conversation = ChatConversation::where('guest_token', $data['token'])->first();
        if (! $conversation) {
            return response()->json(['ok' => false], 404);
        }

        if (ChatIdleCloser::closeIfIdle($conversation)) {
            $conversation->refresh();
        }

        if ($conversation->status === 'closed') {
            return response()->json([
                'ok' => false,
                'closed' => true,
                'message' => ChatIdleCloser::CLOSE_MESSAGE,
            ], 422);
        }

        if ($data['typing']) {
            $this->setTyping($conversation->id, 'guest', true);
        } else {
            $this->setTyping($conversation->id, 'guest', false);
        }

        return response()->json([
            'ok' => true,
            'typing' => [
                'admin' => $this->isTyping($conversation->id, 'admin'),
                'guest' => $this->isTyping($conversation->id, 'guest'),
                'admin_name' => $this->typingAdminName($conversation->id),
            ],
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $key = 'chat-start:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return response()->json(['message' => 'Bạn thao tác quá nhanh. Vui lòng thử lại sau.'], 429);
        }
        RateLimiter::hit($key, 60);

        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:120'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'guest_email' => ['nullable', 'email', 'max:150'],
            'message' => ['nullable', 'string', 'max:2000'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        if (empty($data['guest_phone']) && empty($data['guest_email'])) {
            return response()->json([
                'message' => 'Vui lòng để lại số điện thoại hoặc email để chúng tôi liên hệ lại.',
            ], 422);
        }

        [$productId, $productSnapshot] = ChatProductShare::resolveFromRequest(
            isset($data['product_id']) ? (int) $data['product_id'] : null
        );

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

        $guestBody = trim((string) ($data['message'] ?? ''));
        if ($guestBody === '') {
            $guestBody = $productSnapshot['message_template']
                ?? 'Khách vừa bắt đầu chat và để lại thông tin liên hệ.';
        }

        // Luôn có tin guest để admin nhận notification (kể cả chỉ để lại SĐT)
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender' => 'guest',
            'body' => $guestBody,
            'product_id' => $productId,
            'product_snapshot' => $productSnapshot,
        ]);

        $conversation->last_message_at = now();
        $conversation->save();

        $messages = $conversation->messages()->with(['admin:id,name', 'product'])->orderBy('id')->get()
            ->map(fn (ChatMessage $m) => $this->formatMessage($m));

        return response()->json([
            'conversation' => $this->formatConversation($conversation),
            'messages' => $messages,
            'token' => $conversation->guest_token,
        ], 201);
    }

    public function send(Request $request): JsonResponse
    {
        $key = 'chat-send:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 40)) {
            return response()->json(['message' => 'Gửi tin quá nhanh. Thử lại sau ít phút.'], 429);
        }
        RateLimiter::hit($key, 60);

        $data = $request->validate([
            'token' => ['required', 'string', 'max:64'],
            'message' => ['nullable', 'string', 'max:2000'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $conversation = ChatConversation::where('guest_token', $data['token'])->first();
        if (! $conversation) {
            return response()->json([
                'message' => 'Không tìm thấy cuộc trò chuyện.',
                'can_restart' => true,
            ], 404);
        }

        if (ChatIdleCloser::closeIfIdle($conversation)) {
            $conversation->refresh();
        }

        if ($conversation->status === 'closed') {
            return response()->json([
                'message' => ChatIdleCloser::CLOSE_MESSAGE,
                'can_restart' => true,
                'conversation' => $this->formatConversation($conversation),
            ], 422);
        }

        [$productId, $productSnapshot] = ChatProductShare::resolveFromRequest(
            isset($data['product_id']) ? (int) $data['product_id'] : null
        );

        $body = trim((string) ($data['message'] ?? ''));
        if ($body === '' && $productSnapshot) {
            $body = (string) ($productSnapshot['message_template'] ?? '');
        }
        if ($body === '') {
            return response()->json(['message' => 'Vui lòng nhập tin nhắn hoặc chọn sản phẩm.'], 422);
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
            ->get()
            ->map(fn (ChatMessage $m) => $this->formatMessage($m));

        $conversation = $conversation->fresh(['lastAdmin:id,name']);

        return response()->json([
            'conversation' => $this->formatConversation($conversation),
            'messages' => $messages,
            'typing' => [
                'admin' => $this->isTyping($conversation->id, 'admin'),
                'guest' => false,
                'admin_name' => $this->typingAdminName($conversation->id),
            ],
            'staff' => $this->staffPayload($conversation),
        ]);
    }

    private function typingCacheKey(int $conversationId, string $side): string
    {
        return "chat.typing.{$side}.{$conversationId}";
    }

    private function adminTypingUserKey(int $conversationId): string
    {
        return "chat.typing.admin_user.{$conversationId}";
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

    private function typingAdminName(int $conversationId): ?string
    {
        if (! $this->isTyping($conversationId, 'admin')) {
            return null;
        }

        $userId = (int) Cache::get($this->adminTypingUserKey($conversationId), 0);
        if ($userId <= 0) {
            return null;
        }

        return \App\Models\User::where('id', $userId)->value('name');
    }

    private function unreadStaffCount(ChatConversation $conversation): int
    {
        return $conversation->messages()
            ->whereIn('sender', ['admin', 'bot'])
            ->when($conversation->guest_last_read_at, fn ($q) => $q->where('created_at', '>', $conversation->guest_last_read_at))
            ->count();
    }

    private function staffPayload(ChatConversation $conversation): array
    {
        $conversation->loadMissing('lastAdmin:id,name');

        return [
            'last_admin_id' => $conversation->last_admin_user_id,
            'last_admin_name' => $conversation->lastAdmin?->name,
            'typing_admin_name' => $this->typingAdminName($conversation->id),
        ];
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

    private function formatConversation(ChatConversation $c): array
    {
        $c->loadMissing('lastAdmin:id,name');

        return [
            'id' => $c->id,
            'guest_name' => $c->guest_name,
            'guest_phone' => $c->guest_phone,
            'guest_email' => $c->guest_email,
            'status' => $c->status,
            'last_message_at' => optional($c->last_message_at)->toIso8601String(),
            'last_admin_name' => $c->lastAdmin?->name,
        ];
    }

    private function formatMessage(ChatMessage $m): array
    {
        if (! $m->relationLoaded('admin') && $m->admin_user_id) {
            $m->load('admin:id,name');
        }

        $adminName = $m->admin?->name;
        $label = match ($m->sender) {
            'guest' => 'Bạn',
            'admin' => $adminName ? 'NV: '.$adminName : 'Nhân viên',
            default => 'Trợ lý ảo',
        };

        return [
            'id' => $m->id,
            'sender' => $m->sender,
            'admin_user_id' => $m->admin_user_id,
            'admin_name' => $adminName,
            'sender_label' => $label,
            'body' => $m->body,
            'product_id' => $m->product_id,
            'product' => ChatProductShare::cardFromMessage($m),
            'created_at' => $m->created_at?->format('H:i d/m'),
            'created_at_iso' => $m->created_at?->toIso8601String(),
        ];
    }
}
