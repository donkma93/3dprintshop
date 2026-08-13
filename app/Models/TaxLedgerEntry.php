<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TaxLedgerEntry extends Model
{
    public const SOURCE_PRODUCT_SALE = 'product_sale';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_ADJUSTMENT = 'adjustment';

    public const GROUP_COMMERCE = 'commerce';

    public const GROUP_SERVICE = 'service';

    public const GROUP_PRODUCTION = 'production';

    public const GROUP_OTHER = 'other';

    public const INVOICE_NONE = 'none';

    public const INVOICE_PENDING = 'pending';

    public const INVOICE_ISSUED = 'issued';

    public const INVOICE_CANCELLED = 'cancelled';

    protected $fillable = [
        'entry_date',
        'source_type',
        'source_id',
        'entry_code',
        'description',
        'amount',
        'tax_group',
        'payment_method',
        'customer_name',
        'customer_phone',
        'invoice_status',
        'invoice_number',
        'is_excluded',
        'note',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
        'is_excluded' => 'boolean',
        'source_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (TaxLedgerEntry $entry) {
            if (empty($entry->entry_code)) {
                $entry->entry_code = static::uniqueCode();
            }
        });
    }

    public static function uniqueCode(): string
    {
        do {
            $code = 'TX'.now()->format('ymd').strtoupper(Str::random(5));
        } while (static::where('entry_code', $code)->exists());

        return $code;
    }

    public static function sourceOptions(): array
    {
        return [
            self::SOURCE_PRODUCT_SALE => 'Bán hàng (QR / nội bộ)',
            self::SOURCE_MANUAL => 'Doanh thu thủ công',
            self::SOURCE_ADJUSTMENT => 'Điều chỉnh / trả hàng',
        ];
    }

    public static function groupOptions(): array
    {
        return [
            self::GROUP_COMMERCE => 'Thương mại (bán hàng)',
            self::GROUP_SERVICE => 'Dịch vụ',
            self::GROUP_PRODUCTION => 'Sản xuất',
            self::GROUP_OTHER => 'Khác',
        ];
    }

    public static function invoiceOptions(): array
    {
        return [
            self::INVOICE_NONE => 'Chưa gắn HĐ',
            self::INVOICE_PENDING => 'Chờ xuất HĐ',
            self::INVOICE_ISSUED => 'Đã xuất HĐ',
            self::INVOICE_CANCELLED => 'Hủy HĐ',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSourceLabelAttribute(): string
    {
        return self::sourceOptions()[$this->source_type] ?? $this->source_type;
    }

    public function getGroupLabelAttribute(): string
    {
        return self::groupOptions()[$this->tax_group] ?? $this->tax_group;
    }

    public function getInvoiceLabelAttribute(): string
    {
        return self::invoiceOptions()[$this->invoice_status] ?? $this->invoice_status;
    }

    public function scopeTaxable($query)
    {
        return $query->where('is_excluded', false);
    }
}
