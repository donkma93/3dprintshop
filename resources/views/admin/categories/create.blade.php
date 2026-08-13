@extends('layouts.admin')

@section('title', 'Thêm danh mục')
@section('subtitle', 'Tạo danh mục mới cho sản phẩm in 3D')

@section('content')
<div class="card p-4" style="max-width:720px">
    <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.categories._form')
        <div class="d-flex gap-2">
            <button class="btn btn-dark" type="submit">Lưu danh mục</button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
