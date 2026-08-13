@extends('layouts.admin')

@section('title', 'Chi tiết yêu cầu #'.$order->id)
@section('subtitle', $order->customer_name)

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card p-3 mb-3">
            <h2 class="h6 mb-3">Thông tin khách</h2>
            <dl class="row mb-0 small">
                <dt class="col-sm-4">Họ tên</dt>
                <dd class="col-sm-8 fw-semibold">{{ $order->customer_name }}</dd>
                <dt class="col-sm-4">Điện thoại</dt>
                <dd class="col-sm-8"><a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a></dd>
                <dt class="col-sm-4">Email</dt>
                <dd class="col-sm-8">{{ $order->customer_email ?: '—' }}</dd>
                <dt class="col-sm-4">Địa chỉ</dt>
                <dd class="col-sm-8">{{ $order->customer_address ?: '—' }}</dd>
                <dt class="col-sm-4">Ghi chú khách</dt>
                <dd class="col-sm-8">{{ $order->note ?: '—' }}</dd>
                <dt class="col-sm-4">Nguồn</dt>
                <dd class="col-sm-8">{{ $order->source }} · IP {{ $order->ip_address ?: '—' }}</dd>
                <dt class="col-sm-4">Gửi lúc</dt>
                <dd class="col-sm-8">{{ $order->created_at?->format('d/m/Y H:i') }}</dd>
            </dl>
        </div>
        <div class="card p-3">
            <h2 class="h6 mb-3">Sản phẩm quan tâm</h2>
            @if($order->product)
                <div class="d-flex gap-3 align-items-center">
                    <img src="{{ $order->product->image_url }}" alt="" width="64" height="64" class="rounded object-fit-cover" style="object-fit:cover">
                    <div>
                        <div class="fw-semibold">{{ $order->product->name }}</div>
                        <div class="small text-secondary">
                            {{ number_format($order->product->final_price, 0, ',', '.') }} đ
                            · SL: {{ $order->quantity }}
                        </div>
                        <a href="{{ route('admin.products.edit', $order->product) }}" class="small">Sửa sản phẩm</a>
                    </div>
                </div>
            @else
                <div class="text-secondary">{{ $order->product_name ?: 'Tư vấn / đặt hàng chung (không chọn SP)' }} · SL {{ $order->quantity }}</div>
            @endif
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card p-3">
            <h2 class="h6 mb-3">Cập nhật trạng thái</h2>
            <form action="{{ route('admin.orders.update', $order) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-select" required>
                        @foreach($statusOptions as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $order->status) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ghi chú nội bộ</label>
                    <textarea name="admin_note" rows="4" class="form-control">{{ old('admin_note', $order->admin_note) }}</textarea>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Lưu</button>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Danh sách</a>
                </div>
            </form>
            <hr>
            <form action="{{ route('admin.orders.destroy', $order) }}" method="POST"
                  data-confirm="Xóa yêu cầu #{{ $order->id }}?" data-confirm-title="Xóa">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm" type="submit">Xóa yêu cầu</button>
            </form>
        </div>
    </div>
</div>
@endsection
