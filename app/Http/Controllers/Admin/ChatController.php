<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ChatController extends Controller
{
    private const TYPING_TTL_SECONDS = 4;

    public function index(Request $request): View
    {
        $status = $request->query('status', 'open');

        $conversations = ChatConversation::query()
            ->with([
                'messages' => fn ($q) => $q->latest('id')->limit(1),
                'lastAdmin:id,name',
            ])
            ->withCount([
                'messages as unread_count' => function ($q) {
                    $q->where('sender', 'guest')
                        ->where(function ($inner) {
                            $inner->whereColumn('chat_messages.created_at', '>', 'chat_conversations.admin_last_read_at')
                                ->orWhereNull('chat_conversations.admin_last_read_at');
                        });
                },
            ])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $openCount = ChatConversation::where('status', 'open')->count();

        return view('admin.chat.index', compact('conversations', 'status', 'openCount'));
    }

    public function show(ChatConversation $conversation): View
    {
        $conversation->load([
            'messages' => fn ($q) => $q->orderBy('id')->with('admin:id,name'),
            'lastAdmin:id,name',
        ]);

        $conversation->admin_last_read_at = now();
        $conversation->save();

        $conversation->messages()
            ->where('sender', 'guest')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('admin.chat.show', compact('conversation'));
    }

    public function reply(Request $request, ChatConversation $conversation): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        if ($conversation->status === 'closed') {
            $conversation->status = 'open';
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender' => 'admin',
            'admin_user_id' => $request->user()->id,
            'body' => trim($data['message']),
            'is_read' => true,
        ]);
        $message->load('admin:id,name');

        $this->setTyping($conversation->id, 'admin', false);
        $this->setAdminTypingUser($conversation->id, $request->user()->id, false);

        $conversation->last_message_at = now();
        $conversation->admin_last_read_at = now();
        $conversation->last_admin_user_id = $request->user()->id;
        $conversation->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => $this->formatAdminMessage($message, $request->user()->id),
                'typing' => [
                    'guest' => $this->isTyping($conversation->id, 'guest'),
                    'admin' => false,
                    'admin_name' => null,
                ],
                'staff' => $this->staffPayload($conversation->fresh(['lastAdmin:id,name']), $request->user()->id),
            ]);
        }

        return back()->with('success', 'Đã gửi tin nhắn.');
    }

    public function typing(Request $request, ChatConversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'typing' => ['required', 'boolean'],
        ]);

        if ($conversation->status === 'closed') {
            return response()->json(['ok' => false, 'message' => 'Hội thoại đã đóng.'], 422);
        }

        $typing = (bool) $data['typing'];
        $this->setTyping($conversation->id, 'admin', $typing);
        $this->setAdminTypingUser($conversation->id, $request->user()->id, $typing);

        return response()->json([
            'ok' => true,
            'typing' => [
                'guest' => $this->isTyping($conversation->id, 'guest'),
                'admin' => $this->isTyping($conversation->id, 'admin'),
                'admin_name' => $this->typingAdminName($conversation->id),
            ],
            'staff' => $this->staffPayload($conversation->loadMissing('lastAdmin:id,name'), $request->user()->id),
        ]);
    }

    public function poll(Request $request, ChatConversation $conversation): JsonResponse
    {
        $afterId = (int) $request->query('after_id', 0);
        $currentUserId = (int) $request->user()->id;

        $messages = $conversation->messages()
            ->with('admin:id,name')
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->get()
            ->map(fn (ChatMessage $m) => $this->formatAdminMessage($m, $currentUserId));

        if ($messages->isNotEmpty()) {
            $conversation->admin_last_read_at = now();
            $conversation->save();

            $guestIds = $messages->where('sender', 'guest')->pluck('id')->all();
            if ($guestIds !== []) {
                ChatMessage::whereIn('id', $guestIds)->update(['is_read' => true]);
            }
        }

        $conversation->loadMissing('lastAdmin:id,name');

        return response()->json([
            'status' => $conversation->status,
            'messages' => $messages,
            'typing' => [
                'guest' => $this->isTyping($conversation->id, 'guest'),
                'admin' => $this->isTyping($conversation->id, 'admin'),
                'admin_name' => $this->typingAdminName($conversation->id),
            ],
            'staff' => $this->staffPayload($conversation, $currentUserId),
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

    private function setAdminTypingUser(int $conversationId, int $userId, bool $typing): void
    {
        $key = $this->adminTypingUserKey($conversationId);
        if ($typing) {
            Cache::put($key, $userId, now()->addSeconds(self::TYPING_TTL_SECONDS));
        } else {
            // Chỉ xóa nếu đúng user này đang gõ (tránh xóa user khác)
            if ((int) Cache::get($key) === $userId) {
                Cache::forget($key);
            }
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

    private function formatAdminMessage(ChatMessage $m, int $currentUserId): array
    {
        $adminName = $m->admin?->name;
        $label = match ($m->sender) {
            'guest' => 'Khách',
            'bot' => 'Trợ lý ảo',
            'admin' => $m->admin_user_id && (int) $m->admin_user_id === $currentUserId
                ? 'Bạn'
                : ($adminName ? 'NV: '.$adminName : 'Nhân viên'),
            default => $m->sender,
        };

        return [
            'id' => $m->id,
            'sender' => $m->sender,
            'admin_user_id' => $m->admin_user_id,
            'admin_name' => $adminName,
            'sender_label' => $label,
            'body' => $m->body,
            'created_at' => $m->created_at?->format('H:i d/m'),
            'is_mine' => $m->sender === 'admin' && (int) $m->admin_user_id === $currentUserId,
        ];
    }

    private function staffPayload(ChatConversation $conversation, int $currentUserId): array
    {
        $last = $conversation->lastAdmin;
        $typingName = $this->typingAdminName($conversation->id);
        $typingUserId = (int) Cache::get($this->adminTypingUserKey($conversation->id), 0);

        return [
            'last_admin_id' => $conversation->last_admin_user_id,
            'last_admin_name' => $last?->name,
            'is_last_admin_me' => $conversation->last_admin_user_id
                && (int) $conversation->last_admin_user_id === $currentUserId,
            'typing_admin_id' => $typingUserId ?: null,
            'typing_admin_name' => $typingName,
            'is_typing_admin_me' => $typingUserId > 0 && $typingUserId === $currentUserId,
        ];
    }

    /**
     * Poll toàn cục: tin nhắn khách mới + số hội thoại chưa đọc (nhiều người chat song song).
     * with_list=1 → kèm danh sách cho popup thông báo.
     */
    public function notifications(Request $request): JsonResponse
    {
        $afterId = (int) $request->query('after_id', 0);
        $bootstrap = $afterId <= 0;
        $withList = $request->boolean('with_list');

        $latestGuestMessageId = (int) (ChatMessage::where('sender', 'guest')->max('id') ?? 0);

        $items = collect();
        if (! $bootstrap && $afterId > 0) {
            $messages = ChatMessage::query()
                ->with(['conversation:id,guest_name,guest_phone,guest_email,status,created_at,admin_last_read_at'])
                ->where('sender', 'guest')
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit(40)
                ->get();

            $firstGuestIds = $this->firstGuestMessageIds($messages->pluck('conversation_id')->unique()->values()->all());

            $items = $messages->map(function (ChatMessage $m) use ($firstGuestIds) {
                return $this->formatNotificationItem($m, $firstGuestIds);
            });
        }

        $openCount = ChatConversation::where('status', 'open')->count();
        $unreadCount = $this->unreadConversationCount();
        $unreadMessageCount = $this->unreadGuestMessageCount();

        $payload = [
            'after_id' => max($afterId, $latestGuestMessageId),
            'bootstrap' => $bootstrap,
            'items' => $items,
            'open_count' => $openCount,
            'unread_count' => $unreadCount,
            'unread_message_count' => $unreadMessageCount,
        ];

        if ($withList || $bootstrap) {
            $payload['list'] = $this->notificationList(40);
        }

        return response()->json($payload);
    }

    /**
     * Đánh dấu đã đọc: 1 tin / 1 hội thoại / tất cả.
     */
    public function markNotificationsRead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'all' => ['sometimes', 'boolean'],
            'conversation_id' => ['nullable', 'integer', 'exists:chat_conversations,id'],
            'message_id' => ['nullable', 'integer', 'exists:chat_messages,id'],
        ]);

        if (! empty($data['all'])) {
            $conversations = ChatConversation::query()
                ->where(function ($q) {
                    $q->where('status', 'open')
                        ->orWhereHas('messages', function ($mq) {
                            $mq->where('sender', 'guest')->where('is_read', false);
                        });
                })
                ->get();

            foreach ($conversations as $conversation) {
                $this->markConversationRead($conversation);
            }
        } elseif (! empty($data['message_id'])) {
            $message = ChatMessage::with('conversation')->findOrFail($data['message_id']);
            if ($message->sender === 'guest' && $message->conversation) {
                $message->is_read = true;
                $message->save();

                // Nếu không còn tin guest chưa đọc trong hội thoại → đánh dấu cả hội thoại
                $stillUnread = $message->conversation->messages()
                    ->where('sender', 'guest')
                    ->where('is_read', false)
                    ->exists();

                if (! $stillUnread) {
                    $this->markConversationRead($message->conversation);
                } else {
                    // Cập nhật mốc đọc tới thời điểm tin này (không lùi lại)
                    $conversation = $message->conversation;
                    if (! $conversation->admin_last_read_at || $message->created_at > $conversation->admin_last_read_at) {
                        // Chỉ nâng mốc nếu mọi tin guest trước đó đã đọc
                        $olderUnread = $conversation->messages()
                            ->where('sender', 'guest')
                            ->where('is_read', false)
                            ->where('id', '<', $message->id)
                            ->exists();
                        if (! $olderUnread) {
                            $conversation->admin_last_read_at = $message->created_at;
                            $conversation->save();
                        }
                    }
                }
            }
        } elseif (! empty($data['conversation_id'])) {
            $conversation = ChatConversation::findOrFail($data['conversation_id']);
            $this->markConversationRead($conversation);
        } else {
            return response()->json(['message' => 'Thiếu tham số đánh dấu đã đọc.'], 422);
        }

        return response()->json([
            'ok' => true,
            'unread_count' => $this->unreadConversationCount(),
            'unread_message_count' => $this->unreadGuestMessageCount(),
            'list' => $this->notificationList(40),
        ]);
    }

    private function markConversationRead(ChatConversation $conversation): void
    {
        $conversation->admin_last_read_at = now();
        $conversation->save();

        $conversation->messages()
            ->where('sender', 'guest')
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    private function unreadConversationCount(): int
    {
        return ChatConversation::query()
            ->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('admin_last_read_at')
                    ->orWhereHas('messages', function ($mq) {
                        $mq->where('sender', 'guest')
                            ->where(function ($inner) {
                                $inner->where('is_read', false)
                                    ->orWhereColumn('chat_messages.created_at', '>', 'chat_conversations.admin_last_read_at');
                            });
                    });
            })
            ->count();
    }

    private function unreadGuestMessageCount(): int
    {
        return ChatMessage::query()
            ->where('sender', 'guest')
            ->where(function ($q) {
                $q->where('is_read', false)
                    ->orWhereHas('conversation', function ($cq) {
                        $cq->whereNull('admin_last_read_at')
                            ->orWhereColumn('chat_messages.created_at', '>', 'chat_conversations.admin_last_read_at');
                    });
            })
            ->count();
    }

    private function notificationList(int $limit = 40): array
    {
        $messages = ChatMessage::query()
            ->with(['conversation:id,guest_name,guest_phone,guest_email,status,created_at,admin_last_read_at'])
            ->where('sender', 'guest')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $firstGuestIds = $this->firstGuestMessageIds($messages->pluck('conversation_id')->unique()->values()->all());

        return $messages->map(function (ChatMessage $m) use ($firstGuestIds) {
            return $this->formatNotificationItem($m, $firstGuestIds);
        })->values()->all();
    }

    private function firstGuestMessageIds(array $conversationIds): array
    {
        if ($conversationIds === []) {
            return [];
        }

        return ChatMessage::query()
            ->selectRaw('conversation_id, MIN(id) as first_id')
            ->where('sender', 'guest')
            ->whereIn('conversation_id', $conversationIds)
            ->groupBy('conversation_id')
            ->pluck('first_id', 'conversation_id')
            ->all();
    }

    private function formatNotificationItem(ChatMessage $m, array $firstGuestIds = []): array
    {
        $c = $m->conversation;
        $isNewConversation = isset($firstGuestIds[$m->conversation_id])
            && (int) $firstGuestIds[$m->conversation_id] === (int) $m->id;

        $isUnread = ! $m->is_read;
        if ($c) {
            if (! $c->admin_last_read_at) {
                $isUnread = true;
            } elseif ($m->created_at && $m->created_at->gt($c->admin_last_read_at)) {
                $isUnread = true;
            } elseif ($m->is_read && $c->admin_last_read_at && $m->created_at && $m->created_at->lte($c->admin_last_read_at)) {
                $isUnread = false;
            }
        }

        return [
            'id' => $m->id,
            'conversation_id' => $m->conversation_id,
            'guest_name' => $c?->guest_name ?? 'Khách',
            'guest_phone' => $c?->guest_phone,
            'body' => \Illuminate\Support\Str::limit($m->body, 120),
            'created_at' => $m->created_at?->format('H:i d/m'),
            'created_at_human' => optional($m->created_at)->diffForHumans(),
            'url' => route('admin.chat.show', $m->conversation_id),
            'is_new_conversation' => $isNewConversation,
            'is_unread' => $isUnread,
            'conversation_status' => $c?->status ?? 'open',
        ];
    }

    public function close(ChatConversation $conversation)
    {
        $conversation->status = 'closed';
        $conversation->save();

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender' => 'bot',
            'body' => 'Cuộc trò chuyện đã được đóng bởi nhân viên. Cảm ơn bạn đã liên hệ!',
        ]);

        return back()->with('success', 'Đã đóng cuộc trò chuyện.');
    }

    public function reopen(ChatConversation $conversation)
    {
        $conversation->status = 'open';
        $conversation->save();

        return back()->with('success', 'Đã mở lại cuộc trò chuyện.');
    }
}
