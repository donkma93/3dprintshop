@extends('layouts.admin')

@section('title', 'Sửa người dùng')
@section('subtitle', $user->name)

@section('content')
<div class="card p-3">
    <form action="{{ route('admin.users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.users._form', ['user' => $user])
        <div class="d-flex gap-2">
            <button class="btn btn-dark" type="submit">Lưu thay đổi</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
