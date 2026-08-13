@extends('layouts.admin')

@section('title', 'Thêm nguyên liệu')
@section('subtitle', 'Khai báo loại nhựa / resin')

@section('content')
<div class="card p-4" style="max-width:820px">
    <form method="POST" action="{{ route('admin.materials.store') }}">
        @csrf
        @include('admin.materials._form')
        <div class="d-flex gap-2">
            <button class="btn btn-dark" type="submit">Lưu</button>
            <a href="{{ route('admin.materials.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
