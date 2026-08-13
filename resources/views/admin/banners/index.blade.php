@extends('layouts.admin')

@section('title', 'Banner / Slider')
@section('subtitle', 'Hình ảnh trang chủ do admin quản lý')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-secondary">Tổng {{ $banners->total() }} banner</div>
    <a href="{{ route('admin.banners.create') }}" class="btn btn-dark btn-sm">+ Thêm banner</a>
</div>
<div class="card p-3">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
            <tr>
                <th>Ảnh</th>
                <th>Tiêu đề</th>
                <th>Vị trí</th>
                <th>Thứ tự</th>
                <th>TT</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse($banners as $banner)
                <tr>
                    <td><img src="{{ $banner->image_url }}" width="96" height="48" class="rounded" style="object-fit:cover" alt=""></td>
                    <td>
                        <div class="fw-semibold">{{ $banner->title }}</div>
                        <div class="small text-secondary">{{ $banner->subtitle }}</div>
                    </td>
                    <td>{{ \App\Models\Banner::positionOptions()[$banner->position] ?? $banner->position }}</td>
                    <td>{{ $banner->sort_order }}</td>
                    <td>
                        @if($banner->is_active)
                            <span class="badge badge-soft">Hiện</span>
                        @else
                            <span class="badge text-bg-secondary">Ẩn</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" class="d-inline"
                              data-confirm="Chuyển banner vào thùng rác? Có thể khôi phục trong 30 ngày." data-confirm-title="Đưa vào thùng rác">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-secondary">Chưa có banner.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $banners->links() }}</div>
</div>
@endsection
