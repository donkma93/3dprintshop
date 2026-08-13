<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Material */
class MaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canRevenue = $request->user()?->canViewRevenue() ?? false;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'color' => $this->color,
            'brand' => $this->brand,
            'unit' => $this->unit,
            'stock_quantity' => (float) $this->stock_quantity,
            'unit_price' => $this->when($canRevenue, (float) $this->unit_price),
            'min_stock' => (float) $this->min_stock,
            'stock_value' => $this->when(
                $canRevenue,
                isset($this->stock_value)
                    ? (float) $this->stock_value
                    : (float) $this->stock_quantity * (float) $this->unit_price
            ),
            'is_low_stock' => (float) $this->stock_quantity <= (float) $this->min_stock,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
