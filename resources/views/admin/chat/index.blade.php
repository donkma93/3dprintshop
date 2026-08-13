@extends('layouts.admin')

@section('title', 'Chat khách hàng')
@section('subtitle', 'Nhiều khách chat song song — theo dõi nhân viên đang phụ trách')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="btn-group">
        <a href="{{ route('admin.chat.index', ['status' => 'open']) }}"
           class="btn btn-sm {{ $status === 'open' ? 'btn-dark' : 'btn-outline-secondary' }}">
            Đang mở @if($openCount)<span class="badge text-bg-warning ms-1" id="chatOpenCountBadge">{{ $openCount }}</span>@endif
        </a>
        <a href="{{ route('admin.chat.index', ['status' => 'closed']) }}"
           class="btn btn-sm {{ $status === 'closed' ? 'btn-dark' : 'btn-outline-secondary' }}">Đã đóng</a>
        <a href="{{ route('admin.chat.index', ['status' => 'all']) }}"
           class="btn btn-sm {{ $status === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}">Tất cả</a>
    </div>
    <div class="small text-secondary">
        <i class="bi bi-broadcast"></i> Tự cập nhật thông báo toastr khi có tin mới
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
            <tr>
                <th>Khách</th>
                <th>Liên hệ</th>
                <th>Tin cuối</th>
                <th>Nhân viên phụ trách</th>
                <th>Trạng thái</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($conversations as $c)
                @php
                    $unread = (int) ($c->unread_count ?? 0);
                    $last = $c->messages->first();
                    $staffName = $c->lastAdmin?->name;
                @endphp
                <tr class="{{ $unread ? 'table-warning' : '' }}">
                    <td>
                        <div class="fw-semibold">{{ $c->guest_name }}</div>
                        <div class="small text-secondary">#{{ $c->id }} · {{ optional($c->created_at)->format('d/m/Y H:i') }}</div>
                    </td>
                    <td class="small">
                        @if($c->guest_phone)<div><i class="bi bi-telephone"></i> {{ $c->guest_phone }}</div>@endif
                        @if($c->guest_email)<div><i class="bi bi-envelope"></i> {{ $c->guest_email }}</div>@endif
                    </td>
                    <td class="small" style="max-width:260px">
                        @if($last)
                            <div class="text-truncate">
                                @if($last->sender === 'admin')
                                    <span class="text-teal">NV:</span>
                                @elseif($last->sender === 'guest')
                                    <span class="text-secondary">Khách:</span>
                                @else
                                    <span class="text-secondary">Bot:</span>
                                @endif
                                {{ $last->body }}
                            </div>
                            <div class="text-secondary">{{ optional($c->last_message_at)->diffForHumans() }}</div>
                        @else
                            —
                        @endif
                    </td>
                    <td class="small">
                        @if($staffName)
                            <span class="badge rounded-pill text-bg-light border">
                                <i class="bi bi-person-badge text-primary"></i>
                                {{ $staffName }}
                            </span>
                            @if((int) $c->last_admin_user_id === (int) auth()->id())
                                <div class="text-success mt-1" style="font-size:.72rem">Bạn đang phụ trách</div>
                            @endif
                        @else
                            <span class="text-secondary">Chưa có NV trả lời</span>
                        @endif
                    </td>
                    <td>
                        @if($c->status === 'open')
                            <span class="badge badge-soft">Open</span>
                        @else
                            <span class="badge bg-secondary">Closed</span>
                        @endif
                        @if($unread)
                            <span class="badge text-bg-danger">{{ $unread }} mới</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.chat.show', $c) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-chat-dots"></i> Trả lời
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-secondary py-5">Chưa có cuộc trò chuyện nào.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($conversations->hasPages())
        <div class="card-body border-top">{{ $conversations->links() }}</div>
    @endif
</div>
@endsection
