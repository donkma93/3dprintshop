<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'link',
        'button_text',
        'position',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function positionOptions(): array
    {
        return [
            'home_slider' => 'Slider trang chủ',
            'home_promo' => 'Banner quảng cáo nhỏ',
        ];
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/'.$this->image);
        }

        if ($this->position === 'home_promo') {
            return asset('images/backgrounds/promo-3d.svg');
        }

        return asset('images/backgrounds/hero-3d.svg');
    }
}
