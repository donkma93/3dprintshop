<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxPeriod extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'period_key',
        'period_type',
        'year',
        'month',
        'quarter',
        'starts_on',
        'ends_on',
        'due_on',
        'status',
        'revenue_total',
        'adjustment_total',
        'taxable_revenue',
        'estimated_vat',
        'estimated_pit',
        'estimated_total',
        'paid_amount',
        'paid_on',
        'payment_ref',
        'snapshot_json',
        'admin_note',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'quarter' => 'integer',
        'starts_on' => 'date',
        'ends_on' => 'date',
        'due_on' => 'date',
        'revenue_total' => 'decimal:2',
        'adjustment_total' => 'decimal:2',
        'taxable_revenue' => 'decimal:2',
        'estimated_vat' => 'decimal:2',
        'estimated_pit' => 'decimal:2',
        'estimated_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_on' => 'date',
        'closed_at' => 'datetime',
    ];

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === self::STATUS_CLOSED ? 'Đã khóa sổ' : 'Đang mở';
    }

    public function getSnapshotAttribute(): array
    {
        if (! $this->snapshot_json) {
            return [];
        }
        $data = json_decode($this->snapshot_json, true);

        return is_array($data) ? $data : [];
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
