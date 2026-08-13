@extends('layouts.admin')
@section('title', 'Sửa trang')
@section('subtitle', $page->title)
@section('content')
<div class="card p-4">
    <form method="POST" action="{{ route('admin.pages.update', $page) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.pages._form')
        <button class="btn btn-dark" type="submit">Cập nhật</button>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">Hủy</a>
    </form>
</div>
@endsection
