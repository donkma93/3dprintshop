@extends('layouts.admin')

@section('title', 'Sửa phiếu nhập')
@section('subtitle', 'Điều chỉnh phiếu nhập và tồn kho')

@section('content')
<div class="card p-4" style="max-width:820px">
    <form method="POST" action="{{ route('admin.material-inputs.update', $materialInput) }}">
        @csrf @method('PUT')
        @include('admin.material_inputs._form')
        <div class="d-flex gap-2">
            <button class="btn btn-dark" type="submit">Cập nhật</button>
            <a href="{{ route('admin.material-inputs.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
