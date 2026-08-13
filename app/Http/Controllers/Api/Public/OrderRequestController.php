<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\OrderRequestResource;
use App\Models\OrderRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderRequestController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'customer_address' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'note' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:40'],
        ]);

        $product = null;
        if (! empty($data['product_id'])) {
            $product = Product::where('is_active', true)->find($data['product_id']);
            if (! $product) {
                return $this->fail('Sản phẩm không tồn tại hoặc đã ngừng bán.', 422);
            }
        }

        $order = OrderRequest::create([
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'] ?? null,
            'customer_address' => $data['customer_address'] ?? null,
            'product_id' => $product?->id,
            'product_name' => $product?->name,
            'quantity' => $data['quantity'] ?? 1,
            'note' => $data['note'] ?? null,
            'status' => OrderRequest::STATUS_NEW,
            'source' => $data['source'] ?? 'api',
            'ip_address' => $request->ip(),
        ]);

        return $this->created(
            new OrderRequestResource($order),
            'Đã gửi yêu cầu đặt hàng. Shop sẽ liên hệ lại sớm nhất.'
        );
    }
}
