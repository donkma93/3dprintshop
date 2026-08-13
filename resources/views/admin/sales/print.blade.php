@extends('layouts.admin')

@section('title', 'Phiếu gửi hàng '.$sale->sale_code)
@section('subtitle', 'In nhiệt / in dán kiện — chọn khổ giấy')

@php
    $weight = $sale->package_weight;
    if (!$weight && $sale->product?->weight_grams) {
        $weight = (int) round((float) $sale->product->weight_grams * (int) $sale->quantity);
    }
    $pkgCount = max(1, (int) ($sale->package_count ?: 1));
    $goodsContent = $sale->goods_content ?: ($sale->product?->name ?? '—');
    $declared = $sale->declared_value !== null ? (float) $sale->declared_value : (float) $sale->total_price;
    $recvAddrLine = $sale->effective_receiver_address;
    $recvWard = $sale->effective_receiver_ward;
    $recvDistrict = $sale->effective_receiver_district;
    $recvProvince = $sale->effective_receiver_province;
    $recvPostal = $sale->effective_receiver_postal_code;

    // Khổ in: 80mm (nhiệt mặc định), 58mm, A5, A4
    $paper = request('paper', '80mm');
    $allowedPapers = ['58mm', '80mm', 'a5', 'a4'];
    if (! in_array($paper, $allowedPapers, true)) {
        $paper = '80mm';
    }
@endphp

@section('content')
<div class="no-print print-toolbar mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
        <button type="button" class="btn btn-dark" id="btn_print">
            <i class="bi bi-printer"></i> In phiếu
        </button>
        <a href="{{ route('admin.sales.scan') }}" class="btn btn-outline-dark">Quét bán tiếp</a>
        <a href="{{ route('admin.sales.history') }}" class="btn btn-outline-secondary">Lịch sử bán</a>
        @if($sale->product)
            <a href="{{ route('admin.sales.scan', ['code' => $sale->product->qr_token]) }}" class="btn btn-link btn-sm">
                ← Quay lại SP
            </a>
        @endif
    </div>

    <div class="card p-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-semibold small me-1">Khổ giấy in:</span>
            @foreach([
                '80mm' => 'Nhiệt 80mm',
                '58mm' => 'Nhiệt 58mm',
                'a5' => 'A5',
                'a4' => 'A4',
            ] as $key => $label)
                <a href="{{ route('admin.sales.print', ['sale' => $sale, 'paper' => $key]) }}"
                   class="btn btn-sm {{ $paper === $key ? 'btn-dark' : 'btn-outline-secondary' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <p class="small text-secondary mb-0 mt-2">
            Xem trước đúng khổ đã chọn — <strong>không co chữ</strong>.
            Máy in nhiệt: chọn <strong>80mm</strong> hoặc <strong>58mm</strong>, trong hộp thoại in tắt “Vừa với trang / Fit to page”, đặt lề 0 hoặc tối thiểu.
        </p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success no-print">{{ session('success') }}</div>
@endif

<div class="ship-preview-wrap paper-{{ $paper }}">
    <div class="ship-preview-label no-print">
        Xem trước ·
        @if($paper === '80mm') cuộn nhiệt 80mm
        @elseif($paper === '58mm') cuộn nhiệt 58mm
        @elseif($paper === 'a5') khổ A5
        @else khổ A4
        @endif
    </div>

    <div id="ship-print-root" class="ship-root paper-{{ $paper }}" data-paper="{{ $paper }}">
        <div class="ship-ticket">
            <div class="ship-ticket__header">
                <div class="ship-ticket__title">PHIẾU GỬI HÀNG</div>
                <div class="ship-ticket__code">{{ $sale->sale_code }}</div>
                <div class="ship-ticket__date">{{ optional($sale->sold_at)->format('d/m/Y H:i') }}</div>
                @if($sale->carrier)
                    <div class="ship-ticket__carrier">{{ $sale->carrier_label }}
                        @if($sale->service_label !== '—')
                            · {{ $sale->service_label }}
                        @endif
                    </div>
                @endif
            </div>

            <div class="ship-ticket__block">
                <div class="ship-ticket__block-title">NGƯỜI GỬI</div>
                <div class="ship-ticket__name">{{ $sender['name'] }}</div>
                @if(!empty($sender['phone']))
                    <div class="ship-ticket__line"><span class="k">ĐT:</span> {{ $sender['phone'] }}</div>
                @endif
                @if(!empty($sender['address']))
                    <div class="ship-ticket__line"><span class="k">ĐC:</span> {{ $sender['address'] }}</div>
                @endif
            </div>

            <div class="ship-ticket__block ship-ticket__block--recv">
                <div class="ship-ticket__block-title">NGƯỜI NHẬN</div>
                <div class="ship-ticket__name">{{ $sale->effective_receiver_name ?: '—' }}</div>
                <div class="ship-ticket__phone">{{ $sale->effective_receiver_phone ?: '—' }}</div>
                @if($recvAddrLine)
                    <div class="ship-ticket__line">{{ $recvAddrLine }}</div>
                @endif
                @if($recvWard)
                    <div class="ship-ticket__line">P/X: {{ $recvWard }}</div>
                @endif
                @if($recvDistrict)
                    <div class="ship-ticket__line">Q/H: {{ $recvDistrict }}</div>
                @endif
                @if($recvProvince)
                    <div class="ship-ticket__line ship-ticket__line--strong">Tỉnh/TP: {{ $recvProvince }}</div>
                @endif
                @if($recvPostal)
                    <div class="ship-ticket__line">Mã BC: {{ $recvPostal }}</div>
                @endif
                <div class="ship-ticket__full">
                    {{ $sale->effective_receiver_full_address ?: '—' }}
                </div>
            </div>

            <div class="ship-ticket__block">
                <div class="ship-ticket__block-title">HÀNG HÓA</div>
                <div class="ship-ticket__goods-name">{{ $goodsContent }}</div>
                @if($sale->product)
                    <div class="ship-ticket__line">SKU: {{ $sale->product->sku ?: '—' }}</div>
                @endif
                <div class="ship-ticket__row">
                    <span>SL: <b>{{ $sale->quantity }}</b></span>
                    <span>Kiện: <b>{{ $pkgCount }}</b></span>
                    <span>KL: <b>{{ $weight ? number_format($weight, 0, ',', '.').'g' : '—' }}</b></span>
                </div>
                <div class="ship-ticket__row">
                    <span>Đơn giá: {{ number_format($sale->unit_price, 0, ',', '.') }}</span>
                </div>
                <div class="ship-ticket__row ship-ticket__row--total">
                    <span>Thành tiền</span>
                    <b>{{ number_format($sale->total_price, 0, ',', '.') }} đ</b>
                </div>
                <div class="ship-ticket__row">
                    <span>Giá trị KB</span>
                    <span>{{ number_format($declared, 0, ',', '.') }} đ</span>
                </div>
            </div>

            <div class="ship-ticket__block ship-ticket__block--pay">
                <div class="ship-ticket__row">
                    <span>Thanh toán</span>
                    <b>{{ $sale->payment_label !== '—' ? $sale->payment_label : '—' }}</b>
                </div>
                <div class="ship-ticket__cod">
                    COD:
                    @if($sale->payment_method === \App\Models\ProductSale::PAYMENT_COD && $sale->cod_amount !== null)
                        {{ number_format($sale->cod_amount, 0, ',', '.') }} đ
                    @else
                        0 đ
                    @endif
                </div>
            </div>

            @if($sale->shipping_note || $sale->note)
            <div class="ship-ticket__block">
                <div class="ship-ticket__block-title">GHI CHÚ</div>
                @if($sale->shipping_note)
                    <div class="ship-ticket__line">{{ $sale->shipping_note }}</div>
                @endif
                @if($sale->note)
                    <div class="ship-ticket__line">{{ $sale->note }}</div>
                @endif
            </div>
            @endif

            <div class="ship-ticket__signs">
                <div>
                    <div class="ship-ticket__sign-label">Người gửi</div>
                    <div class="ship-ticket__sign-space"></div>
                </div>
                <div>
                    <div class="ship-ticket__sign-label">Shipper</div>
                    <div class="ship-ticket__sign-space"></div>
                </div>
            </div>

            <div class="ship-ticket__footer">
                NV: {{ $sale->seller?->name ?? '—' }}
                @if($sale->source_label && $sale->source_label !== '—')
                    · {{ $sale->source_label }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
/* ===== Toolbar (màn hình) ===== */
.print-toolbar .card { background: #fff; }

/* Khung xem trước: đúng khổ giấy, KHÔNG scale */
.ship-preview-wrap {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
    padding-bottom: 2rem;
    overflow-x: auto;
}
.ship-preview-label {
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 600;
}

.ship-root {
    background: #fff;
    color: #000;
    box-sizing: border-box;
    /* Không dùng transform/zoom/scale — kích thước thật */
    transform: none !important;
    zoom: 1 !important;
}

/* ----- Khổ xem trước trên màn hình = đúng mm ----- */
.ship-root.paper-58mm { width: 58mm; }
.ship-root.paper-80mm { width: 80mm; }
.ship-root.paper-a5   { width: 148mm; }
.ship-root.paper-a4   { width: 210mm; }

.ship-ticket {
    width: 100%;
    box-sizing: border-box;
    padding: 3mm;
    border: 1px dashed #94a3b8;
    font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
    font-size: 11px;
    line-height: 1.35;
    color: #000;
    background: #fff;
}

/* Nhiệt 80mm: chữ to, dễ đọc */
.ship-root.paper-80mm .ship-ticket {
    font-size: 12px;
    padding: 3mm 2.5mm;
}
/* Nhiệt 58mm: hơi nhỏ hơn nhưng vẫn 1 cột */
.ship-root.paper-58mm .ship-ticket {
    font-size: 10.5px;
    padding: 2mm;
}
/* A5 / A4: rộng hơn, padding thoáng */
.ship-root.paper-a5 .ship-ticket,
.ship-root.paper-a4 .ship-ticket {
    font-size: 12px;
    padding: 6mm 8mm;
}

.ship-ticket__header {
    text-align: center;
    border-bottom: 2px solid #000;
    padding-bottom: 2.5mm;
    margin-bottom: 2.5mm;
}
.ship-ticket__title {
    font-size: 1.35em;
    font-weight: 900;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.ship-ticket__code {
    font-size: 1.2em;
    font-weight: 800;
    margin-top: 1mm;
    word-break: break-all;
}
.ship-ticket__date {
    margin-top: 0.5mm;
    font-size: 0.95em;
}
.ship-ticket__carrier {
    margin-top: 1.5mm;
    display: inline-block;
    border: 1.5px solid #000;
    padding: 0.8mm 2mm;
    font-weight: 700;
    font-size: 0.95em;
}

.ship-ticket__block {
    border: 1.5px solid #000;
    padding: 2mm;
    margin-bottom: 2.5mm;
    page-break-inside: auto;
    break-inside: auto;
}
.ship-ticket__block--recv {
    border-width: 2.5px;
}
.ship-ticket__block--pay {
    background: #fff;
}
.ship-ticket__block-title {
    font-weight: 900;
    font-size: 0.9em;
    letter-spacing: 0.04em;
    border-bottom: 1px solid #000;
    padding-bottom: 1mm;
    margin-bottom: 1.5mm;
    text-transform: uppercase;
}
.ship-ticket__name {
    font-size: 1.25em;
    font-weight: 900;
    margin-bottom: 0.8mm;
    word-break: break-word;
}
.ship-ticket__phone {
    font-size: 1.35em;
    font-weight: 900;
    margin-bottom: 1.5mm;
    letter-spacing: 0.02em;
}
.ship-ticket__line {
    margin-bottom: 0.6mm;
    word-break: break-word;
}
.ship-ticket__line--strong {
    font-weight: 800;
}
.ship-ticket__line .k {
    font-weight: 700;
}
.ship-ticket__full {
    margin-top: 1.5mm;
    padding-top: 1.5mm;
    border-top: 1px dashed #000;
    font-weight: 700;
    word-break: break-word;
}
.ship-ticket__goods-name {
    font-weight: 800;
    font-size: 1.05em;
    margin-bottom: 1mm;
    word-break: break-word;
}
.ship-ticket__row {
    display: flex;
    justify-content: space-between;
    gap: 2mm;
    flex-wrap: wrap;
    margin-top: 0.8mm;
}
.ship-ticket__row--total {
    border-top: 1px solid #000;
    margin-top: 1.5mm;
    padding-top: 1.5mm;
    font-size: 1.1em;
}
.ship-ticket__cod {
    margin-top: 1.5mm;
    font-size: 1.35em;
    font-weight: 900;
    text-align: center;
    border: 2px solid #000;
    padding: 1.5mm;
}
.ship-ticket__signs {
    display: flex;
    gap: 3mm;
    margin-top: 2mm;
}
.ship-ticket__signs > div {
    flex: 1;
    text-align: center;
    border: 1px solid #000;
    padding: 1.5mm;
}
.ship-ticket__sign-label {
    font-weight: 700;
    font-size: 0.9em;
}
.ship-ticket__sign-space {
    height: 12mm;
}
.ship-ticket__footer {
    margin-top: 2mm;
    text-align: center;
    font-size: 0.85em;
    border-top: 1px dashed #000;
    padding-top: 1.5mm;
}

/* A5/A4: 2 cột người gửi/nhận chỉ khi rộng */
.ship-root.paper-a5 .ship-ticket__parties-grid,
.ship-root.paper-a4 .ship-ticket__parties-grid {
    display: block;
}

/* ===== PRINT — đúng khổ mm, không scale/co ===== */
@media print {
    @page {
        margin: 2mm;
    }

    html, body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
        width: auto !important;
        height: auto !important;
        min-height: 0 !important;
        overflow: visible !important;
        zoom: 1 !important;
        transform: none !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Ẩn chrome admin */
    .sidebar,
    .topbar,
    .no-print,
    .admin-notify-wrap,
    .toast-confirm-overlay,
    #toast-container,
    .toastr,
    .ship-preview-label,
    .print-toolbar {
        display: none !important;
    }

    /* Bỏ khung layout admin (sidebar margin) — để phiếu full, không bị ép nhỏ */
    .main {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: none !important;
        min-height: 0 !important;
        overflow: visible !important;
    }
    .content {
        margin: 0 !important;
        padding: 0 !important;
        width: auto !important;
        max-width: none !important;
        overflow: visible !important;
    }
    .ship-preview-wrap {
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: visible !important;
        align-items: flex-start !important;
    }

    #ship-print-root {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        float: none !important;
        transform: none !important;
        zoom: 1 !important;
        /* KHÔNG width:100% — giữ mm cố định */
    }

    /* Khổ cố định theo mm (trừ lề in ~2–4mm) */
    .ship-root.paper-58mm {
        width: 54mm !important;
        max-width: 54mm !important;
        min-width: 54mm !important;
    }
    .ship-root.paper-80mm {
        width: 72mm !important;
        max-width: 72mm !important;
        min-width: 72mm !important;
    }
    .ship-root.paper-a5 {
        width: 138mm !important;
        max-width: 138mm !important;
        min-width: 0 !important;
    }
    .ship-root.paper-a4 {
        width: 194mm !important;
        max-width: 194mm !important;
        min-width: 0 !important;
    }

    .ship-ticket {
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        width: 100% !important;
        max-width: none !important;
        height: auto !important;
        overflow: visible !important;
        page-break-inside: auto;
        break-inside: auto;
        /* Không scale font khi in */
        font-size: inherit;
    }

    /* Từng khối tránh cắt giữa chừng; cả phiếu được dài nhiều trang nếu cần */
    .ship-ticket__block,
    .ship-ticket__header {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    /* Chữ đen 100% — máy nhiệt đọc rõ */
    .ship-ticket,
    .ship-ticket * {
        color: #000 !important;
        background: #fff !important;
        box-shadow: none !important;
    }
}
</style>
<script>
(function () {
    var root = document.getElementById('ship-print-root');
    var paper = root ? (root.getAttribute('data-paper') || '80mm') : '80mm';

    function applyPaperClass() {
        document.documentElement.classList.remove('paper-58mm', 'paper-80mm', 'paper-a5', 'paper-a4');
        document.body.classList.remove('paper-58mm', 'paper-80mm', 'paper-a5', 'paper-a4');
        document.documentElement.classList.add('paper-' + paper);
        document.body.classList.add('paper-' + paper);
    }
    applyPaperClass();

    // Inject @page size đúng khổ trước khi in (hỗ trợ Chrome/Edge)
    var style = document.createElement('style');
    style.setAttribute('data-print-page', '1');
    function pageCss() {
        if (paper === '58mm') {
            return '@page { size: 58mm auto; margin: 1.5mm; }';
        }
        if (paper === '80mm') {
            return '@page { size: 80mm auto; margin: 2mm; }';
        }
        if (paper === 'a5') {
            return '@page { size: A5 portrait; margin: 6mm; }';
        }
        return '@page { size: A4 portrait; margin: 8mm; }';
    }
    style.textContent = pageCss();
    document.head.appendChild(style);

    var btn = document.getElementById('btn_print');
    if (btn) {
        btn.addEventListener('click', function () {
            applyPaperClass();
            style.textContent = pageCss();
            // Nhắc: không dùng Fit to page
            window.print();
        });
    }

    window.addEventListener('beforeprint', function () {
        applyPaperClass();
        style.textContent = pageCss();
    });
})();
</script>
@endpush
