<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ChatConversation */
class ChatConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lastMessage = null;
        if ($this->relationLoaded('messages')) {
            $lastMessage = $this->messages->sortByDesc('id')->first();
        }

        return [
            'id' => $this->id,
            'guest_name' => $this->guest_name,
            'guest_phone' => $this->guest_phone,
            'guest_email' => $this->guest_email,
            'status' => $this->status,
            'last_message_at' => optional($this->last_message_at)->toIso8601String(),
            'admin_last_read_at' => optional($this->admin_last_read_at)->toIso8601String(),
            'guest_last_read_at' => optional($this->guest_last_read_at)->toIso8601String(),
            'last_admin_user_id' => $this->last_admin_user_id,
            'last_admin_name' => $this->whenLoaded('lastAdmin', fn () => $this->lastAdmin?->name),
            'ip_address' => $this->when($request->user()?->isAdmin(), $this->ip_address),
            'user_agent' => $this->when($request->user()?->isAdmin(), $this->user_agent),
            'unread_count' => $this->when(isset($this->unread_count), (int) $this->unread_count),
            'last_message' => $lastMessage ? new ChatMessageResource($lastMessage) : null,
            'messages' => ChatMessageResource::collection($this->whenLoaded('messages')),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
