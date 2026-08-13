<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialInput extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'material_id',
        'input_date',
        'quantity',
        'unit_price',
        'total_price',
        'supplier',
        'invoice_number',
        'notes',
    ];

    protected $casts = [
        'input_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
