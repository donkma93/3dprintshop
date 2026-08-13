<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductSale extends Model
{
    public const SOURCE_WALK_IN = 'walk_in';

    public const SOURCE_PHONE = 'phone';

    public const SOURCE_WEB_CHAT = 'web_chat';

    public const SOURCE_CONTACT = 'contact';

    public const SOURCE_ORDER = 'order_request';

    public const SOURCE_OTHER = 'other';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_TRANSFER = 'transfer';

    public const PAYMENT_COD = 'cod';

    public const SERVICE_STANDARD = 'standard';

    public const SERVICE_EXPRESS = 'express';

    public const SERVICE_ECONOMY = 'economy';

    protected $fillable = [
        'product_id',
        'sold_by',
        'sale_code',
        'quantity',
        'unit_price',
        'unit_cost',
        'total_price',
        'total_cost',
        'profit',
        'stock_before',
        'stock_after',
        'scan_payload',
        'channel',
        'note',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'customer_ward',
        'customer_district',
        'customer_province',
        'customer_postal_code',
        'customer_source',
        'needs_shipping',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'receiver_ward',
        'receiver_district',
        'receiver_province',
        'receiver_postal_code',
        'shipping_note',
        'carrier',
        'shipping_service',
        'payment_method',
        'cod_amount',
        'package_weight',
        'package_count',
        'declared_value',
        'goods_content',
        'sold_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'profit' => 'decimal:2',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'needs_shipping' => 'boolean',
        'cod_amount' => 'decimal:2',
        'package_weight' => 'integer',
        'package_count' => 'integer',
        'declared_value' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductSale $sale) {
            if (empty($sale->sale_code)) {
                $sale->sale_code = static::uniqueSaleCode();
            }
            if (empty($sale->sold_at)) {
                $sale->sold_at = now();
            }
            $qty = max(1, (int) $sale->quantity);
            $sale->quantity = $qty;
            $sale->total_price = round((float) $sale->unit_price * $qty, 2);
            $sale->total_cost = round((float) $sale->unit_cost * $qty, 2);
            $sale->profit = round((float) $sale->total_price - (float) $sale->total_cost, 2);

            if ((int) ($sale->package_count ?? 0) < 1) {
                $sale->package_count = 1;
            }

            // Tự điền người nhận từ khách nếu bật gửi hàng mà chưa nhập
            if ($sale->needs_shipping) {
                $sale->receiver_name = $sale->receiver_name ?: $sale->customer_name;
                $sale->receiver_phone = $sale->receiver_phone ?: $sale->customer_phone;
                $sale->receiver_address = $sale->receiver_address ?: $sale->customer_address;
                $sale->receiver_ward = $sale->receiver_ward ?: $sale->customer_ward;
                $sale->receiver_district = $sale->receiver_district ?: $sale->customer_district;
                $sale->receiver_province = $sale->receiver_province ?: $sale->customer_province;
                $sale->receiver_postal_code = $sale->receiver_postal_code ?: $sale->customer_postal_code;
            }
        });
    }

    public static function uniqueSaleCode(): string
    {
        do {
            $code = 'S'.now()->format('ymd').strtoupper(Str::random(6));
        } while (static::where('sale_code', $code)->exists());

        return $code;
    }

    public static function sourceOptions(): array
    {
        return [
            self::SOURCE_WALK_IN => 'Khách đến trực tiếp',
            self::SOURCE_PHONE => 'Điện thoại',
            self::SOURCE_WEB_CHAT => 'Chat website',
            self::SOURCE_CONTACT => 'Form liên hệ / web',
            self::SOURCE_ORDER => 'Yêu cầu đặt hàng',
            self::SOURCE_OTHER => 'Khác',
        ];
    }

    public static function paymentOptions(): array
    {
        return [
            self::PAYMENT_CASH => 'Tiền mặt',
            self::PAYMENT_TRANSFER => 'Chuyển khoản',
            self::PAYMENT_COD => 'COD (thu hộ)',
        ];
    }

    public static function serviceOptions(): array
    {
        return [
            self::SERVICE_STANDARD => 'Tiêu chuẩn',
            self::SERVICE_EXPRESS => 'Nhanh / Express',
            self::SERVICE_ECONOMY => 'Tiết kiệm',
        ];
    }

    public static function carrierOptions(): array
    {
        return [
            'ghn' => 'GHN',
            'ghtk' => 'Giao Hàng Tiết Kiệm',
            'viettel_post' => 'Viettel Post',
            'jt' => 'J&T Express',
            'vnpost' => 'Vietnam Post',
            'ninjavan' => 'Ninja Van',
            'shopee_express' => 'Shopee Express',
            'grab' => 'GrabExpress',
            'other' => 'Khác / tự giao',
        ];
    }

    /**
     * Ghép địa chỉ đầy đủ 1 dòng (in phiếu / hiển thị).
     */
    public static function composeAddress(
        ?string $street,
        ?string $ward = null,
        ?string $district = null,
        ?string $province = null,
        ?string $postalCode = null
    ): ?string {
        $parts = array_values(array_filter([
            $street ? trim($street) : null,
            $ward ? trim($ward) : null,
            $district ? trim($district) : null,
            $province ? trim($province) : null,
            $postalCode ? ('Mã BC: '.trim($postalCode)) : null,
        ], fn ($v) => $v !== null && $v !== ''));

        return $parts === [] ? null : implode(', ', $parts);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function getSourceLabelAttribute(): string
    {
        return self::sourceOptions()[$this->customer_source] ?? ($this->customer_source ?: '—');
    }

    public function getPaymentLabelAttribute(): string
    {
        return self::paymentOptions()[$this->payment_method] ?? ($this->payment_method ?: '—');
    }

    public function getServiceLabelAttribute(): string
    {
        return self::serviceOptions()[$this->shipping_service] ?? ($this->shipping_service ?: '—');
    }

    public function getCarrierLabelAttribute(): string
    {
        return self::carrierOptions()[$this->carrier] ?? ($this->carrier ?: '—');
    }

    public function getCustomerFullAddressAttribute(): ?string
    {
        return self::composeAddress(
            $this->customer_address,
            $this->customer_ward,
            $this->customer_district,
            $this->customer_province,
            $this->customer_postal_code
        );
    }

    public function getEffectiveReceiverNameAttribute(): ?string
    {
        return $this->receiver_name ?: $this->customer_name;
    }

    public function getEffectiveReceiverPhoneAttribute(): ?string
    {
        return $this->receiver_phone ?: $this->customer_phone;
    }

    public function getEffectiveReceiverAddressAttribute(): ?string
    {
        return $this->receiver_address ?: $this->customer_address;
    }

    public function getEffectiveReceiverWardAttribute(): ?string
    {
        return $this->receiver_ward ?: $this->customer_ward;
    }

    public function getEffectiveReceiverDistrictAttribute(): ?string
    {
        return $this->receiver_district ?: $this->customer_district;
    }

    public function getEffectiveReceiverProvinceAttribute(): ?string
    {
        return $this->receiver_province ?: $this->customer_province;
    }

    public function getEffectiveReceiverPostalCodeAttribute(): ?string
    {
        return $this->receiver_postal_code ?: $this->customer_postal_code;
    }

    public function getEffectiveReceiverFullAddressAttribute(): ?string
    {
        return self::composeAddress(
            $this->effective_receiver_address,
            $this->effective_receiver_ward,
            $this->effective_receiver_district,
            $this->effective_receiver_province,
            $this->effective_receiver_postal_code
        );
    }

    public function canPrintShipping(): bool
    {
        return (bool) $this->needs_shipping
            && filled($this->effective_receiver_name)
            && filled($this->effective_receiver_phone)
            && filled($this->effective_receiver_full_address);
    }
}
