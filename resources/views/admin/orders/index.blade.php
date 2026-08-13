@extends('layouts.admin')

@section('title', 'Yêu cầu đặt hàng')
@section('subtitle', 'Khách để lại thông tin — shop liên hệ lại')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form class="d-flex flex-wrap gap-2" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tên, SĐT, SP...">
        <select name="status" class="form-select form-select-sm" style="width:auto">
            <option value="">Tất cả trạng thái</option>
            @foreach($statusOptions as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-outline-dark">Lọc</button>
    </form>
    @if($newCount > 0)
        <span class="badge text-bg-danger">{{ $newCount }} yêu cầu mới</span>
    @endif
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
            <tr>
                <th>#</th>
                <th>Khách</th>
                <th>Sản phẩm</th>
                <th>SL</th>
                <th>Nguồn</th>
                <th>Trạng thái</th>
                <th>Thời gian</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse($orders as $order)
                <tr class="{{ $order->status === 'new' ? 'table-warning' : '' }}">
                    <td>{{ $order->id }}</td>
                    <td>
                        <div class="fw-semibold">{{ $order->customer_name }}</div>
                        <div class="small"><a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a></div>
                        @if($order->customer_email)
                            <div class="small text-secondary">{{ $order->customer_email }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $order->product_name ?: ($order->product?->name ?? '— Tư vấn chung') }}</div>
                        @if($order->note)
                            <div class="small text-secondary text-truncate" style="max-width:220px">{{ $order->note }}</div>
                        @endif
                    </td>
                    <td>{{ $order->quantity }}</td>
                    <td><span class="badge text-bg-secondary">{{ $order->source }}</span></td>
                    <td>
                        @if($order->status === 'new')
                            <span class="badge text-bg-danger">{{ $order->status_label }}</span>
                        @elseif($order->status === 'confirmed')
                            <span class="badge badge-soft">{{ $order->status_label }}</span>
                        @else
                            <span class="badge text-bg-secondary">{{ $order->status_label }}</span>
                        @endif
                    </td>
                    <td class="small text-secondary">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Xem</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-secondary">Chưa có yêu cầu đặt hàng.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection
