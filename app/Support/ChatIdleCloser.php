<?php

namespace App\Support;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Support\Collection;

class ChatIdleCloser
{
    /** Minutes without any message before auto-close. */
    public const IDLE_MINUTES = 30;

    public const CLOSE_MESSAGE = 'Hội thoại đã tự đóng vì không có tin nhắn mới trong 30 phút. '
        .'Bạn có thể mở hội thoại mới nếu cần hỗ trợ tiếp.';

    /**
     * Close this conversation if it has been idle too long.
     * Returns true when the conversation was closed in this call.
     */
    public static function closeIfIdle(ChatConversation $conversation): bool
    {
        if ($conversation->status !== 'open') {
            return false;
        }

        $lastActivity = $conversation->last_message_at ?? $conversation->created_at;
        if (! $lastActivity) {
            return false;
        }

        if ($lastActivity->gt(now()->subMinutes(self::IDLE_MINUTES))) {
            return false;
        }

        return self::closeConversation($conversation);
    }

    /**
     * Close all open conversations that exceeded the idle window.
     *
     * @return Collection<int, ChatConversation>
     */
    public static function closeAllIdle(): Collection
    {
        $threshold = now()->subMinutes(self::IDLE_MINUTES);

        $conversations = ChatConversation::query()
            ->where('status', 'open')
            ->where(function ($q) use ($threshold) {
                $q->where(function ($inner) use ($threshold) {
                    $inner->whereNotNull('last_message_at')
                        ->where('last_message_at', '<=', $threshold);
                })->orWhere(function ($inner) use ($threshold) {
                    $inner->whereNull('last_message_at')
                        ->where('created_at', '<=', $threshold);
                });
            })
            ->orderBy('id')
            ->get();

        $closed = collect();
        foreach ($conversations as $conversation) {
            if (self::closeConversation($conversation)) {
                $closed->push($conversation->fresh());
            }
        }

        return $closed;
    }

    public static function closeConversation(ChatConversation $conversation, ?string $body = null): bool
    {
        if ($conversation->status === 'closed') {
            return false;
        }

        $conversation->status = 'closed';
        $conversation->last_message_at = now();
        // Closing also clears admin unread so badges don't keep "ghost" messages.
        $conversation->admin_last_read_at = now();
        $conversation->save();

        $conversation->messages()
            ->where('sender', 'guest')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender' => 'bot',
            'body' => $body ?? self::CLOSE_MESSAGE,
        ]);

        return true;
    }
}
