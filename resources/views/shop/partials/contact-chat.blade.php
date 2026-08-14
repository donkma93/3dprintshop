@php
    $phoneRaw = $settings['hotline'] ?? $settings['phone'] ?? null;
    $phoneHref = $phoneRaw ? preg_replace('/\s+/', '', $phoneRaw) : null;
    $zalo = $settings['zalo'] ?? null;
    $facebook = $settings['facebook'] ?? null;
    $email = $settings['email'] ?? null;
@endphp

{{-- Brand icons always separate; expanded on large screens, collapsible on phones --}}
<div class="float-rail" id="floatRail" aria-label="Liên hệ và chat chốt đơn">
    <div class="float-rail__stack" id="contactFabMenu">
        {{-- Top → bottom: contact channels first, chat chốt đơn nearest corner --}}
        @if($email)
            <a class="float-rail__item float-rail__item--mail" href="mailto:{{ $email }}" title="Email {{ $email }}" aria-label="Gửi email">
                <span class="float-rail__btn float-rail__btn--mail">
                    <span class="float-rail__icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48" width="26" height="26" xmlns="http://www.w3.org/2000/svg" focusable="false">
                            <path fill="#fff" d="M10 14.5A2.5 2.5 0 0 1 12.5 12h23A2.5 2.5 0 0 1 38 14.5v19a2.5 2.5 0 0 1-2.5 2.5h-23A2.5 2.5 0 0 1 10 33.5v-19zm3.1 1.5 10.4 8.1c.3.2.7.2 1 0L34.9 16H13.1zm22.4 2.8-9.5 7.4a3.2 3.2 0 0 1-3.9 0L12.5 18.8V32h23V18.8z"/>
                        </svg>
                    </span>
                    <span class="float-rail__label"><span class="float-rail__label-main">Email</span></span>
                </span>
            </a>
        @endif

        @if($facebook)
            <a class="float-rail__item float-rail__item--fb" href="{{ $facebook }}" target="_blank" rel="noopener" title="Facebook" aria-label="Facebook">
                <span class="float-rail__btn float-rail__btn--fb">
                    <span class="float-rail__icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48" width="28" height="28" xmlns="http://www.w3.org/2000/svg" focusable="false">
                            <path fill="#fff" d="M26.8 38V25.4h4.2l.6-4.9h-4.8v-3.1c0-1.4.4-2.4 2.4-2.4h2.6v-4.4c-.4-.1-2-.2-3.8-.2-3.8 0-6.3 2.3-6.3 6.5v3.6H18v4.9h3.7V38h5.1z"/>
                        </svg>
                    </span>
                    <span class="float-rail__label"><span class="float-rail__label-main">Facebook</span></span>
                </span>
            </a>
        @endif

        @if($zalo)
            <a class="float-rail__item float-rail__item--zalo" href="{{ $zalo }}" target="_blank" rel="noopener" title="Chat Zalo" aria-label="Chat Zalo">
                <span class="float-rail__btn float-rail__btn--zalo">
                    <span class="float-rail__icon float-rail__icon--zalo" aria-hidden="true">
                        <svg viewBox="0 0 48 48" width="28" height="28" xmlns="http://www.w3.org/2000/svg" focusable="false">
                            <path fill="#fff" d="M24 10c-7.7 0-14 5.2-14 11.6 0 3.7 2 7 5.2 9.2l-.8 5 5.1-2.7c1.4.4 2.9.6 4.5.6 7.7 0 14-5.2 14-11.6S31.7 10 24 10z"/>
                            <path fill="#0068ff" d="M18.2 19.2h9.6v2.1H21.4l5.8 6.2H17.6v-2.1h6.2l-5.6-6.2z"/>
                        </svg>
                    </span>
                    <span class="float-rail__label"><span class="float-rail__label-main">Zalo</span></span>
                </span>
            </a>
        @endif

        @if($phoneHref)
            <a class="float-rail__item float-rail__item--phone" href="tel:{{ $phoneHref }}" title="Gọi {{ $phoneRaw }}" aria-label="Gọi điện {{ $phoneRaw }}">
                <span class="float-rail__btn float-rail__btn--phone">
                    <span class="float-rail__icon" aria-hidden="true">
                        <svg viewBox="0 0 48 48" width="26" height="26" xmlns="http://www.w3.org/2000/svg" focusable="false">
                            <path fill="#fff" d="M33.8 29.4c-1.1-.6-2.4-.3-3.2.5l-1.7 1.7c-.4.4-1 .5-1.5.3-1.7-.7-3.6-1.9-5.4-3.7s-3-3.7-3.7-5.4c-.2-.5-.1-1.1.3-1.5l1.7-1.7c.8-.8 1.1-2.1.5-3.2l-2.2-4.1c-.7-1.3-2.3-1.8-3.6-1.2l-2.4 1.1c-1.2.6-2 1.7-2.1 3-.3 3.4.7 7.3 4.2 12.2 3.6 5 7.5 7.7 11 8.9 1.3.5 2.7.2 3.6-.7l1.9-1.9c1.1-1.1 1.1-2.8.1-3.9l-2.5-2.4z"/>
                        </svg>
                    </span>
                    <span class="float-rail__label"><span class="float-rail__label-main">Gọi điện</span></span>
                </span>
            </a>
        @endif

        <div class="float-rail__item float-rail__item--chat" id="chatFab">
            <button type="button" class="float-rail__btn float-rail__btn--chat" id="openChatBtn" title="Chat chốt đơn" aria-controls="chatWidget" aria-expanded="false">
                <span class="float-rail__icon" aria-hidden="true">
                    <svg viewBox="0 0 48 48" width="28" height="28" xmlns="http://www.w3.org/2000/svg" focusable="false">
                        <path fill="currentColor" d="M10 16.5c0-4.14 4.7-7.5 10.5-7.5h7c5.8 0 10.5 3.36 10.5 7.5v7c0 4.14-4.7 7.5-10.5 7.5h-2.2L20 35.5l.8-5.5H20.5C14.7 30 10 26.64 10 22.5v-6z"/>
                        <circle cx="20" cy="20.5" r="1.6" fill="#1a1408"/>
                        <circle cx="26" cy="20.5" r="1.6" fill="#1a1408"/>
                        <circle cx="32" cy="20.5" r="1.6" fill="#1a1408"/>
                    </svg>
                </span>
                <span class="float-rail__label">
                    <span class="float-rail__label-main">Chat chốt đơn</span>
                    <span class="float-rail__label-sub">Tư vấn · báo giá · đặt hàng</span>
                </span>
                <span class="float-rail__badge" id="chatMenuBadge" hidden>0</span>
                <span class="float-rail__pulse" id="chatFabPulse" hidden aria-hidden="true"></span>
            </button>
        </div>
    </div>

    {{-- Chỉ hiện trên màn điện thoại nhỏ: gộp / bung icon --}}
    <button type="button" class="float-rail__toggle" id="contactFabToggle" aria-expanded="false" aria-controls="contactFabMenu" title="Liên hệ & chat">
        <span class="float-rail__toggle-open" aria-hidden="true">
            <svg viewBox="0 0 48 48" width="26" height="26" xmlns="http://www.w3.org/2000/svg" focusable="false">
                <path fill="currentColor" d="M14 18.5c0-3.6 4.5-6.5 10-6.5s10 2.9 10 6.5-4.5 6.5-10 6.5h-1.6L18 30l1.2-5H24c-5.5 0-10-2.9-10-6.5z"/>
                <circle cx="19.5" cy="18.5" r="1.4" fill="#fff"/>
                <circle cx="24" cy="18.5" r="1.4" fill="#fff"/>
                <circle cx="28.5" cy="18.5" r="1.4" fill="#fff"/>
            </svg>
        </span>
        <span class="float-rail__toggle-close" aria-hidden="true"><i class="bi bi-x-lg"></i></span>
    </button>
</div>

{{-- Toast tin nhắn nhân viên khi widget đang đóng --}}
<div class="chat-toast" id="chatStaffToast" hidden role="status" aria-live="polite">
    <div class="chat-toast__icon"><i class="bi bi-chat-dots-fill"></i></div>
    <div class="chat-toast__body">
        <strong id="chatStaffToastTitle">Tin nhắn từ nhân viên</strong>
        <p id="chatStaffToastText">Bạn có tin nhắn mới.</p>
    </div>
    <button type="button" class="chat-toast__open" id="chatStaffToastOpen">Mở chat</button>
</div>

{{-- Proactive invite: IP mới trong ngày / quá 3 giờ chưa chat --}}
<div class="chat-toast chat-toast--proactive" id="chatProactiveToast" hidden role="dialog" aria-live="polite" aria-label="Mời chat chốt đơn">
    <div class="chat-toast__icon"><i class="bi bi-emoji-smile"></i></div>
    <div class="chat-toast__body">
        <strong id="chatProactiveTitle">Shop muốn hỗ trợ bạn chốt đơn</strong>
        <p id="chatProactiveText">Chat trực tiếp để tư vấn, báo giá và đặt hàng — chỉ cần để lại tên.</p>
    </div>
    <div class="chat-toast__actions">
        <button type="button" class="chat-toast__dismiss" id="chatProactiveDismiss" title="Để sau">Để sau</button>
        <button type="button" class="chat-toast__open" id="chatProactiveOpen">Chat chốt đơn</button>
    </div>
</div>

<div class="chat-widget" id="chatWidget" hidden>
    <div class="chat-widget__head">
        <div>
            <strong>Chat chốt đơn</strong>
            <div class="chat-widget__sub" id="chatWidgetSub">Tư vấn · báo giá · đặt hàng nhanh với Shop3DPrinting</div>
            <div class="chat-widget__staff" id="chatStaffStatus" hidden></div>
        </div>
        <button type="button" class="chat-widget__close" id="chatClose" aria-label="Đóng chat">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="chat-widget__body">
        <div id="chatLead" class="chat-lead">
            <p class="chat-lead__intro">Xin chào! Dùng khung chat này để <strong>tư vấn, báo giá và chốt đơn</strong>. Chỉ cần để lại <strong>tên</strong> — SĐT/email không bắt buộc.</p>
            <form id="chatStartForm" class="chat-lead__form" novalidate>
                <label class="chat-field">
                    <span>Tên của bạn *</span>
                    <input type="text" name="guest_name" required maxlength="120" placeholder="Ví dụ: Minh, chị Lan, anh Hùng…" autocomplete="name" autofocus>
                </label>
                <div class="chat-field chat-field--optional" id="chatOptionalContact">
                    <button type="button" class="chat-optional-toggle" id="chatOptionalToggle" aria-expanded="false">
                        Thêm SĐT / email (tuỳ chọn)
                    </button>
                    <div class="chat-optional-fields" id="chatOptionalFields" hidden>
                        <label class="chat-field">
                            <span>Số điện thoại</span>
                            <input type="tel" name="guest_phone" maxlength="40" placeholder="09xx xxx xxx" autocomplete="tel">
                        </label>
                        <label class="chat-field">
                            <span>Email</span>
                            <input type="email" name="guest_email" maxlength="150" placeholder="email@domain.com" autocomplete="email">
                        </label>
                    </div>
                </div>
                <label class="chat-field">
                    <span>Nhu cầu / sản phẩm muốn đặt (tuỳ chọn)</span>
                    <textarea name="message" rows="2" maxlength="2000" placeholder="Ví dụ: in mô hình STL, số lượng, kích thước, thời gian giao…"></textarea>
                </label>
                <p class="chat-lead__hint">Nhân viên sẽ chat trực tiếp để chốt đơn. SĐT/email chỉ giúp liên hệ lại nếu bạn muốn.</p>
                <div class="chat-lead__error" id="chatStartError" hidden></div>
                <button type="submit" class="chat-btn-primary" id="chatStartBtn">Bắt đầu chat chốt đơn</button>
            </form>
        </div>

        <div id="chatRoom" class="chat-room" hidden>
            <div class="chat-closed-banner" id="chatClosedBanner" hidden>
                <div class="chat-closed-banner__text">
                    Hội thoại này đã được nhân viên đóng. Bạn có thể xem lại tin cũ hoặc mở hội thoại mới.
                </div>
                <button type="button" class="chat-btn-primary chat-closed-banner__btn" id="chatNewConversationBtn">
                    Mở hội thoại mới
                </button>
            </div>
            <div class="chat-log" id="chatLog" aria-live="polite"></div>
            <div class="chat-typing" id="chatTyping" hidden aria-live="polite">
                <span class="chat-typing__dots" aria-hidden="true"><i></i><i></i><i></i></span>
                <span class="chat-typing__text" id="chatTypingText">Nhân viên đang nhập…</span>
            </div>
            <div class="chat-mention" id="chatMentionBox" hidden>
                <div class="chat-mention__head">
                    <span>Chọn sản phẩm (@)</span>
                    <button type="button" class="chat-mention__close" id="chatMentionClose" aria-label="Đóng">×</button>
                </div>
                <div class="chat-mention__list" id="chatMentionList" role="listbox"></div>
                <div class="chat-mention__empty" id="chatMentionEmpty" hidden>Không tìm thấy sản phẩm</div>
            </div>
            <div class="chat-pending-product" id="chatPendingProduct" aria-live="polite">
                <span class="chat-pending-product__thumb" id="chatPendingThumb"></span>
                <div class="chat-pending-product__meta">
                    <div class="chat-pending-product__name" id="chatPendingName">Sản phẩm</div>
                    <div class="chat-pending-product__sub" id="chatPendingSub">Sẽ gửi kèm thẻ sản phẩm (ảnh + link)</div>
                </div>
                <button type="button" class="chat-pending-product__clear" id="chatPendingClear" aria-label="Bỏ sản phẩm">×</button>
            </div>
            <form id="chatSendForm" class="chat-send">
                <input type="text" id="chatInput" maxlength="2000" placeholder="Nhập tin nhắn… Gõ @ để chọn sản phẩm" autocomplete="off">
                <button type="submit" class="chat-send__btn" aria-label="Gửi"><i class="bi bi-send-fill"></i></button>
            </form>
        </div>
    </div>
</div>
