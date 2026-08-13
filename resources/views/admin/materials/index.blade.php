@extends('layouts.admin')

@section('title', 'Nguyên liệu')
@section('subtitle', 'Khai báo loại nhựa / vật liệu in 3D')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form class="d-flex gap-2" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm tên, loại, hãng...">
        <button class="btn btn-sm btn-outline-dark">Tìm</button>
    </form>
    <a href="{{ route('admin.materials.create') }}" class="btn btn-dark btn-sm">+ Thêm nguyên liệu</a>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
            <tr>
                <th>Nguyên liệu</th>
                <th>Loại</th>
                <th>Tồn kho</th>
                @if(auth()->user()->canViewRevenue())
                <th>Đơn giá</th>
                <th>Giá trị tồn</th>
                @endif
                <th>TT</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse($materials as $material)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $material->name }}</div>
                        <div class="small text-secondary">{{ $material->brand }} · {{ $material->color }}</div>
                    </td>
                    <td>{{ $material->type ?: '—' }}</td>
                    <td>
                        @if($material->isLowStock())
                            <span class="badge badge-warn">{{ $material->stock_quantity }} {{ $material->unit }}</span>
                        @else
                            {{ $material->stock_quantity }} {{ $material->unit }}
                        @endif
                    </td>
                    @if(auth()->user()->canViewRevenue())
                    <td>{{ number_format($material->unit_price, 0, ',', '.') }} đ</td>
                    <td class="fw-semibold">{{ number_format($material->stock_value, 0, ',', '.') }} đ</td>
                    @endif
                    <td>
                        @if($material->is_active)
                            <span class="badge badge-soft">Dùng</span>
                        @else
                            <span class="badge text-bg-secondary">Ngưng</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.materials.edit', $material) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('admin.materials.destroy', $material) }}" method="POST" class="d-inline" data-confirm="Chuyển nguyên liệu này vào thùng rác? Có thể khôi phục trong 30 ngày." data-confirm-title="Đưa vào thùng rác">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ auth()->user()->canViewRevenue() ? 7 : 5 }}" class="text-secondary">Chưa có nguyên liệu.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $materials->links() }}</div>
</div>
@endsection
