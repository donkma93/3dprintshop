@extends('layouts.admin')

@section('title', 'Sửa thiết bị')
@section('subtitle', $equipment->name)

@section('content')
<div class="card p-4" style="max-width:820px">
    <form method="POST" action="{{ route('admin.equipment.update', $equipment) }}">
        @csrf @method('PUT')
        @include('admin.equipment._form')
        <div class="d-flex gap-2">
            <button class="btn btn-dark" type="submit">Cập nhật</button>
            <a href="{{ route('admin.equipment.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
