<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxProfile extends Model
{
    public const METHOD_PRESUMPTIVE = 'presumptive';

    public const METHOD_DECLARATION = 'declaration';

    public const CYCLE_MONTH = 'month';

    public const CYCLE_QUARTER = 'quarter';

    public const CYCLE_YEAR = 'year';

    protected $fillable = [
        'business_name',
        'owner_name',
        'tax_code',
        'id_number',
        'phone',
        'email',
        'address',
        'ward',
        'district',
        'province',
        'business_line',
        'tax_office',
        'method',
        'filing_cycle',
        'vat_rate',
        'pit_rate',
        'revenue_threshold',
        'filing_day',
        'filing_month_offset',
        'is_active',
        'notes',
        'disclaimer',
    ];

    protected $casts = [
        'vat_rate' => 'decimal:4',
        'pit_rate' => 'decimal:4',
        'revenue_threshold' => 'decimal:2',
        'filing_day' => 'integer',
        'filing_month_offset' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function methodOptions(): array
    {
        return [
            self::METHOD_PRESUMPTIVE => 'Khoán / tỷ lệ trên doanh thu (ước tính)',
            self::METHOD_DECLARATION => 'Kê khai (chuẩn bị sau)',
        ];
    }

    public static function cycleOptions(): array
    {
        return [
            self::CYCLE_MONTH => 'Hàng tháng',
            self::CYCLE_QUARTER => 'Hàng quý',
            self::CYCLE_YEAR => 'Hàng năm',
        ];
    }

    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->latest('id')->first()
            ?? static::query()->latest('id')->first();
    }

    public function getMethodLabelAttribute(): string
    {
        return self::methodOptions()[$this->method] ?? $this->method;
    }

    public function getCycleLabelAttribute(): string
    {
        return self::cycleOptions()[$this->filing_cycle] ?? $this->filing_cycle;
    }

    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->address,
            $this->ward,
            $this->district,
            $this->province,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }
}
