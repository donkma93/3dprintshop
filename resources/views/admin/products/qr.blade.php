@extends('layouts.admin')

@section('title', 'Mã QR sản phẩm')
@section('subtitle', $product->name)

@section('content')
<div class="no-print mb-3 d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-dark" onclick="window.print()">
        <i class="bi bi-printer"></i> In tem
    </button>
    <a href="{{ route('admin.products.qr.download', $product) }}" class="btn btn-outline-dark">
        <i class="bi bi-download"></i> Tải tem PNG
    </a>
    <a href="{{ route('admin.products.qr.download', $product) }}?raw=1" class="btn btn-outline-secondary btn-sm">
        Chỉ QR
    </a>
    <form method="POST" action="{{ route('admin.products.qr.regenerate', $product) }}" class="d-inline">
        @csrf
        <button class="btn btn-outline-secondary btn-sm" type="submit"
                data-confirm="Tạo lại ảnh QR? Token giữ nguyên — tem cũ vẫn quét được."
                data-confirm-title="Tạo lại QR">
            Tạo lại QR
        </button>
    </form>
    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-link btn-sm">← Sửa sản phẩm</a>
    <a href="{{ route('admin.sales.scan', ['code' => $product->qr_token]) }}" class="btn btn-link btn-sm">Bán nhanh</a>
</div>

{{-- Tem in: CHỈ QR + mã SP + giá --}}
<div class="qr-label-sheet">
    <div class="qr-label">
        <img src="{{ $qrUrl }}" alt="QR {{ $product->sku }}" class="qr-label__img">
        <div class="qr-label__sku">{{ $product->sku ?: $product->qr_token }}</div>
        <div class="qr-label__price">{{ number_format($product->final_price, 0, ',', '.') }} đ</div>
    </div>
</div>
@endsection

@push('scripts')
<style>
/* Màn hình: xem trước tem */
.qr-label-sheet {
    display: flex;
    justify-content: center;
    padding: 1rem 0 2rem;
}
.qr-label {
    width: 56mm;
    max-width: 100%;
    padding: 10mm 6mm 8mm;
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    background: #fff;
    text-align: center;
    box-sizing: border-box;
}
.qr-label__img {
    width: 38mm;
    height: 38mm;
    object-fit: contain;
    display: block;
    margin: 0 auto 4mm;
}
.qr-label__sku {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 12pt;
    font-weight: 700;
    letter-spacing: .02em;
    color: #0f172a;
    line-height: 1.2;
    word-break: break-all;
}
.qr-label__price {
    margin-top: 2mm;
    font-size: 13pt;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

/* In: chỉ tem — ẩn toàn bộ admin UI */
@media print {
    @page {
        margin: 6mm;
        size: auto;
    }
    html, body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .sidebar,
    .topbar,
    .no-print,
    .admin-notify-wrap,
    .toast-confirm-overlay,
    #toast-container,
    .toastr {
        display: none !important;
        visibility: hidden !important;
    }
    .main {
        margin: 0 !important;
        width: 100% !important;
    }
    .content {
        padding: 0 !important;
    }
    .qr-label-sheet {
        padding: 0 !important;
        display: block !important;
    }
    .qr-label {
        border: none !important;
        border-radius: 0 !important;
        margin: 0 auto;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
}
</style>
@endpush
