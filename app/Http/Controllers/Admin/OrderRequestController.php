<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderRequest;
use Illuminate\Http\Request;

class OrderRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = OrderRequest::with('product')->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(20)->withQueryString();
        $statusOptions = OrderRequest::statusOptions();
        $newCount = OrderRequest::where('status', OrderRequest::STATUS_NEW)->count();

        return view('admin.orders.index', compact('orders', 'statusOptions', 'newCount'));
    }

    public function show(OrderRequest $order)
    {
        $order->load('product');
        $statusOptions = OrderRequest::statusOptions();

        return view('admin.orders.show', compact('order', 'statusOptions'));
    }

    public function update(Request $request, OrderRequest $order)
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(OrderRequest::statusOptions()))],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === OrderRequest::STATUS_CONTACTED && ! $order->contacted_at) {
            $data['contacted_at'] = now();
        }

        $order->update($data);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Đã cập nhật yêu cầu đặt hàng.');
    }

    public function destroy(OrderRequest $order)
    {
        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Đã xóa yêu cầu đặt hàng.');
    }
}
