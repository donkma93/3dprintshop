@extends('layouts.admin')

@section('title', 'Sửa sản phẩm')
@section('subtitle', $product->name)

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.products.qr', $product) }}" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-qr-code"></i> Mã QR bán hàng
    </a>
    <a href="{{ route('admin.sales.scan', ['code' => $product->qr_token]) }}" class="btn btn-outline-success btn-sm">
        <i class="bi bi-bag-check"></i> Bán nhanh (quét)
    </a>
</div>
<div class="card p-4">
    <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.products._form')
        <div class="d-flex gap-2">
            <button class="btn btn-dark" type="submit">Cập nhật</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
