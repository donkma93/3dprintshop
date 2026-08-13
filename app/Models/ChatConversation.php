<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChatConversation extends Model
{
    protected $fillable = [
        'guest_token',
        'guest_name',
        'guest_phone',
        'guest_email',
        'status',
        'last_message_at',
        'admin_last_read_at',
        'guest_last_read_at',
        'last_admin_user_id',
        'user_agent',
        'ip_address',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'admin_last_read_at' => 'datetime',
        'guest_last_read_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function lastAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_admin_user_id');
    }

    public static function newGuestToken(): string
    {
        return Str::random(48);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function getUnreadForAdminAttribute(): int
    {
        return $this->messages()
            ->where('sender', 'guest')
            ->when($this->admin_last_read_at, fn ($q) => $q->where('created_at', '>', $this->admin_last_read_at))
            ->count();
    }

    public function getUnreadForGuestAttribute(): int
    {
        return $this->messages()
            ->whereIn('sender', ['admin', 'bot'])
            ->when($this->guest_last_read_at, fn ($q) => $q->where('created_at', '>', $this->guest_last_read_at))
            ->count();
    }
}
