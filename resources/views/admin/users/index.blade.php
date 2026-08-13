@extends('layouts.admin')

@section('title', 'Người dùng quản trị')
@section('subtitle', 'Tạo tài khoản và phân quyền theo vai trò')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="small text-secondary">
        <strong>Quản trị viên</strong> có toàn quyền (kể cả doanh thu). Các vai trò khác bị hạn chế theo lộ trình phân quyền.
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-dark btn-sm">+ Tạo người dùng</a>
</div>

<div class="card p-3 mb-3">
    <div class="row g-2 small">
        <div class="col-md-3">
            <div class="border rounded-3 p-2 h-100">
                <div class="fw-semibold text-danger">Quản trị viên</div>
                <div class="text-secondary">Toàn quyền · Xem doanh thu/doanh số · Quản lý user</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-2 h-100">
                <div class="fw-semibold">Quản lý</div>
                <div class="text-secondary">Kho, SP, nội dung, chat, thùng rác · Không xem doanh thu</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-2 h-100">
                <div class="fw-semibold">Nhân viên</div>
                <div class="text-secondary">SP, danh mục, NL, nhập kho, thiết bị, chat</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-2 h-100">
                <div class="fw-semibold">Biên tập</div>
                <div class="text-secondary">Banner, bài viết, trang tĩnh, chat</div>
            </div>
        </div>
    </div>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
            <tr>
                <th>Họ tên</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $user->name }}</div>
                        @if($user->id === auth()->id())
                            <span class="badge badge-soft">Bạn</span>
                        @endif
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if($user->isSuperAdmin())
                            <span class="badge text-bg-danger">{{ $user->roleLabel() }}</span>
                        @else
                            <span class="badge text-bg-secondary">{{ $user->roleLabel() }}</span>
                        @endif
                    </td>
                    <td>
                        @if($user->is_active)
                            <span class="badge badge-soft">Hoạt động</span>
                        @else
                            <span class="badge text-bg-secondary">Tạm khóa</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline"
                                  data-confirm="Xóa tài khoản {{ $user->name }}? Hành động không hoàn tác."
                                  data-confirm-title="Xóa người dùng">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-secondary">Chưa có người dùng.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $users->links() }}</div>
</div>
@endsection
