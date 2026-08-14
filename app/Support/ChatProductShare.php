<?php

namespace App\Support;

use App\Models\Product;

class ChatProductShare
{
    /**
     * Compact product card payload stored on chat messages / returned to clients.
     */
    public static function snapshot(?Product $product): ?array
    {
        if (! $product) {
            return null;
        }

        $url = route('shop.products.show', $product->slug);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'price' => (float) $product->final_price,
            'price_formatted' => number_format((float) $product->final_price, 0, ',', '.').' đ',
            'image_url' => $product->image ? asset('storage/'.$product->image) : null,
            'url' => $url,
            'insert_text' => '@'.$product->name,
            'message_template' => 'Tôi muốn hỏi / tư vấn về sản phẩm: '.$product->name
                .($product->sku ? ' (SKU: '.$product->sku.')' : '')
                .' — '.$url,
        ];
    }

    public static function findActive(int $productId): ?Product
    {
        return Product::query()
            ->where('id', $productId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Resolve product_id from request (optional). Returns [product_id, snapshot] or [null, null].
     *
     * @return array{0: ?int, 1: ?array}
     */
    public static function resolveFromRequest(?int $productId): array
    {
        if (! $productId || $productId <= 0) {
            return [null, null];
        }

        $product = self::findActive($productId);
        if (! $product) {
            return [null, null];
        }

        return [$product->id, self::snapshot($product)];
    }

    /**
     * Prefer live product fields when still available; fall back to snapshot JSON.
     */
    public static function cardFromMessage($message): ?array
    {
        if ($message->relationLoaded('product') && $message->product) {
            return self::snapshot($message->product);
        }

        if (is_array($message->product_snapshot) && ! empty($message->product_snapshot['id'])) {
            $snap = $message->product_snapshot;
            // Keep absolute URL stable even if domain changes — prefer regenerating when product exists.
            if (! empty($message->product_id)) {
                $live = Product::find($message->product_id);
                if ($live) {
                    return self::snapshot($live);
                }
            }

            return [
                'id' => $snap['id'] ?? null,
                'name' => $snap['name'] ?? 'Sản phẩm',
                'slug' => $snap['slug'] ?? null,
                'sku' => $snap['sku'] ?? null,
                'price' => isset($snap['price']) ? (float) $snap['price'] : null,
                'price_formatted' => $snap['price_formatted'] ?? null,
                'image_url' => $snap['image_url'] ?? null,
                'url' => $snap['url'] ?? null,
            ];
        }

        return null;
    }
}
