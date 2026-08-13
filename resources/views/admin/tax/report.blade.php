@extends('layouts.admin')

@section('title', 'Báo cáo kỳ thuế')
@section('subtitle', $summary['period']['label'].' — chuẩn bị kê khai')

@section('content')
@php
    $p = $summary['period'];
    $profile = $summary['profile'];
    $tp = $summary['tax_period'];
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
@endphp

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.tax.index', ['period' => $p['key']]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Tổng quan</a>
    <a href="{{ route('admin.tax.ledger', ['period' => $p['key']]) }}" class="btn btn-outline-dark btn-sm">Sổ doanh thu</a>
    <a href="{{ route('admin.tax.export', ['period' => $p['key']]) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download"></i> CSV</a>
    <a href="{{ route('admin.tax.report', ['period' => $p['key'], 'print' => 1]) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer"></i> In báo cáo</a>
</div>

<form method="GET" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Chọn kỳ</label>
            <select name="period" class="form-select" onchange="this.form.submit()">
                @foreach($periodOptions as $opt)
                    <option value="{{ $opt['key'] }}" @selected($opt['key'] === $p['key'])>{{ $opt['label'] }} ({{ $opt['key'] }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-8 small text-secondary">
            {{ $p['starts_on'] }} → {{ $p['ends_on'] }} · Hạn: <strong>{{ \Carbon\Carbon::parse($p['due_on'])->format('d/m/Y') }}</strong>
            @if($summary['locked']) <span class="badge text-bg-dark">Đã khóa</span> @endif
        </div>
    </div>
</form>

<div class="alert alert-light border small">
    {{ $profile->disclaimer }}
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card p-3 h-100">
            <div class="small text-secondary">Hộ kinh doanh</div>
            <div class="fw-bold">{{ $profile->business_name }}</div>
            <div class="small">MST: {{ $profile->tax_code ?: '—' }}</div>
            <div class="small">{{ $profile->full_address }}</div>
            <div class="small">CQT: {{ $profile->tax_office ?: '—' }}</div>
            <div class="small mt-1">{{ $profile->method_label }} · {{ $profile->cycle_label }}</div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row g-2">
            <div class="col-6 col-md-4">
                <div class="card p-3 h-100">
                    <div class="small text-secondary">Doanh thu (+)</div>
                    <div class="fs-5 fw-bold text-success">{{ $fmt($summary['revenue_positive']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card p-3 h-100">
                    <div class="small text-secondary">Điều chỉnh</div>
                    <div class="fs-5 fw-bold text-danger">{{ $fmt($summary['adjustment_total']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card p-3 h-100">
                    <div class="small text-secondary">Doanh thu tính thuế</div>
                    <div class="fs-5 fw-bold">{{ $fmt($summary['taxable_revenue']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card p-3 h-100">
                    <div class="small text-secondary">Ước GTGT {{ number_format((float)$profile->vat_rate, 2) }}%</div>
                    <div class="fs-5 fw-bold">{{ $fmt($summary['estimated_vat']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card p-3 h-100">
                    <div class="small text-secondary">Ước TNCN {{ number_format((float)$profile->pit_rate, 2) }}%</div>
                    <div class="fs-5 fw-bold">{{ $fmt($summary['estimated_pit']) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card p-3 h-100 border-primary border-2">
                    <div class="small text-secondary">Tổng ước thuế</div>
                    <div class="fs-5 fw-bold text-primary">{{ $fmt($summary['estimated_total']) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card p-3">
            <h3 class="h6 fw-bold">Theo nhóm</h3>
            @php $groups = \App\Models\TaxLedgerEntry::groupOptions(); @endphp
            <table class="table table-sm mb-0">
                <tbody>
                @forelse($summary['by_group'] as $g => $total)
                    <tr>
                        <td>{{ $groups[$g] ?? $g }}</td>
                        <td class="text-end">{{ $fmt($total) }}</td>
                    </tr>
                @empty
                    <tr><td class="text-secondary">Không có dữ liệu</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="small text-secondary mt-2">
                {{ $summary['entry_count'] }} dòng tính thuế · {{ $summary['excluded_count'] }} loại trừ
                · YTD: {{ $fmt($summary['ytd_revenue']) }}
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-3 mb-3">
            <h3 class="h6 fw-bold">Khóa sổ / mở lại</h3>
            @if($summary['locked'])
                <p class="small">Kỳ <strong>{{ $p['key'] }}</strong> đã khóa
                    @if($tp?->closed_at) lúc {{ $tp->closed_at->format('d/m/Y H:i') }} @endif.
                </p>
                <form method="POST" action="{{ route('admin.tax.period.reopen') }}" onsubmit="return confirm('Mở lại kỳ? Có thể ghi sổ lại.')">
                    @csrf
                    <input type="hidden" name="period" value="{{ $p['key'] }}">
                    <button class="btn btn-outline-warning btn-sm" type="submit">Mở lại kỳ</button>
                </form>
            @else
                <p class="small text-secondary">Khóa sổ lưu snapshot số liệu kỳ và chặn ghi/sửa dòng trong khoảng ngày kỳ.</p>
                <form method="POST" action="{{ route('admin.tax.period.close') }}" onsubmit="return confirm('Khóa sổ kỳ {{ $p['key'] }}?')">
                    @csrf
                    <input type="hidden" name="period" value="{{ $p['key'] }}">
                    <div class="mb-2">
                        <label class="form-label">Ghi chú khóa sổ</label>
                        <textarea name="admin_note" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                    <button class="btn btn-dark btn-sm" type="submit">Khóa sổ kỳ này</button>
                </form>
            @endif
        </div>
        <div class="card p-3">
            <h3 class="h6 fw-bold">Ghi nhận đã nộp thuế</h3>
            <form method="POST" action="{{ route('admin.tax.period.paid') }}" class="row g-2">
                @csrf
                <input type="hidden" name="period" value="{{ $p['key'] }}">
                <div class="col-md-4">
                    <label class="form-label">Số tiền nộp (đ)</label>
                    <input type="number" step="1" min="0" name="paid_amount" class="form-control form-control-sm"
                           value="{{ old('paid_amount', $tp?->paid_amount ?? $summary['estimated_total']) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ngày nộp</label>
                    <input type="date" name="paid_on" class="form-control form-control-sm"
                           value="{{ old('paid_on', optional($tp?->paid_on)->toDateString() ?? now()->toDateString()) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mã/tham chiếu</label>
                    <input type="text" name="payment_ref" class="form-control form-control-sm"
                           value="{{ old('payment_ref', $tp?->payment_ref) }}">
                </div>
                <div class="col-12">
                    <button class="btn btn-success btn-sm" type="submit">Lưu đã nộp</button>
                    @if($tp?->paid_amount !== null)
                        <span class="small text-secondary ms-2">
                            Hiện: {{ $fmt($tp->paid_amount) }}
                            @if($tp->paid_on) ({{ $tp->paid_on->format('d/m/Y') }}) @endif
                        </span>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
