<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'sku_prefix',
        'description',
        'is_active',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'image',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = static::uniqueSlug($category->name);
            }
            if (empty($category->sku_prefix)) {
                $category->sku_prefix = static::uniqueSkuPrefix($category->name);
            } else {
                $category->sku_prefix = static::normalizeSkuPrefix($category->sku_prefix);
            }
        });

        static::updating(function (Category $category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = static::uniqueSlug($category->name, $category->id);
            }
            if ($category->isDirty('sku_prefix') && ! empty($category->sku_prefix)) {
                $category->sku_prefix = static::normalizeSkuPrefix($category->sku_prefix);
            }
            if (empty($category->sku_prefix)) {
                $category->sku_prefix = static::uniqueSkuPrefix($category->name, $category->id);
            }
        });
    }

    public static function normalizeSkuPrefix(string $prefix): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $prefix) ?? '');
        $prefix = substr($prefix, 0, 8);

        return $prefix !== '' ? $prefix : 'CAT';
    }

    /**
     * Tạo mã prefix duy nhất từ tên danh mục (vd: "Mô hình trang trí" → MHT).
     */
    public static function uniqueSkuPrefix(string $name, ?int $ignoreId = null): string
    {
        $words = preg_split('/[\s\-_]+/u', trim($name)) ?: [];
        $letters = '';
        foreach ($words as $word) {
            $clean = preg_replace('/[^\p{L}\p{N}]/u', '', $word) ?? '';
            if ($clean === '') {
                continue;
            }
            // Lấy chữ cái đầu; với tiếng Việt không Latin thuần, fallback slug
            $first = mb_substr($clean, 0, 1, 'UTF-8');
            $ascii = Str::ascii($first);
            $ascii = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $ascii) ?? '');
            if ($ascii !== '') {
                $letters .= $ascii;
            }
        }

        if (strlen($letters) < 2) {
            $slug = strtoupper(preg_replace('/[^A-Z0-9]/i', '', Str::ascii($name)) ?? '');
            $letters = substr($slug, 0, 3) ?: 'CAT';
        }

        $base = static::normalizeSkuPrefix(substr($letters, 0, 4));
        $prefix = $base;
        $i = 1;

        while (static::withTrashed()
            ->where('sku_prefix', $prefix)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $suffix = (string) $i;
            $prefix = static::normalizeSkuPrefix(substr($base, 0, max(1, 8 - strlen($suffix))).$suffix);
            $i++;
            if ($i > 999) {
                $prefix = static::normalizeSkuPrefix('C'.strtoupper(Str::random(4)));
                break;
            }
        }

        return $prefix;
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'danh-muc';
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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Số thứ tự tiếp theo cho SKU trong danh mục này.
     */
    public function nextSkuSequence(): int
    {
        $prefix = $this->sku_prefix ?: static::uniqueSkuPrefix($this->name, $this->id);
        $pattern = $prefix.'-';

        $max = 0;
        Product::withTrashed()
            ->where('sku', 'like', $pattern.'%')
            ->pluck('sku')
            ->each(function (?string $sku) use ($pattern, &$max) {
                if (! $sku || ! str_starts_with(strtoupper($sku), strtoupper($pattern))) {
                    return;
                }
                $tail = substr($sku, strlen($pattern));
                if (ctype_digit($tail)) {
                    $max = max($max, (int) $tail);
                }
            });

        return $max + 1;
    }

    public function formatSku(int $sequence, int $pad = 4): string
    {
        $prefix = $this->sku_prefix ?: 'CAT';

        return strtoupper($prefix).'-'.str_pad((string) $sequence, $pad, '0', STR_PAD_LEFT);
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/'.$this->image);
        }

        return 'https://placehold.co/400x400/f5f5f5/555555?text='.urlencode($this->name);
    }

    public function getSeoTitleAttribute(): string
    {
        return $this->meta_title ?: $this->name;
    }

    public function getSeoDescriptionAttribute(): string
    {
        return $this->meta_description ?: Str::limit(strip_tags((string) $this->description), 160);
    }
}
