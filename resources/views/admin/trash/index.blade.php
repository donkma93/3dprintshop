@extends('layouts.admin')

@section('title', 'Thùng rác')
@section('subtitle', 'Mục đã xóa — tự xóa vĩnh viễn sau '.$retentionDays.' ngày')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.trash.index') }}"
           class="btn btn-sm {{ $filter === '' ? 'btn-dark' : 'btn-outline-secondary' }}">
            Tất cả ({{ $total }})
        </a>
        @foreach($labels as $type => $label)
            <a href="{{ route('admin.trash.index', ['type' => $type]) }}"
               class="btn btn-sm {{ $filter === $type ? 'btn-dark' : 'btn-outline-secondary' }}">
                {{ $label }} ({{ $counts[$type] ?? 0 }})
            </a>
        @endforeach
    </div>

    @if($total > 0)
        <form action="{{ route('admin.trash.empty') }}" method="POST"
              data-confirm="Xóa vĩnh viễn {{ $filter ? ($labels[$filter] ?? '') : 'toàn bộ' }} mục trong thùng rác? Không thể hoàn tác."
              data-confirm-title="Dọn thùng rác">
            @csrf
            @method('DELETE')
            @if($filter)
                <input type="hidden" name="type" value="{{ $filter }}">
            @endif
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash"></i> Dọn {{ $filter ? 'nhóm này' : 'toàn bộ' }}
            </button>
        </form>
    @endif
</div>

<div class="alert alert-light border mb-3">
    <i class="bi bi-info-circle text-primary"></i>
    Các mục xóa sẽ nằm trong thùng rác <strong>{{ $retentionDays }} ngày</strong> rồi bị xóa vĩnh viễn tự động.
    Bạn có thể <strong>khôi phục</strong> hoặc <strong>xóa ngay</strong> bất cứ lúc nào.
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
            <tr>
                <th>Loại</th>
                <th>Tên / Mô tả</th>
                <th>Ngày xóa</th>
                <th>Xóa vĩnh viễn sau</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td><span class="badge text-bg-secondary">{{ $item['label'] }}</span></td>
                    <td>
                        <div class="fw-semibold">{{ $item['name'] }}</div>
                        @if($item['meta'])
                            <div class="small text-secondary">{{ $item['meta'] }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $item['deleted_at']?->format('d/m/Y H:i') }}
                    </td>
                    <td>
                        @if($item['days_left'] <= 0)
                            <span class="badge badge-warn">Sắp / đã đến hạn</span>
                        @elseif($item['days_left'] <= 7)
                            <span class="badge badge-warn">Còn {{ $item['days_left'] }} ngày</span>
                        @else
                            <span class="badge badge-soft">Còn {{ $item['days_left'] }} ngày</span>
                        @endif
                        <div class="small text-secondary">
                            {{ $item['purge_at']?->format('d/m/Y') }}
                        </div>
                    </td>
                    <td class="text-end">
                        <form action="{{ route('admin.trash.restore', [$item['type'], $item['id']]) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-arrow-counterclockwise"></i> Khôi phục
                            </button>
                        </form>
                        <form action="{{ route('admin.trash.force-delete', [$item['type'], $item['id']]) }}" method="POST" class="d-inline"
                              data-confirm="Xóa vĩnh viễn «{{ $item['name'] }}»? Không thể hoàn tác."
                              data-confirm-title="Xóa vĩnh viễn">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x-circle"></i> Xóa ngay
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-secondary text-center py-4">
                        <i class="bi bi-trash3 fs-3 d-block mb-2 opacity-50"></i>
                        Thùng rác trống.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
