@extends('layouts.admin')
@section('title', 'Sửa banner')
@section('subtitle', $banner->title)
@section('content')
<div class="card p-4" style="max-width:820px">
    <form method="POST" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.banners._form')
        <button class="btn btn-dark" type="submit">Cập nhật</button>
        <a href="{{ route('admin.banners.index') }}" class="btn btn-outline-secondary">Hủy</a>
    </form>
</div>
@endsection
