@extends('layouts.admin')

@section('title', 'Lịch sử bán hàng')
@section('subtitle', 'Khách hàng · sản phẩm đã bán · phiếu gửi hàng')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form class="d-flex flex-wrap gap-2 align-items-center" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
               placeholder="Mã GD / KH / SĐT / SKU" style="min-width:200px">
        <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
        <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
        <div class="form-check form-check-inline mb-0">
            <input class="form-check-input" type="checkbox" name="shipping_only" value="1" id="shipping_only"
                   @checked(request()->boolean('shipping_only'))>
            <label class="form-check-label small" for="shipping_only">Chỉ đơn gửi hàng</label>
        </div>
        <button class="btn btn-sm btn-outline-dark">Lọc</button>
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.sales.scan') }}" class="btn btn-dark btn-sm"><i class="bi bi-qr-code-scan"></i> Quét bán</a>
        @if($canRevenue)
            <a href="{{ route('admin.sales.report') }}" class="btn btn-outline-dark btn-sm">Báo cáo lãi lỗ</a>
        @endif
    </div>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
            <tr>
                <th>Mã GD</th>
                <th>Sản phẩm</th>
                <th>Khách hàng</th>
                <th>SL</th>
                <th>Đơn giá</th>
                <th>Doanh thu</th>
                @if($canRevenue)
                    <th>Giá vốn</th>
                    <th>Lãi gộp</th>
                @endif
                <th>Gửi hàng</th>
                <th>Người bán</th>
                <th>Thời gian</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td><code>{{ $sale->sale_code }}</code></td>
                    <td>
                        <div class="fw-semibold">{{ $sale->product?->name ?? '—' }}</div>
                        <div class="small text-secondary">{{ $sale->product?->sku }}</div>
                    </td>
                    <td>
                        @if($sale->customer_name || $sale->customer_phone)
                            <div class="fw-semibold">{{ $sale->customer_name ?: '—' }}</div>
                            <div class="small text-secondary">
                                {{ $sale->customer_phone }}
                                @if($sale->customer_source)
                                    · {{ $sale->source_label }}
                                @endif
                            </div>
                            @if($sale->needs_shipping && $sale->effective_receiver_full_address)
                                <div class="small text-secondary mt-1" style="max-width:220px">
                                    <i class="bi bi-geo-alt"></i> {{ \Illuminate\Support\Str::limit($sale->effective_receiver_full_address, 80) }}
                                </div>
                            @elseif($sale->customer_full_address)
                                <div class="small text-secondary mt-1" style="max-width:220px">
                                    {{ \Illuminate\Support\Str::limit($sale->customer_full_address, 60) }}
                                </div>
                            @endif
                        @else
                            <span class="text-secondary">—</span>
                        @endif
                    </td>
                    <td>{{ $sale->quantity }}</td>
                    <td>{{ number_format($sale->unit_price, 0, ',', '.') }}</td>
                    <td class="fw-semibold">{{ number_format($sale->total_price, 0, ',', '.') }}</td>
                    @if($canRevenue)
                        <td>{{ number_format($sale->total_cost, 0, ',', '.') }}</td>
                        <td class="{{ $sale->profit >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                            {{ number_format($sale->profit, 0, ',', '.') }}
                        </td>
                    @endif
                    <td>
                        @if($sale->needs_shipping)
                            <span class="badge text-bg-primary">Gửi</span>
                            @if($sale->carrier)
                                <div class="small">{{ $sale->carrier_label }}</div>
                            @endif
                            @if($sale->payment_method === 'cod')
                                <div class="small text-danger">COD {{ number_format($sale->cod_amount ?? 0, 0, ',', '.') }}</div>
                            @endif
                        @else
                            <span class="text-secondary small">—</span>
                        @endif
                    </td>
                    <td class="small">{{ $sale->seller?->name ?? '—' }}</td>
                    <td class="small">{{ optional($sale->sold_at)->format('d/m/Y H:i') }}</td>
                    <td class="text-end text-nowrap">
                        @if($sale->needs_shipping)
                            <a href="{{ route('admin.sales.print', $sale) }}" class="btn btn-sm btn-outline-dark" title="In phiếu gửi">
                                <i class="bi bi-printer"></i>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canRevenue ? 12 : 10 }}" class="text-secondary">Chưa có giao dịch.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $sales->links() }}</div>
</div>
@endsection
