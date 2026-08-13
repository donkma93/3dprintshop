@extends('layouts.admin')
@section('title', 'Thêm banner')
@section('subtitle', 'Slider / banner quảng cáo trang chủ')
@section('content')
<div class="card p-4" style="max-width:820px">
    <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.banners._form')
        <button class="btn btn-dark" type="submit">Lưu</button>
        <a href="{{ route('admin.banners.index') }}" class="btn btn-outline-secondary">Hủy</a>
    </form>
</div>
@endsection
