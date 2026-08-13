<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canRevenue = $request->user()?->canViewRevenue() ?? false;

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'qr_token' => $this->qr_token,
            'qr_payload' => $this->qr_token ? \App\Support\ProductQrCode::payload($this->resource) : null,
            'qr_image_url' => $this->qr_image ? asset('storage/'.$this->qr_image) : null,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price !== null ? (float) $this->sale_price : null,
            'sale_starts_at' => optional($this->sale_starts_at)->toIso8601String(),
            'sale_ends_at' => optional($this->sale_ends_at)->toIso8601String(),
            'promo_label' => $this->promo_label,
            'is_on_sale' => $this->is_on_sale,
            'final_price' => (float) $this->final_price,
            'price_formatted' => number_format((float) $this->final_price, 0, ',', '.').' đ',
            'discount_percent' => (int) $this->discount_percent,
            'sale_badge' => $this->sale_badge,
            'cost_price' => $this->when($canRevenue, (float) $this->cost_price),
            'stock' => (int) $this->stock,
            'image' => $this->image,
            'image_url' => $this->image ? asset('storage/'.$this->image) : null,
            'material_used' => $this->material_used,
            'weight_grams' => $this->weight_grams !== null ? (float) $this->weight_grams : null,
            'is_featured' => (bool) $this->is_featured,
            'is_active' => (bool) $this->is_active,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'og_image' => $this->og_image,
            'og_image_url' => $this->og_image ? asset('storage/'.$this->og_image) : null,
            'sort_order' => (int) $this->sort_order,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
