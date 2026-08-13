<?php

namespace App\Models;

use App\Support\ProductQrCode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'qr_token',
        'qr_image',
        'short_description',
        'description',
        'price',
        'sale_price',
        'sale_starts_at',
        'sale_ends_at',
        'promo_label',
        'cost_price',
        'stock',
        'image',
        'material_used',
        'weight_grams',
        'is_featured',
        'is_active',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight_grams' => 'decimal:2',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'final_price',
        'is_on_sale',
        'discount_percent',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = static::uniqueSlug($product->name);
            }
            if (empty($product->sku) && $product->category_id) {
                $category = Category::withTrashed()->find($product->category_id);
                if ($category) {
                    $product->sku = static::generateUniqueSku($category);
                }
            } elseif (! empty($product->sku)) {
                $product->sku = static::normalizeSku($product->sku);
            }
            if (empty($product->qr_token)) {
                $product->qr_token = ProductQrCode::uniqueToken();
            }
        });

        static::created(function (Product $product) {
            try {
                ProductQrCode::ensureImage($product);
            } catch (\Throwable) {
                // QR sẽ được sinh lại khi mở trang chi tiết
            }
        });

        static::updating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = static::uniqueSlug($product->name, $product->id);
            }

            // Đổi danh mục + SKU trống hoặc SKU đang theo prefix cũ → sinh lại theo danh mục mới
            if ($product->isDirty('category_id') && $product->category_id) {
                $category = Category::withTrashed()->find($product->category_id);
                if ($category) {
                    $oldPrefix = null;
                    if ($product->getOriginal('category_id')) {
                        $oldCat = Category::withTrashed()->find($product->getOriginal('category_id'));
                        $oldPrefix = $oldCat?->sku_prefix;
                    }
                    $sku = (string) $product->sku;
                    $belongsToOld = $oldPrefix && str_starts_with(strtoupper($sku), strtoupper($oldPrefix).'-');
                    $belongsToNew = $category->sku_prefix
                        && str_starts_with(strtoupper($sku), strtoupper($category->sku_prefix).'-');

                    if ($sku === '' || $belongsToOld || ! $belongsToNew) {
                        $product->sku = static::generateUniqueSku($category, $product->id);
                    }
                }
            } elseif (empty($product->sku) && $product->category_id) {
                $category = Category::withTrashed()->find($product->category_id);
                if ($category) {
                    $product->sku = static::generateUniqueSku($category, $product->id);
                }
            } elseif (! empty($product->sku) && $product->isDirty('sku')) {
                $product->sku = static::normalizeSku($product->sku);
            }
        });
    }

    public static function normalizeSku(string $sku): string
    {
        return strtoupper(trim($sku));
    }

    /**
     * Sinh SKU duy nhất theo prefix danh mục: PREFIX-0001, PREFIX-0002, ...
     */
    public static function generateUniqueSku(Category $category, ?int $ignoreProductId = null): string
    {
        if (empty($category->sku_prefix)) {
            $category->sku_prefix = Category::uniqueSkuPrefix($category->name, $category->id);
            if ($category->exists) {
                $category->saveQuietly();
            }
        }

        $sequence = $category->nextSkuSequence();
        // nextSkuSequence không ignore product hiện tại — khi regenerate cùng product có thể giữ số
        // Đảm bảo unique kể cả race: loop
        do {
            $sku = $category->formatSku($sequence);
            $exists = static::withTrashed()
                ->where('sku', $sku)
                ->when($ignoreProductId, fn ($q) => $q->where('id', '!=', $ignoreProductId))
                ->exists();
            $sequence++;
        } while ($exists && $sequence < 100000);

        return $sku;
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'san-pham';
        $slug = $base;
        $i = 1;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(ProductSale::class);
    }

    public function getQrPayloadAttribute(): string
    {
        return ProductQrCode::payload($this);
    }

    public function getQrImageUrlAttribute(): ?string
    {
        if ($this->qr_image) {
            return asset('storage/'.$this->qr_image);
        }

        return null;
    }

    public function orderRequests(): HasMany
    {
        return $this->hasMany(OrderRequest::class);
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/'.$this->image);
        }

        return 'https://placehold.co/800x800/f5f5f5/888888?text=Product';
    }

    /**
     * Giảm giá đang hiệu lực: có sale_price < price và trong khung thời gian (nếu set).
     */
    public function isOnSale(): bool
    {
        if ($this->sale_price === null || $this->sale_price === '') {
            return false;
        }

        $sale = (float) $this->sale_price;
        $list = (float) $this->price;

        if ($sale <= 0 || $sale >= $list) {
            return false;
        }

        $now = Carbon::now();

        if ($this->sale_starts_at && $now->lt($this->sale_starts_at)) {
            return false;
        }

        if ($this->sale_ends_at && $now->gt($this->sale_ends_at)) {
            return false;
        }

        return true;
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->isOnSale();
    }

    public function getFinalPriceAttribute(): float
    {
        return $this->isOnSale() ? (float) $this->sale_price : (float) $this->price;
    }

    public function getDiscountPercentAttribute(): int
    {
        if (! $this->isOnSale()) {
            return 0;
        }

        $list = (float) $this->price;
        if ($list <= 0) {
            return 0;
        }

        return (int) max(1, round((1 - ((float) $this->sale_price / $list)) * 100));
    }

    public function getSaleBadgeAttribute(): ?string
    {
        if (! $this->isOnSale()) {
            return null;
        }

        if ($this->promo_label) {
            return $this->promo_label;
        }

        return '-'.$this->discount_percent.'%';
    }

    public function getProfitAttribute(): float
    {
        return (float) $this->final_price - (float) $this->cost_price;
    }

    public function getSeoTitleAttribute(): string
    {
        return $this->meta_title ?: $this->name;
    }

    public function getSeoDescriptionAttribute(): string
    {
        return $this->meta_description
            ?: ($this->short_description ?: Str::limit(strip_tags((string) $this->description), 160));
    }

    public function getOgImageUrlAttribute(): string
    {
        if ($this->og_image) {
            return asset('storage/'.$this->og_image);
        }

        return $this->image_url;
    }

    public function scopeOnSale($query)
    {
        $now = now();

        return $query->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'price')
            ->where('sale_price', '>', 0)
            ->where(function ($q) use ($now) {
                $q->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>=', $now);
            });
    }
}
