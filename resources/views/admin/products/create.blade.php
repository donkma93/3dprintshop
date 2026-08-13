@extends('layouts.admin')

@section('title', 'Đăng sản phẩm')
@section('subtitle', 'Thêm sản phẩm in 3D lên cửa hàng')

@section('content')
<div class="card p-4">
    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.products._form')
        <div class="d-flex gap-2">
            <button class="btn btn-dark" type="submit">Đăng sản phẩm</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
