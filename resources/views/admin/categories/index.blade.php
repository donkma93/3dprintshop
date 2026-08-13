@extends('layouts.admin')

@section('title', 'Danh mục sản phẩm')
@section('subtitle', 'Phân loại sản phẩm hiển thị trên web')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-secondary">Tổng {{ $categories->total() }} danh mục</div>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-dark btn-sm">+ Thêm danh mục</a>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
            <tr>
                <th>Tên</th>
                <th>Slug</th>
                <th>Mã SKU</th>
                <th>Sản phẩm</th>
                <th>Trạng thái</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $category->name }}</div>
                        <div class="small text-secondary">{{ Str::limit($category->description, 60) }}</div>
                    </td>
                    <td><code>{{ $category->slug }}</code></td>
                    <td>
                        @if($category->sku_prefix)
                            <code class="fw-semibold">{{ $category->sku_prefix }}</code>
                            <div class="small text-secondary">{{ $category->sku_prefix }}-0001…</div>
                        @else
                            <span class="text-secondary">—</span>
                        @endif
                    </td>
                    <td>{{ $category->products_count }}</td>
                    <td>
                        @if($category->is_active)
                            <span class="badge badge-soft">Hiển thị</span>
                        @else
                            <span class="badge text-bg-secondary">Ẩn</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline" data-confirm="Chuyển danh mục này vào thùng rác? Có thể khôi phục trong 30 ngày." data-confirm-title="Đưa vào thùng rác">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-secondary">Chưa có danh mục.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $categories->links() }}</div>
</div>
@endsection
