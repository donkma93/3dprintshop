@extends('layouts.admin')
@section('title', 'Sửa bài viết')
@section('subtitle', $post->title)
@section('content')
<div class="card p-4">
    <form method="POST" action="{{ route('admin.posts.update', $post) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.posts._form')
        <button class="btn btn-dark" type="submit">Cập nhật</button>
        <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">Hủy</a>
    </form>
</div>
@endsection
