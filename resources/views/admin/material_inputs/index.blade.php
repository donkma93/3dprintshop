@extends('layouts.admin')

@section('title', 'Nhập nguyên liệu')
@section('subtitle', 'Theo dõi đầu vào nhựa / resin và giá thành')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-secondary">Lịch sử phiếu nhập</div>
    <a href="{{ route('admin.material-inputs.create') }}" class="btn btn-dark btn-sm">+ Tạo phiếu nhập</a>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
            <tr>
                <th>Ngày</th>
                <th>Nguyên liệu</th>
                <th>SL</th>
                @if(auth()->user()->canViewRevenue())
                <th>Đơn giá</th>
                <th>Thành tiền</th>
                @endif
                <th>NCC / HĐ</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse($inputs as $input)
                <tr>
                    <td>{{ $input->input_date->format('d/m/Y') }}</td>
                    <td>
                        <div class="fw-semibold">{{ $input->material?->name }}</div>
                        <div class="small text-secondary">{{ $input->material?->type }}</div>
                    </td>
                    <td>{{ $input->quantity }} {{ $input->material?->unit }}</td>
                    @if(auth()->user()->canViewRevenue())
                    <td>{{ number_format($input->unit_price, 0, ',', '.') }} đ</td>
                    <td class="fw-semibold">{{ number_format($input->total_price, 0, ',', '.') }} đ</td>
                    @endif
                    <td>
                        <div>{{ $input->supplier ?: '—' }}</div>
                        <div class="small text-secondary">{{ $input->invoice_number }}</div>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.material-inputs.edit', $input) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('admin.material-inputs.destroy', $input) }}" method="POST" class="d-inline" data-confirm="Chuyển phiếu nhập vào thùng rác? Tồn kho sẽ bị trừ; khôi phục sẽ cộng lại. Giữ 30 ngày." data-confirm-title="Đưa vào thùng rác">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ auth()->user()->canViewRevenue() ? 7 : 5 }}" class="text-secondary">Chưa có phiếu nhập.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $inputs->links() }}</div>
</div>
@endsection
