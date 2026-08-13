@extends('layouts.admin')

@section('title', 'Thêm thiết bị')
@section('subtitle', 'Ghi nhận máy in / thiết bị đã mua sắm')

@section('content')
<div class="card p-4" style="max-width:820px">
    <form method="POST" action="{{ route('admin.equipment.store') }}">
        @csrf
        @include('admin.equipment._form')
        <div class="d-flex gap-2">
            <button class="btn btn-dark" type="submit">Lưu</button>
            <a href="{{ route('admin.equipment.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
