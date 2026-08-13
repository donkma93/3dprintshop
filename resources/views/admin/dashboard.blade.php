@extends('layouts.admin')

@section('title', 'Tổng quan')
@section('subtitle', 'Biểu đồ và thống kê hệ thống')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary small">Sản phẩm</div>
                    <div class="fs-3 fw-bold">{{ $stats['products'] }}</div>
                    <div class="small text-success">{{ $stats['active_products'] }} đang hiển thị</div>
                </div>
                <div class="icon"><i class="bi bi-box-seam"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary small">Nguyên liệu</div>
                    <div class="fs-3 fw-bold">{{ $stats['materials'] }}</div>
                    <div class="small text-warning">{{ $stats['low_stock_materials'] }} sắp hết</div>
                </div>
                <div class="icon"><i class="bi bi-droplet-half"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary small">Thiết bị</div>
                    <div class="fs-3 fw-bold">{{ $stats['equipment'] }}</div>
                    <div class="small text-secondary">{{ $stats['material_inputs'] }} phiếu nhập</div>
                </div>
                <div class="icon"><i class="bi bi-printer"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-secondary small">Chat đang mở</div>
                    <div class="fs-3 fw-bold">{{ $stats['open_chats'] }}</div>
                    <div class="small text-secondary">{{ $stats['categories'] }} danh mục</div>
                </div>
                <div class="icon"><i class="bi bi-chat-dots"></i></div>
            </div>
        </div>
    </div>
</div>

@if($canRevenue)
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm" style="border-left:4px solid #0f766e !important">
            <div class="text-secondary small">Giá trị tồn nguyên liệu</div>
            <div class="fs-5 fw-bold">{{ number_format($stats['material_stock_value'], 0, ',', '.') }} đ</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm" style="border-left:4px solid #ca8a04 !important">
            <div class="text-secondary small">Giá trị thiết bị</div>
            <div class="fs-5 fw-bold">{{ number_format($stats['equipment_value'], 0, ',', '.') }} đ</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm" style="border-left:4px solid #2563eb !important">
            <div class="text-secondary small">Doanh số kho (giá bán × tồn)</div>
            <div class="fs-5 fw-bold">{{ number_format($stats['catalog_sales_value'], 0, ',', '.') }} đ</div>
            <div class="small text-secondary">Biên tiềm năng: {{ number_format($stats['potential_margin'], 0, ',', '.') }} đ</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm" style="border-left:4px solid #dc2626 !important">
            <div class="text-secondary small">Chi nhập NL (30 ngày)</div>
            <div class="fs-5 fw-bold">{{ number_format($stats['inputs_total_30d'], 0, ',', '.') }} đ</div>
            <div class="small text-secondary">Tổng: {{ number_format($stats['inputs_total_all'], 0, ',', '.') }} đ</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card p-3 border-0 shadow-sm" style="border-left:4px solid #16a34a !important">
            <div class="text-secondary small">Doanh thu bán QR (tháng này)</div>
            <div class="fs-5 fw-bold text-success">{{ number_format($stats['qr_sales_revenue_month'] ?? 0, 0, ',', '.') }} đ</div>
            <div class="small text-secondary">
                {{ (int) ($stats['qr_sales_count_month'] ?? 0) }} GD · {{ (int) ($stats['qr_sales_units_month'] ?? 0) }} sp
                · Lãi gộp {{ number_format($stats['qr_sales_profit_month'] ?? 0, 0, ',', '.') }} đ
            </div>
            <a href="{{ route('admin.sales.report') }}" class="small">Xem báo cáo lãi lỗ →</a>
        </div>
    </div>
</div>
@else
<div class="alert alert-light border mb-4 d-flex align-items-start gap-2">
    <i class="bi bi-shield-lock text-secondary fs-5"></i>
    <div class="small">
        <strong>Số liệu doanh thu / doanh số chỉ dành cho Quản trị viên.</strong>
        Tài khoản của bạn không xem được giá trị tồn, chi phí nhập và doanh số kho.
    </div>
</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card p-3 h-100">
            <h2 class="h6 mb-3">Hoạt động 6 tháng gần đây</h2>
            <div style="position:relative;height:300px">
                <canvas id="chartActivity"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3 h-100">
            <h2 class="h6 mb-3">Cơ cấu tồn kho / sản phẩm</h2>
            <div style="position:relative;height:300px">
                <canvas id="chartStock"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <h2 class="h6 mb-3">Sản phẩm theo danh mục</h2>
            <div style="position:relative;height:280px">
                <canvas id="chartCategories"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-3 h-100">
            <h2 class="h6 mb-3">Tồn nguyên liệu (top 8)</h2>
            <div style="position:relative;height:280px">
                <canvas id="chartMaterials"></canvas>
            </div>
        </div>
    </div>
</div>

@if($canRevenue)
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card p-3 h-100">
            <h2 class="h6 mb-3">Chi phí nhập nguyên liệu theo tháng</h2>
            <div style="position:relative;height:280px">
                <canvas id="chartSpend"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card p-3 h-100">
            <h2 class="h6 mb-3">Cơ cấu giá trị tài sản / doanh số</h2>
            <div style="position:relative;height:280px">
                <canvas id="chartAssets"></canvas>
            </div>
        </div>
    </div>
</div>
@endif

@if($lowStock->isNotEmpty())
<div class="card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 mb-0">Cảnh báo nguyên liệu sắp hết</h2>
        @if(auth()->user()->hasPermission('materials.manage'))
            <a href="{{ route('admin.materials.index') }}" class="small">Quản lý</a>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-2">
        @foreach($lowStock as $material)
            <span class="badge badge-warn px-3 py-2">
                {{ $material->name }}: {{ $material->stock_quantity }} {{ $material->unit }}
                <span class="opacity-75">(min {{ $material->min_stock }})</span>
            </span>
        @endforeach
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    var charts = @json($charts);
    var canRevenue = @json($canRevenue);
    var gold = '#ca8a04';
    var teal = '#0f766e';
    var blue = '#2563eb';
    var slate = '#64748b';
    var palette = ['#0f766e', '#ca8a04', '#2563eb', '#dc2626', '#7c3aed', '#0891b2', '#ea580c', '#4f46e5'];

    Chart.defaults.font.family = 'system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
    Chart.defaults.color = '#64748b';

    new Chart(document.getElementById('chartActivity'), {
        type: 'line',
        data: {
            labels: charts.months || [],
            datasets: [
                {
                    label: 'Sản phẩm mới',
                    data: charts.products_created || [],
                    borderColor: teal,
                    backgroundColor: 'rgba(15,118,110,.12)',
                    fill: true,
                    tension: 0.35,
                },
                {
                    label: 'Phiếu nhập NL',
                    data: charts.inputs_count || [],
                    borderColor: gold,
                    backgroundColor: 'rgba(202,138,4,.12)',
                    fill: true,
                    tension: 0.35,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    new Chart(document.getElementById('chartStock'), {
        type: 'doughnut',
        data: {
            labels: (charts.stock_overview && charts.stock_overview.labels) || [],
            datasets: [{
                data: (charts.stock_overview && charts.stock_overview.values) || [],
                backgroundColor: [teal, slate, blue, gold],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    new Chart(document.getElementById('chartCategories'), {
        type: 'bar',
        data: {
            labels: (charts.categories && charts.categories.labels) || [],
            datasets: [{
                label: 'Số SP',
                data: (charts.categories && charts.categories.values) || [],
                backgroundColor: teal,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    var matLabels = (charts.top_materials || []).map(function (m) { return m.name; });
    var matValues = (charts.top_materials || []).map(function (m) { return m.qty; });
    new Chart(document.getElementById('chartMaterials'), {
        type: 'bar',
        data: {
            labels: matLabels,
            datasets: [{
                label: 'Tồn',
                data: matValues,
                backgroundColor: gold,
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });

    if (canRevenue && document.getElementById('chartSpend')) {
        new Chart(document.getElementById('chartSpend'), {
            type: 'bar',
            data: {
                labels: charts.months || [],
                datasets: [{
                    label: 'Chi nhập (đ)',
                    data: charts.inputs_spend || [],
                    backgroundColor: 'rgba(220,38,38,.75)',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ' ' + Number(ctx.raw || 0).toLocaleString('vi-VN') + ' đ';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (v) {
                                return Number(v).toLocaleString('vi-VN');
                            }
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('chartAssets'), {
            type: 'pie',
            data: {
                labels: (charts.asset_breakdown && charts.asset_breakdown.labels) || [],
                datasets: [{
                    data: (charts.asset_breakdown && charts.asset_breakdown.values) || [],
                    backgroundColor: palette,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var v = Number(ctx.raw || 0).toLocaleString('vi-VN');
                                return ' ' + ctx.label + ': ' + v + ' đ';
                            }
                        }
                    }
                }
            }
        });
    }
})();
</script>
@endpush
