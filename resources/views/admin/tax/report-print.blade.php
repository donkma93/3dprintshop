<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Báo cáo thuế {{ $summary['period']['key'] }}</title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #000; margin: 16mm; }
        h1 { font-size: 16pt; text-align: center; margin: 0 0 4px; }
        h2 { font-size: 12pt; text-align: center; font-weight: normal; margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #333; padding: 6px 8px; vertical-align: top; }
        th { background: #f2f2f2; text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        .muted { color: #444; font-size: 10pt; }
        .no-border td { border: none; padding: 2px 0; }
        .footer { margin-top: 24px; font-size: 10pt; }
        @media print {
            body { margin: 12mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
@php
    $p = $summary['period'];
    $profile = $summary['profile'];
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $groups = \App\Models\TaxLedgerEntry::groupOptions();
@endphp

<div class="no-print" style="margin-bottom:12px">
    <button onclick="window.print()">In</button>
    <a href="{{ route('admin.tax.report', ['period' => $p['key']]) }}">Quay lại</a>
</div>

<h1>BẢNG TỔNG HỢP DOANH THU & ƯỚC TÍNH THUẾ</h1>
<h2>Chuẩn bị kê khai hộ kinh doanh — {{ $p['label'] }} ({{ $p['key'] }})</h2>

<table class="no-border">
    <tr><td width="30%"><strong>Tên hộ / cửa hàng</strong></td><td>{{ $profile->business_name }}</td></tr>
    <tr><td><strong>Chủ hộ</strong></td><td>{{ $profile->owner_name ?: '—' }}</td></tr>
    <tr><td><strong>MST</strong></td><td>{{ $profile->tax_code ?: '—' }}</td></tr>
    <tr><td><strong>Địa chỉ</strong></td><td>{{ $profile->full_address ?: '—' }}</td></tr>
    <tr><td><strong>Ngành nghề</strong></td><td>{{ $profile->business_line ?: '—' }}</td></tr>
    <tr><td><strong>CQT quản lý</strong></td><td>{{ $profile->tax_office ?: '—' }}</td></tr>
    <tr><td><strong>Kỳ</strong></td><td>{{ $p['starts_on'] }} → {{ $p['ends_on'] }} · Hạn tham chiếu {{ $p['due_on'] }}</td></tr>
    <tr><td><strong>Phương pháp / chu kỳ</strong></td><td>{{ $profile->method_label }} · {{ $profile->cycle_label }}</td></tr>
</table>

<table>
    <thead>
    <tr>
        <th>Chỉ tiêu</th>
        <th class="num">Số tiền</th>
    </tr>
    </thead>
    <tbody>
    <tr><td>Doanh thu dương trong kỳ</td><td class="num">{{ $fmt($summary['revenue_positive']) }}</td></tr>
    <tr><td>Điều chỉnh / trả hàng</td><td class="num">{{ $fmt($summary['adjustment_total']) }}</td></tr>
    <tr><td><strong>Doanh thu tính thuế</strong></td><td class="num"><strong>{{ $fmt($summary['taxable_revenue']) }}</strong></td></tr>
    <tr><td>Ước GTGT ({{ number_format((float)$profile->vat_rate, 2) }}%)</td><td class="num">{{ $fmt($summary['estimated_vat']) }}</td></tr>
    <tr><td>Ước TNCN ({{ number_format((float)$profile->pit_rate, 2) }}%)</td><td class="num">{{ $fmt($summary['estimated_pit']) }}</td></tr>
    <tr><td><strong>Tổng ước thuế phải nộp (tham chiếu)</strong></td><td class="num"><strong>{{ $fmt($summary['estimated_total']) }}</strong></td></tr>
    <tr><td>Doanh thu lũy kế năm (đến hết kỳ)</td><td class="num">{{ $fmt($summary['ytd_revenue']) }}</td></tr>
    @if($summary['threshold'])
    <tr><td>Ngưỡng cảnh báo cấu hình</td><td class="num">{{ $fmt($summary['threshold']) }}</td></tr>
    @endif
    </tbody>
</table>

@if(!empty($summary['by_group']))
<table>
    <thead>
    <tr><th>Nhóm ngành (sổ nội bộ)</th><th class="num">Doanh thu</th></tr>
    </thead>
    <tbody>
    @foreach($summary['by_group'] as $g => $total)
        <tr><td>{{ $groups[$g] ?? $g }}</td><td class="num">{{ $fmt($total) }}</td></tr>
    @endforeach
    </tbody>
</table>
@endif

<p class="muted">
    Số dòng tính thuế: {{ $summary['entry_count'] }} · Loại trừ: {{ $summary['excluded_count'] }}
    · Trạng thái kỳ: {{ $summary['locked'] ? 'Đã khóa sổ' : 'Đang mở' }}
</p>

<div class="footer">
    <p><strong>Lưu ý:</strong> {{ $profile->disclaimer }}</p>
    <p>Tài liệu nội bộ phục vụ chuẩn bị kê khai. Không phải tờ khai gửi CQT. Tỷ lệ % do người dùng cấu hình — đối chiếu văn bản pháp luật hiện hành.</p>
    <p>In lúc: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<script>window.addEventListener('load', function () { /* optional auto print */ });</script>
</body>
</html>
