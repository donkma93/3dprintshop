@extends('layouts.admin')
@section('title', 'Thêm video / live')
@section('subtitle', 'Dán link YouTube · TikTok · Facebook (online) — thumbnail tự lấy')
@section('content')
<div class="card p-4" style="max-width:820px">
    <form method="POST" action="{{ route('admin.videos.store') }}">
        @csrf
        @include('admin.videos._form')
        <button class="btn btn-dark" type="submit">Lưu link</button>
        <a href="{{ route('admin.videos.index') }}" class="btn btn-outline-secondary">Hủy</a>
    </form>
</div>
@endsection
