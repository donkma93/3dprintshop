<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\OrderRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class OrderRequestController extends Controller
{
    public function store(Request $request)
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
        ], [
            'customer_name.required' => 'Vui lòng nhập họ tên.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại để shop gọi lại.',
            'customer_phone.max' => 'Số điện thoại quá dài.',
            'customer_email.email' => 'Email không hợp lệ.',
        ]);

        $product = null;
        if (! empty($data['product_id'])) {
            $product = Product::where('is_active', true)->find($data['product_id']);
        }

        OrderRequest::create([
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'] ?? null,
            'customer_address' => $data['customer_address'] ?? null,
            'product_id' => $product?->id,
            'product_name' => $product?->name,
            'quantity' => $data['quantity'] ?? 1,
            'note' => $data['note'] ?? null,
            'status' => OrderRequest::STATUS_NEW,
            'source' => $data['source'] ?? 'home',
            'ip_address' => $request->ip(),
        ]);

        $message = 'Đã gửi yêu cầu đặt hàng. Shop sẽ liên hệ lại sớm nhất!';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
