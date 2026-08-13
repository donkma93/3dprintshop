@extends('layouts.admin')

@section('title', 'Tạo người dùng')
@section('subtitle', 'Thêm tài khoản quản trị mới')

@section('content')
<div class="card p-3">
    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        @include('admin.users._form')
        <div class="d-flex gap-2">
            <button class="btn btn-dark" type="submit">Tạo tài khoản</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
