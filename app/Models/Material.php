<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'color',
        'brand',
        'unit',
        'stock_quantity',
        'unit_price',
        'min_stock',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'stock_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function inputs(): HasMany
    {
        return $this->hasMany(MaterialInput::class);
    }

    public function isLowStock(): bool
    {
        return (float) $this->stock_quantity <= (float) $this->min_stock;
    }

    public function getStockValueAttribute(): float
    {
        return (float) $this->stock_quantity * (float) $this->unit_price;
    }
}
