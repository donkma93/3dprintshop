@extends('layouts.admin')

@section('title', 'Chuẩn bị thuế')
@section('subtitle', 'Module riêng — sổ doanh thu & ước tính thuế HKD (không nộp điện tử)')

@section('content')
@php
    $p = $summary['period'];
    $profile = $summary['profile'];
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
@endphp

<div class="alert alert-warning border-0 shadow-sm">
    <div class="fw-semibold mb-1"><i class="bi bi-shield-exclamation"></i> Module chuẩn bị — không thay thế tờ khai</div>
    <div class="small mb-0">
        {{ $profile->disclaimer ?: 'Số liệu chỉ phục vụ quản trị nội bộ và chuẩn bị kê khai. Không tự nộp thuế điện tử.' }}
        Tỷ lệ GTGT/TNCN cấu hình trong hồ sơ — hãy đối chiếu văn bản hiện hành và CQT quản lý.
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.tax.profile') }}" class="btn btn-outline-dark btn-sm"><i class="bi bi-person-badge"></i> Hồ sơ HKD</a>
    <a href="{{ route('admin.tax.ledger', ['period' => $p['key']]) }}" class="btn btn-outline-dark btn-sm"><i class="bi bi-journal-text"></i> Sổ doanh thu</a>
    <a href="{{ route('admin.tax.report', ['period' => $p['key']]) }}" class="btn btn-outline-dark btn-sm"><i class="bi bi-file-earmark-bar-graph"></i> Báo cáo kỳ</a>
    <a href="{{ route('admin.tax.export', ['period' => $p['key']]) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download"></i> Xuất CSV</a>
    <form method="POST" action="{{ route('admin.tax.sync') }}" class="d-inline">
        @csrf
        <button class="btn btn-dark btn-sm" type="submit"><i class="bi bi-arrow-repeat"></i> Đồng bộ bán hàng → sổ</button>
    </form>
</div>

<form method="GET" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Kỳ kê khai</label>
            <select name="period" class="form-select" onchange="this.form.submit()">
                @foreach($periodOptions as $opt)
                    <option value="{{ $opt['key'] }}" @selected($opt['key'] === $p['key'])>{{ $opt['label'] }} ({{ $opt['key'] }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-8 small text-secondary">
            {{ $p['starts_on'] }} → {{ $p['ends_on'] }}
            · Hạn tham chiếu: <strong>{{ \Carbon\Carbon::parse($p['due_on'])->format('d/m/Y') }}</strong>
            @if($summary['days_to_due'] !== null)
                @if($summary['days_to_due'] < 0)
                    <span class="badge text-bg-danger">Quá hạn {{ abs($summary['days_to_due']) }} ngày</span>
                @elseif($summary['days_to_due'] <= 15)
                    <span class="badge text-bg-warning">Còn {{ $summary['days_to_due'] }} ngày</span>
                @else
                    <span class="badge text-bg-secondary">Còn {{ $summary['days_to_due'] }} ngày</span>
                @endif
            @endif
            @if($summary['locked'])
                <span class="badge text-bg-dark">Đã khóa sổ</span>
            @endif
        </div>
    </div>
</form>

@if($summary['threshold_warning'])
<div class="alert alert-danger">
    <strong>Cảnh báo ngưỡng doanh thu năm:</strong>
    Lũy kế {{ $fmt($summary['ytd_revenue']) }}
    @if($summary['threshold'])
        / ngưỡng {{ $fmt($summary['threshold']) }}
        ({{ number_format(($summary['threshold_ratio'] ?? 0) * 100, 1) }}%).
    @endif
    Cân nhắc rà soát nghĩa vụ và phương pháp tính thuế với CQT / kế toán.
</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card p-3 h-100">
            <div class="small text-secondary">Doanh thu tính thuế (kỳ)</div>
            <div class="fs-4 fw-bold text-success">{{ $fmt($summary['taxable_revenue']) }}</div>
            <div class="small text-secondary">{{ $summary['entry_count'] }} dòng · loại trừ {{ $summary['excluded_count'] }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 h-100">
            <div class="small text-secondary">Ước GTGT ({{ rtrim(rtrim(number_format((float)$profile->vat_rate, 2, '.', ''), '0'), '.') }}%)</div>
            <div class="fs-4 fw-bold">{{ $fmt($summary['estimated_vat']) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 h-100">
            <div class="small text-secondary">Ước TNCN ({{ rtrim(rtrim(number_format((float)$profile->pit_rate, 2, '.', ''), '0'), '.') }}%)</div>
            <div class="fs-4 fw-bold">{{ $fmt($summary['estimated_pit']) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 h-100 border-2" style="border-color: var(--accent)!important">
            <div class="small text-secondary">Tổng ước thuế kỳ</div>
            <div class="fs-4 fw-bold text-primary">{{ $fmt($summary['estimated_total']) }}</div>
            <div class="small text-secondary">Doanh thu + điều chỉnh = {{ $fmt($summary['taxable_revenue']) }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card p-3">
            <h3 class="h6 fw-bold">Doanh thu theo ngày (kỳ {{ $p['label'] }})</h3>
            @if($summary['by_day']->isEmpty())
                <p class="text-secondary small mb-0">Chưa có dòng sổ trong kỳ. Bấm <strong>Đồng bộ bán hàng → sổ</strong> hoặc ghi thủ công.</p>
            @else
                <div class="table-responsive" style="max-height: 320px; overflow:auto">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Ngày</th><th class="text-end">Số dòng</th><th class="text-end">Tổng</th></tr></thead>
                        <tbody>
                        @foreach($summary['by_day'] as $day)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($day->day)->format('d/m/Y') }}</td>
                                <td class="text-end">{{ (int) $day->entries }}</td>
                                <td class="text-end">{{ $fmt($day->total) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card p-3 mb-3">
            <h3 class="h6 fw-bold">Theo nhóm ngành (ước tính)</h3>
            @php $groups = \App\Models\TaxLedgerEntry::groupOptions(); @endphp
            <ul class="list-group list-group-flush">
                @forelse($summary['by_group'] as $g => $total)
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>{{ $groups[$g] ?? $g }}</span>
                        <strong>{{ $fmt($total) }}</strong>
                    </li>
                @empty
                    <li class="list-group-item px-0 text-secondary small">Chưa có dữ liệu.</li>
                @endforelse
            </ul>
        </div>
        <div class="card p-3">
            <h3 class="h6 fw-bold">Hồ sơ & lũy kế năm</h3>
            <div class="small">
                <div><strong>{{ $profile->business_name }}</strong></div>
                <div>MST: {{ $profile->tax_code ?: '—' }} · {{ $profile->method_label }}</div>
                <div>Chu kỳ: {{ $profile->cycle_label }}</div>
                <div class="mt-2">Doanh thu YTD: <strong>{{ $fmt($summary['ytd_revenue']) }}</strong></div>
                @if($summary['threshold'])
                    <div class="progress mt-2" style="height: 8px">
                        <div class="progress-bar {{ $summary['threshold_warning'] ? 'bg-danger' : 'bg-success' }}"
                             style="width: {{ min(100, ($summary['threshold_ratio'] ?? 0) * 100) }}%"></div>
                    </div>
                    <div class="text-secondary mt-1">Ngưỡng cảnh báo: {{ $fmt($summary['threshold']) }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($closedPeriods->isNotEmpty())
<div class="card p-3">
    <h3 class="h6 fw-bold">Kỳ đã khóa gần đây</h3>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
            <tr>
                <th>Kỳ</th>
                <th>Doanh thu TT</th>
                <th>Ước thuế</th>
                <th>Đã nộp</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($closedPeriods as $cp)
                <tr>
                    <td>{{ $cp->period_key }}</td>
                    <td>{{ $fmt($cp->taxable_revenue) }}</td>
                    <td>{{ $fmt($cp->estimated_total) }}</td>
                    <td>{{ $cp->paid_amount !== null ? $fmt($cp->paid_amount) : '—' }}</td>
                    <td><a href="{{ route('admin.tax.report', ['period' => $cp->period_key]) }}">Xem</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
