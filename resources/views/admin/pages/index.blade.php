@extends('layouts.admin')
@section('title', 'Trang tĩnh')
@section('subtitle', 'Giới thiệu, liên hệ, chính sách...')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-secondary">Tổng {{ $pages->total() }} trang</div>
    <a href="{{ route('admin.pages.create') }}" class="btn btn-dark btn-sm">+ Thêm trang</a>
</div>
<div class="card p-3">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
            <tr>
                <th>Tiêu đề</th>
                <th>Slug</th>
                <th>Menu</th>
                <th>TT</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse($pages as $page)
                <tr>
                    <td class="fw-semibold">{{ $page->title }}</td>
                    <td><code>{{ $page->slug }}</code></td>
                    <td>{{ $page->show_in_menu ? 'Có' : 'Không' }}</td>
                    <td>
                        @if($page->is_published)
                            <span class="badge badge-soft">Hiện</span>
                        @else
                            <span class="badge text-bg-secondary">Ẩn</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" class="d-inline"
                              data-confirm="Chuyển trang vào thùng rác?" data-confirm-title="Đưa vào thùng rác">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-secondary">Chưa có trang.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $pages->links() }}</div>
</div>
@endsection
