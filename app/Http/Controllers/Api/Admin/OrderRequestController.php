<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\OrderRequestResource;
use App\Models\OrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderRequestController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $query = OrderRequest::with('product')->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        return $this->ok(OrderRequestResource::collection($query->paginate($perPage)));
    }

    public function show(OrderRequest $order): JsonResponse
    {
        $order->load('product');

        return $this->ok(new OrderRequestResource($order));
    }

    public function update(Request $request, OrderRequest $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(OrderRequest::statusOptions()))],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === OrderRequest::STATUS_CONTACTED && ! $order->contacted_at) {
            $data['contacted_at'] = now();
        }

        $order->update($data);

        return $this->ok(new OrderRequestResource($order->fresh('product')), 'Đã cập nhật yêu cầu đặt hàng.');
    }

    public function destroy(OrderRequest $order): JsonResponse
    {
        $order->delete();

        return $this->ok(null, 'Đã xóa yêu cầu đặt hàng.');
    }
}
