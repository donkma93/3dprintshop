<?php

namespace App\Http\Resources;

use App\Support\ChatProductShare;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ChatMessage */
class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $adminName = $this->whenLoaded('admin', fn () => $this->admin?->name, null);
        if ($adminName === null && $this->admin_user_id) {
            $adminName = $this->admin?->name;
        }

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender' => $this->sender,
            'admin_user_id' => $this->admin_user_id,
            'admin_name' => $adminName,
            'sender_label' => match ($this->sender) {
                'guest' => 'Khách',
                'admin' => $adminName ? 'NV: '.$adminName : 'Nhân viên',
                default => 'Trợ lý ảo',
            },
            'body' => $this->body,
            'product_id' => $this->product_id,
            'product' => ChatProductShare::cardFromMessage($this->resource),
            'is_read' => (bool) $this->is_read,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'created_at_label' => optional($this->created_at)->format('H:i d/m'),
        ];
    }
}
