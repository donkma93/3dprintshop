@extends('layouts.admin')

@section('title', 'Tạo phiếu nhập')
@section('subtitle', 'Nhập nguyên liệu nhựa / resin vào kho')

@section('content')
<div class="card p-4" style="max-width:820px">
    <form method="POST" action="{{ route('admin.material-inputs.store') }}">
        @csrf
        @include('admin.material_inputs._form')
        <div class="d-flex gap-2">
            <button class="btn btn-dark" type="submit">Lưu phiếu nhập</button>
            <a href="{{ route('admin.material-inputs.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
