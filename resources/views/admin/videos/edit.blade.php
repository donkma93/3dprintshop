@extends('layouts.admin')
@section('title', 'Sửa video / live')
@section('subtitle', $video->title)
@section('content')
<div class="card p-4" style="max-width:820px">
    <form method="POST" action="{{ route('admin.videos.update', $video) }}">
        @csrf
        @method('PUT')
        @include('admin.videos._form', ['video' => $video])
        <button class="btn btn-dark" type="submit">Cập nhật (lấy lại thumbnail)</button>
        <a href="{{ route('admin.videos.index') }}" class="btn btn-outline-secondary">Hủy</a>
    </form>
</div>
@endsection