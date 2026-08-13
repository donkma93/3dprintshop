<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Admin 3D Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar: #0f172a;
            --accent: #14b8a6;
        }
        body { background: #f1f5f9; }
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: var(--sidebar);
            color: #cbd5e1;
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1000;
            overflow-y: auto;
        }
        .sidebar .brand {
            font-weight: 800;
            color: #fff;
            padding: 1.25rem 1.25rem .5rem;
            font-size: 1.15rem;
        }
        .sidebar .brand span { color: var(--accent); }
        .sidebar .nav-link {
            color: #94a3b8;
            border-radius: .65rem;
            margin: .15rem .75rem;
            padding: .65rem .9rem;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(20, 184, 166, .15);
            color: #fff;
        }
        .sidebar .nav-link i { width: 1.4rem; }
        .main {
            margin-left: 260px;
            min-height: 100vh;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: .9rem 1.5rem;
        }
        .content { padding: 1.5rem; }
        .stat-card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }
        .stat-card .icon {
            width: 48px; height: 48px;
            border-radius: .85rem;
            display: grid; place-items: center;
            background: #ecfdf5; color: #0f766e;
            font-size: 1.25rem;
        }
        .card { border: 0; border-radius: 1rem; box-shadow: 0 8px 24px rgba(15, 23, 42, .05); }
        .table > :not(caption) > * > * { vertical-align: middle; }
        .badge-soft {
            background: #ecfdf5;
            color: #0f766e;
            font-weight: 600;
        }
        .badge-warn {
            background: #faf7ef;
            color: #9a7b12;
            font-weight: 600;
        }
        .admin-notify-wrap {
            position: relative;
        }
        .admin-notify-btn {
            border: 0;
            background: transparent;
            color: #64748b;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: background .15s ease, color .15s ease;
        }
        .admin-notify-btn:hover,
        .admin-notify-btn.is-open {
            background: #f1f5f9;
            color: #0f172a;
        }
        .admin-notify-btn .badge {
            font-size: .65rem;
            min-width: 1.1rem;
        }
        .admin-notify-panel {
            position: absolute;
            right: 0;
            top: calc(100% + .55rem);
            width: min(380px, calc(100vw - 1.5rem));
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 18px 50px rgba(15, 23, 42, .16);
            z-index: 1200;
            overflow: hidden;
            display: none;
        }
        .admin-notify-panel.is-open { display: block; animation: adminNotifyIn .16s ease; }
        @keyframes adminNotifyIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: none; }
        }
        .admin-notify-panel__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .85rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .admin-notify-panel__head strong { font-size: .95rem; color: #0f172a; }
        .admin-notify-panel__actions {
            display: flex;
            align-items: center;
            gap: .35rem;
        }
        .admin-notify-panel__actions .btn {
            font-size: .75rem;
            padding: .2rem .55rem;
        }
        .admin-notify-list {
            max-height: min(420px, 60vh);
            overflow-y: auto;
        }
        .admin-notify-item {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .35rem .5rem;
            padding: .8rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            text-align: left;
            background: #fff;
            transition: background .12s ease;
        }
        .admin-notify-item:hover { background: #f8fafc; }
        .admin-notify-item.is-unread {
            background: #f0fdfa;
        }
        .admin-notify-item.is-unread:hover { background: #ecfdf5; }
        .admin-notify-item__title {
            font-size: .88rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: .4rem;
        }
        .admin-notify-item__dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #14b8a6;
            flex-shrink: 0;
        }
        .admin-notify-item__body {
            grid-column: 1 / 2;
            font-size: .82rem;
            color: #64748b;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .admin-notify-item__meta {
            font-size: .72rem;
            color: #94a3b8;
            white-space: nowrap;
        }
        .admin-notify-item__mark {
            grid-column: 2;
            grid-row: 1 / span 2;
            align-self: center;
            border: 0;
            background: transparent;
            color: #94a3b8;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .admin-notify-item__mark:hover {
            background: #e2e8f0;
            color: #0f766e;
        }
        .admin-notify-empty {
            padding: 2rem 1rem;
            text-align: center;
            color: #94a3b8;
            font-size: .9rem;
        }
        .admin-notify-panel__foot {
            padding: .65rem 1rem;
            border-top: 1px solid #e2e8f0;
            background: #fff;
            text-align: center;
        }
        .admin-notify-panel__foot a {
            font-size: .85rem;
            font-weight: 600;
            color: #0f766e;
            text-decoration: none;
        }
        .admin-notify-panel__foot a:hover { text-decoration: underline; }
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); transition: .2s; }
            .sidebar.show { transform: none; }
            .main { margin-left: 0; }
        }
    </style>
</head>
<body>
<aside class="sidebar" id="sidebar">
    <div class="brand">3D<span>Admin</span></div>
    <div class="px-3 pb-2 small text-secondary">Quản lý bán hàng in 3D</div>
    @php($authUser = auth()->user())
    <nav class="nav flex-column py-2">
        @if($authUser?->hasPermission('dashboard.view'))
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-speedometer2"></i> Tổng quan
        </a>
        @endif
        @if($authUser?->hasPermission('products.manage'))
        <a class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
            <i class="bi bi-box-seam"></i> Sản phẩm
        </a>
        @endif
        @if($authUser?->hasPermission('categories.manage'))
        <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
            <i class="bi bi-tags"></i> Danh mục
        </a>
        @endif
        @if($authUser?->hasPermission('materials.manage'))
        <a class="nav-link {{ request()->routeIs('admin.materials.*') ? 'active' : '' }}" href="{{ route('admin.materials.index') }}">
            <i class="bi bi-droplet-half"></i> Nguyên liệu
        </a>
        @endif
        @if($authUser?->hasPermission('material_inputs.manage'))
        <a class="nav-link {{ request()->routeIs('admin.material-inputs.*') ? 'active' : '' }}" href="{{ route('admin.material-inputs.index') }}">
            <i class="bi bi-box-arrow-in-down"></i> Nhập nguyên liệu
        </a>
        @endif
        @if($authUser?->hasPermission('equipment.manage'))
        <a class="nav-link {{ request()->routeIs('admin.equipment.*') ? 'active' : '' }}" href="{{ route('admin.equipment.index') }}">
            <i class="bi bi-printer"></i> Thiết bị
        </a>
        @endif

        @if($authUser?->hasPermission('banners.manage') || $authUser?->hasPermission('posts.manage') || $authUser?->hasPermission('pages.manage'))
        <hr class="border-secondary mx-3 opacity-25">
        <div class="px-3 small text-secondary text-uppercase mb-1">Nội dung website</div>
        @endif
        @if($authUser?->hasPermission('banners.manage'))
        <a class="nav-link {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}" href="{{ route('admin.banners.index') }}">
            <i class="bi bi-images"></i> Banner / Slider
        </a>
        @endif
        @if($authUser?->hasPermission('posts.manage'))
        <a class="nav-link {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}" href="{{ route('admin.posts.index') }}">
            <i class="bi bi-newspaper"></i> Bài viết
        </a>
        @endif
        @if($authUser?->hasPermission('pages.manage'))
        <a class="nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}" href="{{ route('admin.pages.index') }}">
            <i class="bi bi-file-earmark-text"></i> Trang tĩnh
        </a>
        @endif
        @if($authUser?->hasPermission('chat.manage'))
        <a class="nav-link {{ request()->routeIs('admin.chat.*') ? 'active' : '' }}" href="{{ route('admin.chat.index') }}" id="adminChatNavLink">
            <i class="bi bi-chat-dots"></i> Chat khách hàng
            <span class="badge rounded-pill text-bg-danger ms-1 d-none" id="adminChatBadge">0</span>
        </a>
        @endif
        @if($authUser?->hasPermission('orders.manage'))
        <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
            <i class="bi bi-bag-check"></i> Đặt hàng / liên hệ
        </a>
        @endif
        @if($authUser?->hasPermission('sales.sell'))
        <hr class="border-secondary mx-3 opacity-25">
        <div class="px-3 small text-secondary text-uppercase mb-1">Bán hàng nội bộ</div>
        <a class="nav-link {{ request()->routeIs('admin.sales.scan') ? 'active' : '' }}" href="{{ route('admin.sales.scan') }}">
            <i class="bi bi-qr-code-scan"></i> Quét QR bán hàng
        </a>
        <a class="nav-link {{ request()->routeIs('admin.sales.history') ? 'active' : '' }}" href="{{ route('admin.sales.history') }}">
            <i class="bi bi-receipt"></i> Lịch sử bán
        </a>
        @endif
        @if($authUser?->canViewRevenue())
        <a class="nav-link {{ request()->routeIs('admin.sales.report') ? 'active' : '' }}" href="{{ route('admin.sales.report') }}">
            <i class="bi bi-graph-up-arrow"></i> Doanh thu / lãi lỗ
        </a>
        @endif
        @if($authUser?->hasPermission('tax.manage'))
        <hr class="border-secondary mx-3 opacity-25">
        <div class="px-3 small text-secondary text-uppercase mb-1">Thuế HKD (chuẩn bị)</div>
        <a class="nav-link {{ request()->routeIs('admin.tax.index') ? 'active' : '' }}" href="{{ route('admin.tax.index') }}">
            <i class="bi bi-calculator"></i> Tổng quan thuế
        </a>
        <a class="nav-link {{ request()->routeIs('admin.tax.ledger') ? 'active' : '' }}" href="{{ route('admin.tax.ledger') }}">
            <i class="bi bi-journal-text"></i> Sổ doanh thu
        </a>
        <a class="nav-link {{ request()->routeIs('admin.tax.report') ? 'active' : '' }}" href="{{ route('admin.tax.report') }}">
            <i class="bi bi-file-earmark-bar-graph"></i> Báo cáo kỳ
        </a>
        <a class="nav-link {{ request()->routeIs('admin.tax.profile*') ? 'active' : '' }}" href="{{ route('admin.tax.profile') }}">
            <i class="bi bi-person-badge"></i> Hồ sơ HKD
        </a>
        @endif
        @if($authUser?->hasPermission('users.manage'))
        <hr class="border-secondary mx-3 opacity-25">
        <div class="px-3 small text-secondary text-uppercase mb-1">Hệ thống</div>
        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
            <i class="bi bi-people"></i> Người dùng
        </a>
        @endif
        @if($authUser?->hasPermission('settings.manage'))
        <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}">
            <i class="bi bi-gear"></i> Cài đặt & SEO
        </a>
        @endif
        @if($authUser?->hasPermission('trash.manage'))
        <a class="nav-link {{ request()->routeIs('admin.trash.*') ? 'active' : '' }}" href="{{ route('admin.trash.index') }}">
            <i class="bi bi-trash3"></i> Thùng rác
        </a>
        @endif
        <hr class="border-secondary mx-3 opacity-25">
        <a class="nav-link" href="{{ route('shop.home') }}" target="_blank">
            <i class="bi bi-shop"></i> Xem cửa hàng
        </a>
        <form action="{{ route('admin.logout') }}" method="POST" class="mx-3 mt-2">
            @csrf
            <button class="btn btn-outline-light btn-sm w-100" type="submit">
                <i class="bi bi-box-arrow-right"></i> Đăng xuất
            </button>
        </form>
    </nav>
</aside>

<div class="main">
    <div class="topbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" onclick="document.getElementById('sidebar').classList.toggle('show')">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <div class="fw-semibold">@yield('title', 'Dashboard')</div>
                <div class="small text-secondary">@yield('subtitle', 'Bảng điều khiển')</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            @if($authUser?->hasPermission('chat.manage'))
            <div class="admin-notify-wrap" id="adminNotifyWrap">
                <button type="button" class="admin-notify-btn" id="adminNotifyBtn" aria-expanded="false" aria-controls="adminNotifyPanel" title="Thông báo chat">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger d-none" id="adminChatTopBadge">0</span>
                </button>
                <div class="admin-notify-panel" id="adminNotifyPanel" role="dialog" aria-label="Danh sách thông báo chat">
                    <div class="admin-notify-panel__head">
                        <strong>Thông báo chat</strong>
                        <div class="admin-notify-panel__actions">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="adminNotifyMarkAll" title="Đánh dấu tất cả đã đọc">
                                Đã đọc hết
                            </button>
                        </div>
                    </div>
                    <div class="admin-notify-list" id="adminNotifyList">
                        <div class="admin-notify-empty">Đang tải thông báo…</div>
                    </div>
                    <div class="admin-notify-panel__foot">
                        <a href="{{ route('admin.chat.index') }}">Xem tất cả hội thoại</a>
                    </div>
                </div>
            </div>
            @endif
            <div class="small text-end">
                <div class="fw-semibold text-dark">
                    <i class="bi bi-person-circle"></i> {{ $authUser->name ?? 'Admin' }}
                </div>
                <div class="text-secondary" style="font-size:.75rem">
                    {{ $authUser?->roleLabel() ?? '' }}
                    @if($authUser?->canViewRevenue())
                        · <span class="text-success">Doanh thu</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @yield('content')
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@include('partials.toastr')
@stack('scripts')
@if($authUser?->hasPermission('chat.manage'))
<script>
(function () {
    var pollUrl = @json(route('admin.chat.notifications'));
    var readUrl = @json(route('admin.chat.notifications.read'));
    var afterId = 0;
    var badge = document.getElementById('adminChatBadge');
    var topBadge = document.getElementById('adminChatTopBadge');
    var notifyBtn = document.getElementById('adminNotifyBtn');
    var notifyPanel = document.getElementById('adminNotifyPanel');
    var notifyList = document.getElementById('adminNotifyList');
    var markAllBtn = document.getElementById('adminNotifyMarkAll');
    var notifyWrap = document.getElementById('adminNotifyWrap');
    var currentConversationId = @json(optional(request()->route('conversation'))->id);
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var notifiedIds = {};
    var storageKey = 'admin_chat_notified_ids';
    var listCache = [];
    var panelOpen = false;

    try {
        notifiedIds = JSON.parse(sessionStorage.getItem(storageKey) || '{}') || {};
    } catch (e) {
        notifiedIds = {};
    }

    function rememberNotified(id) {
        notifiedIds[String(id)] = 1;
        var keys = Object.keys(notifiedIds);
        if (keys.length > 200) {
            keys.slice(0, keys.length - 150).forEach(function (k) { delete notifiedIds[k]; });
        }
        try { sessionStorage.setItem(storageKey, JSON.stringify(notifiedIds)); } catch (e) {}
    }

    function badgeCount(data) {
        // Ưu tiên số tin chưa đọc cho icon; fallback số hội thoại
        if (data && typeof data.unread_message_count !== 'undefined') {
            return Number(data.unread_message_count) || 0;
        }
        return Number(data && data.unread_count) || 0;
    }

    function setBadges(count) {
        count = Number(count) || 0;
        [badge, topBadge].forEach(function (el) {
            if (!el) return;
            if (count > 0) {
                el.textContent = count > 99 ? '99+' : String(count);
                el.classList.remove('d-none');
            } else {
                el.classList.add('d-none');
            }
        });
        document.title = count > 0
            ? '(' + count + ') ' + document.title.replace(/^\(\d+\+?\)\s*/, '')
            : document.title.replace(/^\(\d+\+?\)\s*/, '');
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function setPanelOpen(open) {
        panelOpen = !!open;
        if (!notifyPanel || !notifyBtn) return;
        notifyPanel.classList.toggle('is-open', panelOpen);
        notifyBtn.classList.toggle('is-open', panelOpen);
        notifyBtn.setAttribute('aria-expanded', panelOpen ? 'true' : 'false');
        if (panelOpen) {
            renderList(listCache);
            // refresh list when opening
            poll(true);
        }
    }

    function renderList(list) {
        if (!notifyList) return;
        listCache = Array.isArray(list) ? list : [];
        if (!listCache.length) {
            notifyList.innerHTML = '<div class="admin-notify-empty">Chưa có thông báo chat.</div>';
            return;
        }

        // Unread first, then newest
        var sorted = listCache.slice().sort(function (a, b) {
            var ua = a.is_unread ? 1 : 0;
            var ub = b.is_unread ? 1 : 0;
            if (ua !== ub) return ub - ua;
            return (Number(b.id) || 0) - (Number(a.id) || 0);
        });

        notifyList.innerHTML = sorted.map(function (item) {
            var title = item.is_new_conversation
                ? ('Chat mới · ' + (item.guest_name || 'Khách'))
                : (item.guest_name || 'Khách');
            var meta = item.created_at_human || item.created_at || '';
            if (item.guest_phone) meta = item.guest_phone + ' · ' + meta;
            return (
                '<div class="admin-notify-item' + (item.is_unread ? ' is-unread' : '') + '" data-id="' + esc(item.id) + '" data-url="' + esc(item.url || '') + '" data-conversation-id="' + esc(item.conversation_id) + '" role="button" tabindex="0">' +
                    '<div class="admin-notify-item__title">' +
                        (item.is_unread ? '<span class="admin-notify-item__dot" title="Chưa đọc"></span>' : '') +
                        esc(title) +
                    '</div>' +
                    '<div class="admin-notify-item__meta">' + esc(meta) + '</div>' +
                    '<div class="admin-notify-item__body">' + esc(item.body || '') + '</div>' +
                    (item.is_unread
                        ? '<button type="button" class="admin-notify-item__mark" data-mark-id="' + esc(item.id) + '" title="Đánh dấu đã đọc"><i class="bi bi-check2-all"></i></button>'
                        : '<span class="admin-notify-item__mark" title="Đã đọc" style="cursor:default;opacity:.35"><i class="bi bi-check2"></i></span>') +
                '</div>'
            );
        }).join('');
    }

    function toastItem(item) {
        if (!item || !item.id) return;
        if (notifiedIds[String(item.id)]) return;
        if (currentConversationId && Number(item.conversation_id) === Number(currentConversationId)) {
            rememberNotified(item.id);
            return;
        }
        rememberNotified(item.id);

        // Cập nhật list popup ngay khi có tin mới
        listCache = [item].concat(listCache.filter(function (x) { return Number(x.id) !== Number(item.id); }));
        if (panelOpen) renderList(listCache);

        var title = item.is_new_conversation
            ? ('Chat mới: ' + (item.guest_name || 'Khách'))
            : ('Tin nhắn từ ' + (item.guest_name || 'Khách'));
        var body = item.body || '';
        if (item.guest_phone) body = (item.guest_phone + ' · ' + body);

        if (window.toastr) {
            var $toast = toastr.info(body, title, {
                timeOut: 8000,
                extendedTimeOut: 3000,
                closeButton: true,
                progressBar: true,
                newestOnTop: true,
                onclick: function () {
                    if (item.url) window.location.href = item.url;
                }
            });
            if ($toast && $toast.css) {
                $toast.css('cursor', 'pointer');
            }
        } else if (window.appToast) {
            appToast.info(title + ' — ' + body);
        }
    }

    function markRead(payload) {
        return fetch(readUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload || {})
        }).then(function (r) {
            if (!r.ok) throw new Error('mark failed');
            return r.json();
        }).then(function (data) {
            setBadges(badgeCount(data));
            if (Array.isArray(data.list)) {
                renderList(data.list);
            }
            if (window.appToast && payload && payload.all) {
                appToast.success('Đã đánh dấu tất cả thông báo đã đọc.');
            }
            return data;
        });
    }

    function poll(forceList) {
        var url = pollUrl + '?after_id=' + encodeURIComponent(afterId || 0);
        if (forceList || afterId === 0 || panelOpen) {
            url += '&with_list=1';
        }
        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).then(function (r) {
            if (!r.ok) throw new Error('poll failed');
            return r.json();
        }).then(function (data) {
            if (typeof data.after_id !== 'undefined') {
                afterId = Math.max(afterId, Number(data.after_id) || 0);
            }
            setBadges(badgeCount(data));
            if (Array.isArray(data.list)) {
                // merge: prefer server list when provided
                renderList(data.list);
            }
            if (!data.bootstrap && Array.isArray(data.items)) {
                data.items.forEach(function (item) {
                    // ensure unread flag for new live items
                    if (typeof item.is_unread === 'undefined') item.is_unread = true;
                    toastItem(item);
                });
            }
        }).catch(function () {});
    }

    notifyBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        setPanelOpen(!panelOpen);
    });

    markAllBtn?.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        markRead({ all: true }).catch(function () {
            if (window.appToast) appToast.error('Không đánh dấu được. Thử lại.');
        });
    });

    notifyList?.addEventListener('click', function (e) {
        var markBtn = e.target.closest('[data-mark-id]');
        if (markBtn) {
            e.preventDefault();
            e.stopPropagation();
            var mid = markBtn.getAttribute('data-mark-id');
            markRead({ message_id: Number(mid) }).catch(function () {
                if (window.appToast) appToast.error('Không đánh dấu được tin này.');
            });
            return;
        }

        var row = e.target.closest('.admin-notify-item');
        if (!row) return;
        var url = row.getAttribute('data-url');
        var id = row.getAttribute('data-id');
        // Đánh dấu đã đọc rồi chuyển trang
        markRead({ message_id: Number(id) }).finally(function () {
            if (url) window.location.href = url;
        });
    });

    notifyList?.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var row = e.target.closest('.admin-notify-item');
        if (!row) return;
        e.preventDefault();
        row.click();
    });

    document.addEventListener('click', function (e) {
        if (!panelOpen) return;
        if (notifyWrap && !notifyWrap.contains(e.target)) {
            setPanelOpen(false);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && panelOpen) setPanelOpen(false);
    });

    // Bootstrap once then poll every 4s
    poll(true);
    setInterval(function () { poll(false); }, 4000);

    window.adminChatNotify = {
        getAfterId: function () { return afterId; },
        setAfterId: function (id) { afterId = Math.max(afterId, Number(id) || 0); },
        markNotified: rememberNotified,
        refresh: function () { poll(true); },
        openPanel: function () { setPanelOpen(true); }
    };
})();
</script>
@endif
</body>
</html>
