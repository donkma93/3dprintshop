@extends('layouts.admin')
@section('title', 'Thêm trang')
@section('subtitle', 'Tạo trang tĩnh chuẩn SEO')
@section('content')
<div class="card p-4">
    <form method="POST" action="{{ route('admin.pages.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.pages._form')
        <button class="btn btn-dark" type="submit">Lưu</button>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">Hủy</a>
    </form>
</div>
@endsection
