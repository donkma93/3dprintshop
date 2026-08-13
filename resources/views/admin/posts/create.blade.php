@extends('layouts.admin')
@section('title', 'Viết bài')
@section('subtitle', 'Đăng bài tin tức chuẩn SEO')
@section('content')
<div class="card p-4">
    <form method="POST" action="{{ route('admin.posts.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.posts._form')
        <button class="btn btn-dark" type="submit">Đăng bài</button>
        <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">Hủy</a>
    </form>
</div>
@endsection
