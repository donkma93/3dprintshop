@extends('layouts.admin')

@section('title', 'Doanh thu & lãi lỗ')
@section('subtitle', 'Đối chiếu bán sản phẩm (QR) và chi phí nhập nguyên liệu')

@section('content')
<form method="GET" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Từ ngày</label>
            <input type="date" name="from" class="form-control" value="{{ $from->toDateString() }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Đến ngày</label>
            <input type="date" name="to" class="form-control" value="{{ $to->toDateString() }}">
        </div>
        <div class="col-md-3">
            <button class="btn btn-dark">Áp dụng</button>
            <a href="{{ route('admin.sales.report') }}" class="btn btn-outline-secondary">Tháng này</a>
        </div>
        <div class="col-md-3 text-md-end small text-secondary">
            Kỳ: {{ $from->format('d/m/Y') }} → {{ $to->format('d/m/Y') }}
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-4 col-xl-2">
        <div class="card p-3 h-100">
            <div class="small text-secondary">Doanh thu bán SP</div>
            <div class="fs-5 fw-bold text-success">{{ number_format($salesSummary['revenue'], 0, ',', '.') }} đ</div>
            <div class="small text-secondary">{{ $salesSummary['orders'] }} GD · {{ $salesSummary['units'] }} sp</div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card p-3 h-100">
            <div class="small text-secondary">Giá vốn SP đã bán</div>
            <div class="fs-5 fw-bold">{{ number_format($salesSummary['cogs'], 0, ',', '.') }} đ</div>
            <div class="small text-secondary">cost_price × SL</div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card p-3 h-100">
            <div class="small text-secondary">Lãi gộp (bán SP)</div>
            <div class="fs-5 fw-bold {{ $salesSummary['gross_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                {{ number_format($salesSummary['gross_profit'], 0, ',', '.') }} đ
            </div>
            <div class="small text-secondary">Doanh thu − giá vốn</div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card p-3 h-100">
            <div class="small text-secondary">Chi nhập nguyên liệu</div>
            <div class="fs-5 fw-bold text-danger">{{ number_format($materialSpend, 0, ',', '.') }} đ</div>
            <div class="small text-secondary">Trong kỳ báo cáo</div>
        </div>
    </div>
    <div class="col-md-8 col-xl-4">
        <div class="card p-3 h-100 border-2" style="border-color: var(--accent)!important">
            <div class="small text-secondary">Lãi / lỗ vận hành (ước tính kỳ)</div>
            <div class="fs-4 fw-bold {{ $operatingProfit >= 0 ? 'text-success' : 'text-danger' }}">
                {{ number_format($operatingProfit, 0, ',', '.') }} đ
            </div>
            <div class="small text-secondary">
                = Lãi gộp bán SP − Chi nhập NL<br>
                <span class="text-muted">Chưa trừ khấu hao thiết bị / chi phí khác.</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card p-3">
            <h3 class="h6 fw-bold">Theo ngày</h3>
            <canvas id="profitChart" height="140"></canvas>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card p-3">
            <h3 class="h6 fw-bold">Top sản phẩm theo doanh thu</h3>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>SP</th>
                        <th>SL</th>
                        <th>DT</th>
                        <th>Lãi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($topProducts as $row)
                        <tr>
                            <td class="small">{{ $row->product?->name ?? ('#'.$row->product_id) }}</td>
                            <td>{{ (int) $row->units }}</td>
                            <td>{{ number_format($row->revenue, 0, ',', '.') }}</td>
                            <td class="{{ $row->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($row->profit, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-secondary">Chưa có bán trong kỳ.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card p-3">
            <div class="d-flex justify-content-between mb-2">
                <h3 class="h6 fw-bold mb-0">Bán SP gần đây</h3>
                <a href="{{ route('admin.sales.history', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="small">Lịch sử</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Thời gian</th><th>SP</th><th>SL</th><th>DT</th><th>Lãi</th></tr></thead>
                    <tbody>
                    @forelse($recentSales as $sale)
                        <tr>
                            <td class="small">{{ optional($sale->sold_at)->format('d/m H:i') }}</td>
                            <td class="small">{{ $sale->product?->name }}</td>
                            <td>{{ $sale->quantity }}</td>
                            <td>{{ number_format($sale->total_price, 0, ',', '.') }}</td>
                            <td class="{{ $sale->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($sale->profit, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-secondary">—</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-3">
            <div class="d-flex justify-content-between mb-2">
                <h3 class="h6 fw-bold mb-0">Nhập nguyên liệu trong kỳ</h3>
                <a href="{{ route('admin.material-inputs.index') }}" class="small">Nhập NL</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Ngày</th><th>NL</th><th>SL</th><th>Thành tiền</th></tr></thead>
                    <tbody>
                    @forelse($materialInputs as $input)
                        <tr>
                            <td class="small">{{ optional($input->input_date)->format('d/m/Y') ?? $input->input_date }}</td>
                            <td class="small">{{ $input->material?->name ?? '—' }}</td>
                            <td>{{ $input->quantity }} {{ $input->material?->unit }}</td>
                            <td class="text-danger">{{ number_format($input->total_price, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-secondary">Không có phiếu nhập trong kỳ.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $chartByDay = $byDay->map(function ($r) {
        return [
            'day' => $r->day,
            'revenue' => (float) $r->revenue,
            'profit' => (float) $r->profit,
            'cogs' => (float) $r->cogs,
        ];
    })->values();
@endphp
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    var byDay = @json($chartByDay);
    var materialByDay = @json($materialByDay);

    var labels = byDay.map(function (r) { return r.day; });
    // Gộp ngày có nhập NL nhưng chưa bán
    Object.keys(materialByDay || {}).forEach(function (d) {
        if (labels.indexOf(d) === -1) labels.push(d);
    });
    labels.sort();

    var revenue = labels.map(function (d) {
        var row = byDay.find(function (r) { return r.day === d; });
        return row ? row.revenue : 0;
    });
    var profit = labels.map(function (d) {
        var row = byDay.find(function (r) { return r.day === d; });
        return row ? row.profit : 0;
    });
    var spend = labels.map(function (d) {
        return Number(materialByDay[d] || 0);
    });

    var ctx = document.getElementById('profitChart');
    if (!ctx || typeof Chart === 'undefined') return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Doanh thu SP', data: revenue, backgroundColor: 'rgba(20,184,166,.55)' },
                { label: 'Lãi gộp SP', data: profit, backgroundColor: 'rgba(34,197,94,.45)' },
                { label: 'Chi nhập NL', data: spend, backgroundColor: 'rgba(239,68,68,.45)' }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { ticks: { callback: function (v) { return new Intl.NumberFormat('vi-VN').format(v); } } }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function (c) {
                            return c.dataset.label + ': ' + new Intl.NumberFormat('vi-VN').format(c.raw) + ' đ';
                        }
                    }
                }
            }
        }
    });
})();
</script>
@endpush
