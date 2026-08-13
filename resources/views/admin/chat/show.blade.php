@extends('layouts.admin')

@section('title', 'Chat với '.$conversation->guest_name)
@section('subtitle', 'Hội thoại #'.$conversation->id)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a href="{{ route('admin.chat.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Danh sách
    </a>
    <div class="d-flex gap-2">
        @if($conversation->status === 'open')
            <form action="{{ route('admin.chat.close', $conversation) }}" method="POST">
                @csrf
                <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Đóng hội thoại này?')">Đóng chat</button>
            </form>
        @else
            <form action="{{ route('admin.chat.reopen', $conversation) }}" method="POST">
                @csrf
                <button class="btn btn-sm btn-outline-success" type="submit">Mở lại</button>
            </form>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="fw-semibold">{{ $conversation->guest_name }}</div>
                    <div class="small text-secondary">
                        @if($conversation->status === 'open')
                            <span class="badge badge-soft">Đang mở</span>
                        @else
                            <span class="badge bg-secondary">Đã đóng</span>
                        @endif
                        <span id="adminGuestTypingHint" class="ms-2 text-primary d-none">
                            <span class="admin-typing-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                            Khách đang nhập…
                        </span>
                    </div>
                </div>
                <div class="text-end">
                    <div class="small text-secondary">Tin nhắn & typing tự làm mới ~2s</div>
                    <div id="adminStaffBanner" class="admin-staff-banner {{ $conversation->lastAdmin ? '' : 'd-none' }}">
                        <i class="bi bi-person-badge"></i>
                        <span id="adminStaffBannerText">
                            @if($conversation->lastAdmin)
                                @if((int) $conversation->last_admin_user_id === (int) auth()->id())
                                    Bạn đang phụ trách hội thoại này
                                @else
                                    NV {{ $conversation->lastAdmin->name }} đang phụ trách
                                @endif
                            @endif
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="adminChatLog" class="admin-chat-log px-3 py-3">
                    @foreach($conversation->messages as $m)
                        @php
                            if ($m->sender === 'guest') {
                                $label = 'Khách';
                            } elseif ($m->sender === 'bot') {
                                $label = 'Trợ lý ảo';
                            } elseif ((int) $m->admin_user_id === (int) auth()->id()) {
                                $label = 'Bạn';
                            } else {
                                $label = $m->admin?->name ? 'NV: '.$m->admin->name : 'Nhân viên';
                            }
                        @endphp
                        <div class="admin-msg admin-msg--{{ $m->sender }} {{ $m->sender === 'admin' && (int) $m->admin_user_id !== (int) auth()->id() ? 'admin-msg--other-staff' : '' }}" data-id="{{ $m->id }}">
                            <div class="admin-msg__meta">
                                {{ $label }}
                                · {{ $m->created_at?->format('H:i d/m') }}
                            </div>
                            <div class="admin-msg__body">{!! nl2br(e($m->body)) !!}</div>
                        </div>
                    @endforeach
                </div>
                <div id="adminGuestTypingBar" class="admin-typing-bar d-none" aria-live="polite">
                    <span class="admin-typing-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                    <span>{{ $conversation->guest_name }} đang nhập tin nhắn…</span>
                </div>
            </div>
            <div class="card-footer bg-white">
                <form id="adminReplyForm" action="{{ route('admin.chat.reply', $conversation) }}" method="POST" class="d-flex gap-2">
                    @csrf
                    <textarea name="message" id="adminReplyInput" class="form-control" rows="2" placeholder="Nhập nội dung trả lời..." required {{ $conversation->status === 'closed' ? 'disabled' : '' }}></textarea>
                    <button class="btn btn-primary px-4" type="submit" {{ $conversation->status === 'closed' ? 'disabled' : '' }}>Gửi</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <h3 class="h6 fw-bold text-uppercase text-secondary">Thông tin liên hệ</h3>
                <div class="mb-2"><span class="text-secondary">Họ tên:</span> <strong>{{ $conversation->guest_name }}</strong></div>
                <div class="mb-2">
                    <span class="text-secondary">SĐT:</span>
                    @if($conversation->guest_phone)
                        <a href="tel:{{ preg_replace('/\s+/', '', $conversation->guest_phone) }}">{{ $conversation->guest_phone }}</a>
                    @else
                        —
                    @endif
                </div>
                <div class="mb-2">
                    <span class="text-secondary">Email:</span>
                    @if($conversation->guest_email)
                        <a href="mailto:{{ $conversation->guest_email }}">{{ $conversation->guest_email }}</a>
                    @else
                        —
                    @endif
                </div>
                <div class="small text-secondary mt-3">
                    IP: {{ $conversation->ip_address ?? '—' }}<br>
                    Bắt đầu: {{ $conversation->created_at?->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <h3 class="h6 fw-bold text-uppercase text-secondary">Nhân viên</h3>
                <div class="mb-2">
                    <span class="text-secondary">Phụ trách:</span>
                    <strong id="adminStaffSideName">{{ $conversation->lastAdmin?->name ?? 'Chưa có' }}</strong>
                </div>
                <div class="mb-0 small" id="adminStaffSideHint">
                    @if($conversation->lastAdmin && (int) $conversation->last_admin_user_id === (int) auth()->id())
                        <span class="text-success">Bạn là người trả lời gần nhất.</span>
                    @elseif($conversation->lastAdmin)
                        <span class="text-warning">Đồng nghiệp đang / đã chat — tránh trả lời trùng.</span>
                    @else
                        <span class="text-secondary">Chưa có nhân viên nào gửi tin trong hội thoại này.</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body small text-secondary">
                Khách để lại thông tin trên website qua form chat. Bạn trả lời tại đây; tin nhắn hiện ngay trên widget của khách (poll vài giây). Mỗi tin admin hiển thị tên nhân viên gửi.
            </div>
        </div>
    </div>
</div>

<style>
    .admin-chat-log {
        height: min(62vh, 560px);
        overflow-y: auto;
        background: #f8fafc;
    }
    .admin-msg {
        max-width: 85%;
        margin-bottom: .85rem;
        clear: both;
    }
    .admin-msg--guest { float: left; }
    .admin-msg--admin, .admin-msg--bot { float: right; text-align: right; }
    .admin-msg__meta { font-size: .72rem; color: #94a3b8; margin-bottom: .2rem; }
    .admin-msg__body {
        display: inline-block;
        text-align: left;
        padding: .65rem .85rem;
        border-radius: 12px;
        background: #fff;
        border: 1px solid #e2e8f0;
        white-space: pre-wrap;
        word-break: break-word;
        font-size: .92rem;
    }
    .admin-msg--admin .admin-msg__body {
        background: #0f766e;
        color: #fff;
        border-color: #0f766e;
    }
    .admin-msg--other-staff .admin-msg__body {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #fff;
    }
    .admin-msg--bot .admin-msg__body {
        background: #ecfdf5;
        border-color: #99f6e4;
        color: #134e4a;
    }
    .admin-staff-banner {
        margin-top: .25rem;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .78rem;
        color: #0f766e;
        background: #f0fdfa;
        border: 1px solid #99f6e4;
        border-radius: 999px;
        padding: .2rem .65rem;
    }
    .admin-staff-banner.is-other {
        color: #1d4ed8;
        background: #eff6ff;
        border-color: #bfdbfe;
    }
    .admin-chat-log::after { content: ""; display: table; clear: both; }
    .admin-typing-bar {
        display: flex;
        align-items: center;
        gap: .45rem;
        padding: .45rem 1rem .7rem;
        font-size: .82rem;
        color: #0f766e;
        background: #f0fdfa;
        border-top: 1px solid #ccfbf1;
    }
    .admin-typing-dots {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        vertical-align: middle;
    }
    .admin-typing-dots i {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: currentColor;
        display: inline-block;
        opacity: .45;
        animation: adminTypingDot 1.2s infinite ease-in-out;
    }
    .admin-typing-dots i:nth-child(2) { animation-delay: .15s; }
    .admin-typing-dots i:nth-child(3) { animation-delay: .3s; }
    @keyframes adminTypingDot {
        0%, 80%, 100% { opacity: .35; transform: translateY(0); }
        40% { opacity: 1; transform: translateY(-2px); }
    }
</style>

<script>
(function () {
    var log = document.getElementById('adminChatLog');
    var form = document.getElementById('adminReplyForm');
    var input = document.getElementById('adminReplyInput');
    var typingBar = document.getElementById('adminGuestTypingBar');
    var typingHint = document.getElementById('adminGuestTypingHint');
    var staffBanner = document.getElementById('adminStaffBanner');
    var staffBannerText = document.getElementById('adminStaffBannerText');
    var staffSideName = document.getElementById('adminStaffSideName');
    var staffSideHint = document.getElementById('adminStaffSideHint');
    var pollUrl = @json(route('admin.chat.poll', $conversation));
    var typingUrl = @json(route('admin.chat.typing', $conversation));
    var lastId = {{ $conversation->messages->max('id') ?? 0 }};
    var currentUserId = {{ (int) auth()->id() }};
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || form.querySelector('input[name="_token"]')?.value;
    var localTyping = false;
    var typingSendTimer = null;
    var typingIdleTimer = null;
    var conversationOpen = @json($conversation->status === 'open');

    function scrollBottom() {
        log.scrollTop = log.scrollHeight;
    }
    scrollBottom();

    function setGuestTyping(on) {
        if (typingBar) typingBar.classList.toggle('d-none', !on);
        if (typingHint) typingHint.classList.toggle('d-none', !on);
        if (on) scrollBottom();
    }

    function notifyTyping(isTyping) {
        if (!conversationOpen) return;
        if (isTyping === localTyping && isTyping) {
            // refresh handled by throttle below
        } else if (isTyping === localTyping) {
            return;
        }
        localTyping = !!isTyping;
        fetch(typingUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify({ typing: localTyping })
        }).catch(function () {});
    }

    function onLocalTypingActivity() {
        if (!conversationOpen || !input || input.disabled) return;
        if (!localTyping) {
            notifyTyping(true);
        } else if (!typingSendTimer) {
            typingSendTimer = setTimeout(function () {
                typingSendTimer = null;
                if (localTyping) {
                    localTyping = false;
                    notifyTyping(true);
                }
            }, 2000);
        }
        clearTimeout(typingIdleTimer);
        typingIdleTimer = setTimeout(function () {
            notifyTyping(false);
        }, 1800);
    }

    function applyStaffInfo(staff) {
        if (!staff) return;
        var name = staff.last_admin_name || null;
        var isMe = !!staff.is_last_admin_me;
        if (staffSideName) staffSideName.textContent = name || 'Chưa có';
        if (staffBanner && staffBannerText) {
            if (name) {
                staffBanner.classList.remove('d-none');
                if (isMe) {
                    staffBanner.classList.remove('is-other');
                    staffBannerText.textContent = 'Bạn đang phụ trách hội thoại này';
                } else {
                    staffBanner.classList.add('is-other');
                    staffBannerText.textContent = 'NV ' + name + ' đang phụ trách';
                }
            } else {
                staffBanner.classList.add('d-none');
            }
        }
        if (staffSideHint) {
            if (name && isMe) {
                staffSideHint.innerHTML = '<span class="text-success">Bạn là người trả lời gần nhất.</span>';
            } else if (name) {
                staffSideHint.innerHTML = '<span class="text-warning">Đồng nghiệp đang / đã chat — tránh trả lời trùng.</span>';
            } else {
                staffSideHint.innerHTML = '<span class="text-secondary">Chưa có nhân viên nào gửi tin trong hội thoại này.</span>';
            }
        }
        if (staff.typing_admin_name && !staff.is_typing_admin_me && typingHint) {
            // optional: could show peer admin typing; keep guest typing primary
        }
    }

    function appendMsg(m) {
        if (document.querySelector('.admin-msg[data-id="' + m.id + '"]')) return;
        var wrap = document.createElement('div');
        wrap.className = 'admin-msg admin-msg--' + m.sender;
        if (m.sender === 'admin' && m.admin_user_id && Number(m.admin_user_id) !== currentUserId) {
            wrap.classList.add('admin-msg--other-staff');
        }
        wrap.setAttribute('data-id', m.id);
        var who = m.sender_label
            || (m.sender === 'guest' ? 'Khách'
                : (m.sender === 'admin'
                    ? (Number(m.admin_user_id) === currentUserId ? 'Bạn' : (m.admin_name ? ('NV: ' + m.admin_name) : 'Nhân viên'))
                    : 'Trợ lý ảo'));
        wrap.innerHTML =
            '<div class="admin-msg__meta">' + who + ' · ' + (m.created_at || '') + '</div>' +
            '<div class="admin-msg__body"></div>';
        wrap.querySelector('.admin-msg__body').textContent = m.body;
        log.appendChild(wrap);
        lastId = Math.max(lastId, m.id);
        if (m.sender === 'guest') setGuestTyping(false);
        scrollBottom();
    }

    input?.addEventListener('input', onLocalTypingActivity);
    input?.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') onLocalTypingActivity();
    });

    form?.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = (input.value || '').trim();
        if (!text) return;
        clearTimeout(typingIdleTimer);
        clearTimeout(typingSendTimer);
        typingSendTimer = null;
        notifyTyping(false);
        var fd = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: fd
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data.message) appendMsg(data.message);
            if (data.typing) setGuestTyping(!!data.typing.guest);
            if (data.staff) applyStaffInfo(data.staff);
            input.value = '';
            input.focus();
        }).catch(function () {
            form.submit();
        });
    });

    setInterval(function () {
        fetch(pollUrl + '?after_id=' + lastId, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            (data.messages || []).forEach(function (m) {
                appendMsg(m);
                if (window.adminChatNotify && m.sender === 'guest') {
                    window.adminChatNotify.markNotified(m.id);
                    window.adminChatNotify.setAfterId(m.id);
                }
            });
            if (data.typing) {
                setGuestTyping(!!data.typing.guest);
            }
            if (data.staff) applyStaffInfo(data.staff);
            if (data.status === 'closed') {
                conversationOpen = false;
            }
        }).catch(function () {});
    }, 2000);
})();
</script>
@endsection
