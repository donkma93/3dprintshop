@extends('layouts.admin')

@section('title', 'Thiết bị đã mua')
@section('subtitle', 'Máy in 3D và thiết bị phụ trợ')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form class="d-flex gap-2" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm tên, hãng, model...">
        <button class="btn btn-sm btn-outline-dark">Tìm</button>
    </form>
    <a href="{{ route('admin.equipment.create') }}" class="btn btn-dark btn-sm">+ Thêm thiết bị</a>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
            <tr>
                <th>Thiết bị</th>
                <th>Loại</th>
                <th>Ngày mua</th>
                @if(auth()->user()->canViewRevenue())
                <th>Giá mua</th>
                @endif
                <th>Trạng thái</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse($equipment as $item)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $item->name }}</div>
                        <div class="small text-secondary">{{ $item->brand }} {{ $item->model }} · SN: {{ $item->serial_number ?: '—' }}</div>
                    </td>
                    <td>{{ $item->type ?: '—' }}</td>
                    <td>{{ optional($item->purchase_date)->format('d/m/Y') ?: '—' }}</td>
                    @if(auth()->user()->canViewRevenue())
                    <td class="fw-semibold">{{ number_format($item->purchase_price, 0, ',', '.') }} đ</td>
                    @endif
                    <td>
                        @if($item->status === 'active')
                            <span class="badge badge-soft">{{ $item->status_label }}</span>
                        @elseif($item->status === 'maintenance')
                            <span class="badge badge-warn">{{ $item->status_label }}</span>
                        @else
                            <span class="badge text-bg-secondary">{{ $item->status_label }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.equipment.edit', $item) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('admin.equipment.destroy', $item) }}" method="POST" class="d-inline" data-confirm="Chuyển thiết bị này vào thùng rác? Có thể khôi phục trong 30 ngày." data-confirm-title="Đưa vào thùng rác">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ auth()->user()->canViewRevenue() ? 6 : 5 }}" class="text-secondary">Chưa có thiết bị.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $equipment->links() }}</div>
</div>
@endsection
