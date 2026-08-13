<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Equipment */
class EquipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canRevenue = $request->user()?->canViewRevenue() ?? false;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'brand' => $this->brand,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'purchase_date' => optional($this->purchase_date)->format('Y-m-d'),
            'purchase_price' => $this->when($canRevenue, (float) $this->purchase_price),
            'supplier' => $this->supplier,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
