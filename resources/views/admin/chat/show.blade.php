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
                            $productCard = \App\Support\ChatProductShare::cardFromMessage($m);
                        @endphp
                        <div class="admin-msg admin-msg--{{ $m->sender }} {{ $m->sender === 'admin' && (int) $m->admin_user_id !== (int) auth()->id() ? 'admin-msg--other-staff' : '' }}" data-id="{{ $m->id }}">
                            <div class="admin-msg__meta">
                                {{ $label }}
                                · {{ $m->created_at?->format('H:i d/m') }}
                            </div>
                            <div class="admin-msg__body">{!! nl2br(e($m->body)) !!}</div>
                            @if($productCard)
                                <a class="admin-product-card" href="{{ $productCard['url'] ?? '#' }}" target="_blank" rel="noopener">
                                    @if(!empty($productCard['image_url']))
                                        <img src="{{ $productCard['image_url'] }}" alt="{{ $productCard['name'] ?? '' }}">
                                    @else
                                        <div class="admin-product-card__empty"><i class="bi bi-box-seam"></i></div>
                                    @endif
                                    <div class="admin-product-card__body">
                                        <div class="admin-product-card__name">{{ $productCard['name'] ?? 'Sản phẩm' }}</div>
                                        @if(!empty($productCard['price_formatted']))
                                            <div class="admin-product-card__price">{{ $productCard['price_formatted'] }}</div>
                                        @endif
                                        @if(!empty($productCard['url']))
                                            <div class="admin-product-card__link">{{ $productCard['url'] }}</div>
                                        @endif
                                    </div>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div id="adminGuestTypingBar" class="admin-typing-bar d-none" aria-live="polite">
                    <span class="admin-typing-dots" aria-hidden="true"><i></i><i></i><i></i></span>
                    <span>{{ $conversation->guest_name }} đang nhập tin nhắn…</span>
                </div>
            </div>
            <div class="card-footer bg-white">
                <div id="adminMentionBox" class="admin-mention-box d-none mb-2">
                    <div class="d-flex justify-content-between align-items-center px-2 py-1 border-bottom small text-secondary">
                        <span>Chọn sản phẩm (@)</span>
                        <button type="button" class="btn btn-sm btn-link text-secondary p-0" id="adminMentionClose">Đóng</button>
                    </div>
                    <div id="adminMentionList" class="admin-mention-list"></div>
                    <div id="adminMentionEmpty" class="small text-secondary text-center py-2 d-none">Không tìm thấy sản phẩm</div>
                </div>
                <div id="adminPendingProduct" class="admin-pending-product d-none mb-2">
                    <span id="adminPendingThumb"></span>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold small text-truncate" id="adminPendingName">Sản phẩm</div>
                        <div class="small text-secondary" id="adminPendingSub">Sẽ gửi kèm thẻ sản phẩm (ảnh + link)</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-secondary p-0" id="adminPendingClear" aria-label="Bỏ">×</button>
                </div>
                <form id="adminReplyForm" action="{{ route('admin.chat.reply', $conversation) }}" method="POST" class="d-flex gap-2">
                    @csrf
                    <input type="hidden" name="product_id" id="adminReplyProductId" value="">
                    <textarea name="message" id="adminReplyInput" class="form-control" rows="2" placeholder="Nhập trả lời… Gõ @ để chọn sản phẩm" {{ $conversation->status === 'closed' ? 'disabled' : '' }}></textarea>
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
    .admin-mention-box {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        max-height: 220px;
        overflow: hidden;
    }
    .admin-mention-list { max-height: 180px; overflow-y: auto; }
    .admin-mention-item {
        display: flex;
        align-items: center;
        gap: .6rem;
        width: 100%;
        border: 0;
        background: #fff;
        text-align: left;
        padding: .5rem .75rem;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
    }
    .admin-mention-item:hover, .admin-mention-item.is-active { background: #eff6ff; }
    .admin-mention-item img {
        width: 36px; height: 36px; border-radius: 8px; object-fit: cover; background: #e2e8f0;
    }
    .admin-mention-item .meta { min-width: 0; }
    .admin-mention-item .name { font-weight: 600; font-size: .9rem; }
    .admin-mention-item .sub { font-size: .78rem; color: #64748b; }
    .admin-product-card {
        display: flex;
        gap: .65rem;
        margin-top: .4rem;
        max-width: 280px;
        text-align: left;
        text-decoration: none !important;
        color: inherit !important;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 2px 8px rgba(15,23,42,.06);
    }
    .admin-msg--admin .admin-product-card,
    .admin-msg--bot .admin-product-card {
        border-color: rgba(255,255,255,.25);
        background: rgba(255,255,255,.12);
        color: #fff !important;
    }
    .admin-product-card img {
        width: 72px; height: 72px; object-fit: cover; background: #e2e8f0; flex-shrink: 0;
    }
    .admin-product-card__empty {
        width: 72px; height: 72px; display: flex; align-items: center; justify-content: center;
        background: #e2e8f0; color: #94a3b8; font-size: 1.3rem; flex-shrink: 0;
    }
    .admin-product-card__body { padding: .45rem .55rem .5rem; min-width: 0; }
    .admin-product-card__name {
        font-weight: 700; font-size: .82rem; line-height: 1.3;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .admin-product-card__price { font-size: .78rem; font-weight: 700; color: #b45309; margin-top: .15rem; }
    .admin-msg--admin .admin-product-card__price,
    .admin-msg--bot .admin-product-card__price { color: #fde68a; }
    .admin-product-card__link {
        font-size: .7rem; color: #2563eb; margin-top: .2rem; word-break: break-all;
    }
    .admin-msg--admin .admin-product-card__link,
    .admin-msg--bot .admin-product-card__link { color: #bfdbfe; }
    .admin-pending-product {
        display: flex;
        align-items: center;
        gap: .55rem;
        padding: .45rem .6rem;
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        background: #eff6ff;
    }
    .admin-pending-product img {
        width: 36px; height: 36px; border-radius: 8px; object-fit: cover; background: #e2e8f0;
    }
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
    var productsUrl = @json(route('shop.chat.products'));
    var lastId = {{ $conversation->messages->max('id') ?? 0 }};
    var currentUserId = {{ (int) auth()->id() }};
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || form.querySelector('input[name="_token"]')?.value;
    var localTyping = false;
    var typingSendTimer = null;
    var typingIdleTimer = null;
    var conversationOpen = @json($conversation->status === 'open');
    var mentionBox = document.getElementById('adminMentionBox');
    var mentionList = document.getElementById('adminMentionList');
    var mentionEmpty = document.getElementById('adminMentionEmpty');
    var mentionClose = document.getElementById('adminMentionClose');
    var pendingBox = document.getElementById('adminPendingProduct');
    var pendingThumb = document.getElementById('adminPendingThumb');
    var pendingName = document.getElementById('adminPendingName');
    var pendingSub = document.getElementById('adminPendingSub');
    var pendingClear = document.getElementById('adminPendingClear');
    var productIdInput = document.getElementById('adminReplyProductId');
    var mentionTimer = null;
    var mentionItems = [];
    var mentionActive = -1;
    var mentionQuery = null;
    var pendingProduct = null;

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
            // Refresh cache TTL while still typing (must be < server TTL 6s).
            typingSendTimer = setTimeout(function () {
                typingSendTimer = null;
                if (localTyping) {
                    localTyping = false;
                    notifyTyping(true);
                }
            }, 1800);
        }
        clearTimeout(typingIdleTimer);
        typingIdleTimer = setTimeout(function () {
            notifyTyping(false);
        }, 2500);
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

    function productCardHtml(product) {
        if (!product || !product.id) return '';
        var img = product.image_url
            ? '<img src="' + escHtml(product.image_url) + '" alt="">'
            : '<div class="admin-product-card__empty"><i class="bi bi-box-seam"></i></div>';
        return '<a class="admin-product-card" href="' + escHtml(product.url || '#') + '" target="_blank" rel="noopener">' +
            img +
            '<div class="admin-product-card__body">' +
                '<div class="admin-product-card__name">' + escHtml(product.name || 'Sản phẩm') + '</div>' +
                (product.price_formatted ? ('<div class="admin-product-card__price">' + escHtml(product.price_formatted) + '</div>') : '') +
                (product.url ? ('<div class="admin-product-card__link">' + escHtml(product.url) + '</div>') : '') +
            '</div></a>';
    }

    function setPendingProduct(product) {
        if (!product || !product.id) {
            clearPendingProduct();
            return;
        }
        pendingProduct = product;
        if (productIdInput) productIdInput.value = String(product.id);
        if (pendingBox) {
            pendingBox.classList.remove('d-none');
            if (pendingName) pendingName.textContent = product.name || 'Sản phẩm';
            if (pendingSub) {
                pendingSub.textContent = (product.price_formatted || '') +
                    (product.sku ? (' · ' + product.sku) : '') +
                    ' · kèm ảnh + link khi gửi';
            }
            if (pendingThumb) {
                pendingThumb.innerHTML = product.image_url
                    ? '<img src="' + escHtml(product.image_url) + '" alt="">'
                    : '<span class="bg-light rounded d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="bi bi-box-seam text-secondary"></i></span>';
            }
        }
    }

    function clearPendingProduct() {
        pendingProduct = null;
        if (productIdInput) productIdInput.value = '';
        if (pendingBox) pendingBox.classList.add('d-none');
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
            '<div class="admin-msg__meta">' + escHtml(who) + ' · ' + escHtml(m.created_at || '') + '</div>' +
            '<div class="admin-msg__body"></div>';
        wrap.querySelector('.admin-msg__body').textContent = m.body || '';
        if (m.product) {
            wrap.insertAdjacentHTML('beforeend', productCardHtml(m.product));
        }
        log.appendChild(wrap);
        lastId = Math.max(lastId, m.id);
        if (m.sender === 'guest') setGuestTyping(false);
        scrollBottom();
    }

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }
    function getMentionMatch(value, caret) {
        if (caret == null) caret = (value || '').length;
        var before = String(value || '').slice(0, caret);
        var m = before.match(/(^|[\s\n])@([^\s@]{0,40})$/);
        if (!m) return null;
        return { start: caret - m[2].length - 1, end: caret, query: m[2] };
    }
    function hideMention() {
        mentionQuery = null;
        mentionItems = [];
        mentionActive = -1;
        if (mentionBox) mentionBox.classList.add('d-none');
        if (mentionList) mentionList.innerHTML = '';
        if (mentionEmpty) mentionEmpty.classList.add('d-none');
    }
    function renderMentionList(items) {
        mentionItems = items || [];
        mentionActive = mentionItems.length ? 0 : -1;
        if (!mentionList) return;
        mentionList.innerHTML = '';
        if (!mentionItems.length) {
            if (mentionEmpty) mentionEmpty.classList.remove('d-none');
            return;
        }
        if (mentionEmpty) mentionEmpty.classList.add('d-none');
        mentionItems.forEach(function (p, idx) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'admin-mention-item' + (idx === 0 ? ' is-active' : '');
            var img = p.image_url
                ? '<img src="' + escHtml(p.image_url) + '" alt="">'
                : '<span class="bg-light rounded d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px"><i class="bi bi-box-seam text-secondary"></i></span>';
            btn.innerHTML = img +
                '<span class="meta"><div class="name">' + escHtml(p.name) + '</div>' +
                '<div class="sub">' + escHtml(p.price_formatted || '') + (p.sku ? (' · ' + escHtml(p.sku)) : '') + '</div></span>';
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                pickMention(p);
            });
            mentionList.appendChild(btn);
        });
    }
    function setMentionActive(idx) {
        if (!mentionItems.length) return;
        mentionActive = (idx + mentionItems.length) % mentionItems.length;
        var nodes = mentionList ? mentionList.querySelectorAll('.admin-mention-item') : [];
        nodes.forEach(function (n, i) { n.classList.toggle('is-active', i === mentionActive); });
    }
    function fetchMentionProducts(q) {
        fetch(productsUrl + '?q=' + encodeURIComponent(q || ''), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!mentionQuery) return;
            if (mentionBox) mentionBox.classList.remove('d-none');
            renderMentionList((data && data.data) ? data.data : []);
        }).catch(function () {
            if (mentionBox) mentionBox.classList.remove('d-none');
            renderMentionList([]);
        });
    }
    function updateMentionFromInput() {
        if (!input || input.disabled) { hideMention(); return; }
        var match = getMentionMatch(input.value, input.selectionStart);
        if (!match) { hideMention(); return; }
        mentionQuery = match;
        clearTimeout(mentionTimer);
        mentionTimer = setTimeout(function () {
            if (mentionQuery) fetchMentionProducts(mentionQuery.query);
        }, 120);
    }
    function pickMention(product) {
        if (!input || !product) return;
        var value = input.value || '';
        var start = mentionQuery ? mentionQuery.start : value.length;
        var end = mentionQuery ? mentionQuery.end : value.length;
        var insert = 'Tôi đang tư vấn sản phẩm: ' + product.name +
            (product.sku ? (' (SKU: ' + product.sku + ')') : '') +
            (product.price_formatted ? (' — ' + product.price_formatted) : '') +
            (product.url ? (' — ' + product.url) : '') + ' ';
        input.value = value.slice(0, start) + insert + value.slice(end);
        var caret = start + insert.length;
        input.focus();
        try { input.setSelectionRange(caret, caret); } catch (e) {}
        setPendingProduct(product);
        hideMention();
        onLocalTypingActivity();
    }

    input?.addEventListener('input', function () {
        onLocalTypingActivity();
        updateMentionFromInput();
    });
    input?.addEventListener('click', updateMentionFromInput);
    input?.addEventListener('keydown', function (e) {
        if (mentionBox && !mentionBox.classList.contains('d-none') && mentionItems.length) {
            if (e.key === 'ArrowDown') { e.preventDefault(); setMentionActive(mentionActive + 1); return; }
            if (e.key === 'ArrowUp') { e.preventDefault(); setMentionActive(mentionActive - 1); return; }
            if ((e.key === 'Enter' && !e.shiftKey) || e.key === 'Tab') {
                if (mentionActive >= 0 && mentionItems[mentionActive]) {
                    e.preventDefault();
                    pickMention(mentionItems[mentionActive]);
                    return;
                }
            }
            if (e.key === 'Escape') { e.preventDefault(); hideMention(); return; }
        }
        if (e.key !== 'Enter') onLocalTypingActivity();
    });
    mentionClose?.addEventListener('click', hideMention);
    pendingClear?.addEventListener('click', clearPendingProduct);

    form?.addEventListener('submit', function (e) {
        e.preventDefault();
        if (mentionBox && !mentionBox.classList.contains('d-none') && mentionItems.length && mentionActive >= 0) {
            pickMention(mentionItems[mentionActive]);
            return;
        }
        var text = (input.value || '').trim();
        if (!text && !(pendingProduct && pendingProduct.id)) return;
        hideMention();
        clearTimeout(typingIdleTimer);
        clearTimeout(typingSendTimer);
        typingSendTimer = null;
        notifyTyping(false);
        var fd = new FormData(form);
        if (pendingProduct && pendingProduct.id) {
            fd.set('product_id', String(pendingProduct.id));
        }
        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: fd
        }).then(function (r) {
            return r.json().then(function (data) {
                return { ok: r.ok, status: r.status, data: data };
            });
        }).then(function (res) {
            var data = res.data || {};
            if (!res.ok) {
                if (data.closed || data.status === 'closed') {
                    applyClosedUi(data.message || 'Hội thoại đã đóng do quá 30 phút không có tin nhắn.');
                    return;
                }
                alert(data.message || 'Không gửi được tin nhắn.');
                return;
            }
            if (data.message) appendMsg(data.message);
            if (data.typing) setGuestTyping(!!data.typing.guest);
            if (data.staff) applyStaffInfo(data.staff);
            input.value = '';
            clearPendingProduct();
            input.focus();
        }).catch(function () {
            form.submit();
        });
    });

    function applyClosedUi(notice) {
        conversationOpen = false;
        setGuestTyping(false);
        notifyTyping(false);
        if (input) {
            input.disabled = true;
            input.placeholder = 'Hội thoại đã đóng';
        }
        var btn = form ? form.querySelector('button[type="submit"]') : null;
        if (btn) btn.disabled = true;
        clearPendingProduct();
        if (notice && window.appToast) {
            appToast.warning(notice);
        } else if (notice) {
            // one-time banner via alert only if toast missing
        }
        if (staffBanner && staffBannerText) {
            staffBanner.classList.remove('d-none', 'is-other');
            staffBannerText.textContent = 'Hội thoại đã đóng (quá 30 phút không nhắn tin hoặc đã đóng thủ công)';
        }
    }

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
            if (data.status === 'closed' && conversationOpen) {
                applyClosedUi('Hội thoại đã tự đóng vì không có tin nhắn mới trong 30 phút.');
            }
        }).catch(function () {});
    }, 2000);
})();
</script>
@endsection
