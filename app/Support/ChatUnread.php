<?php

namespace App\Support;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single source of truth for admin chat unread badges / notifications.
 *
 * A guest message is unread only when:
 * - its conversation is still open, and
 * - admin has never read the thread, or the message is newer than admin_last_read_at.
 *
 * Closed conversations never contribute to unread counts (prevents phantom badges).
 */
class ChatUnread
{
    public static function applyUnreadGuestMessageScope(Builder $query): Builder
    {
        return $query
            ->where('sender', 'guest')
            ->whereHas('conversation', function ($cq) {
                $cq->where('status', 'open')
                    ->where(function ($inner) {
                        $inner->whereNull('admin_last_read_at')
                            ->orWhereColumn(
                                'chat_messages.created_at',
                                '>',
                                'chat_conversations.admin_last_read_at'
                            );
                    });
            });
    }

    public static function guestMessageCount(): int
    {
        return self::applyUnreadGuestMessageScope(ChatMessage::query())->count();
    }

    public static function conversationCount(): int
    {
        return ChatConversation::query()
            ->where('status', 'open')
            ->whereHas('messages', function ($mq) {
                $mq->where('sender', 'guest')
                    ->where(function ($inner) {
                        $inner->whereNull('chat_conversations.admin_last_read_at')
                            ->orWhereColumn(
                                'chat_messages.created_at',
                                '>',
                                'chat_conversations.admin_last_read_at'
                            );
                    });
            })
            ->count();
    }

    /**
     * withCount callback for conversation lists (open/closed filter applied by caller).
     */
    public static function unreadCountRelation(Builder $q): void
    {
        $q->where('sender', 'guest')
            ->where(function ($inner) {
                $inner->whereColumn('chat_messages.created_at', '>', 'chat_conversations.admin_last_read_at')
                    ->orWhereNull('chat_conversations.admin_last_read_at');
            });
    }

    /**
     * Clear unread state when a conversation is closed so badges stay accurate.
     */
    public static function markConversationRead(ChatConversation $conversation): void
    {
        $conversation->admin_last_read_at = now();
        $conversation->save();

        $conversation->messages()
            ->where('sender', 'guest')
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
