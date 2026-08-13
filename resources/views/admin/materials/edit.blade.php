@extends('layouts.admin')

@section('title', 'Sửa nguyên liệu')
@section('subtitle', $material->name)

@section('content')
<div class="card p-4" style="max-width:820px">
    <form method="POST" action="{{ route('admin.materials.update', $material) }}">
        @csrf @method('PUT')
        @include('admin.materials._form')
        <div class="d-flex gap-2">
            <button class="btn btn-dark" type="submit">Cập nhật</button>
            <a href="{{ route('admin.materials.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
