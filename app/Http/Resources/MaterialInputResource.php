<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\MaterialInput */
class MaterialInputResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canRevenue = $request->user()?->canViewRevenue() ?? false;

        return [
            'id' => $this->id,
            'material_id' => $this->material_id,
            'material' => new MaterialResource($this->whenLoaded('material')),
            'input_date' => optional($this->input_date)->format('Y-m-d'),
            'quantity' => (float) $this->quantity,
            'unit_price' => $this->when($canRevenue, (float) $this->unit_price),
            'total_price' => $this->when($canRevenue, (float) $this->total_price),
            'supplier' => $this->supplier,
            'invoice_number' => $this->invoice_number,
            'notes' => $this->notes,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
