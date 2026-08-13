@extends('layouts.admin')

@section('title', 'Sửa danh mục')
@section('subtitle', $category->name)

@section('content')
<div class="card p-4" style="max-width:720px">
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.categories._form')
        <div class="d-flex gap-2">
            <button class="btn btn-dark" type="submit">Cập nhật</button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
