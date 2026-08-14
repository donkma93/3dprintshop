@php
    $phoneRaw = $settings['hotline'] ?? $settings['phone'] ?? null;
    $phoneHref = $phoneRaw ? preg_replace('/\s+/', '', $phoneRaw) : null;
    $zalo = $settings['zalo'] ?? null;
    $facebook = $settings['facebook'] ?? null;
    $email = $settings['email'] ?? null;
@endphp

{{-- Floating contact + virtual assistant chat --}}
<div class="contact-fab" id="contactFab" aria-label="Liên hệ nhanh">
    <div class="contact-fab__menu" id="contactFabMenu" hidden>
        @if($phoneHref)
            <a class="contact-fab__item contact-fab__item--phone" href="tel:{{ $phoneHref }}" title="Gọi {{ $phoneRaw }}">
                <i class="bi bi-telephone-fill"></i>
                <span>Gọi điện</span>
            </a>
        @endif
        @if($zalo)
            <a class="contact-fab__item contact-fab__item--zalo" href="{{ $zalo }}" target="_blank" rel="noopener" title="Chat Zalo">
                <i class="bi bi-chat-dots-fill"></i>
                <span>Zalo</span>
            </a>
        @endif
        @if($facebook)
            <a class="contact-fab__item contact-fab__item--fb" href="{{ $facebook }}" target="_blank" rel="noopener" title="Fanpage">
                <i class="bi bi-facebook"></i>
                <span>Fanpage</span>
            </a>
        @endif
        @if($email)
            <a class="contact-fab__item contact-fab__item--mail" href="mailto:{{ $email }}" title="Email">
                <i class="bi bi-envelope-fill"></i>
                <span>Email</span>
            </a>
        @endif
        <button type="button" class="contact-fab__item contact-fab__item--chat" id="openChatBtn" title="Chat trợ lý">
            <i class="bi bi-robot"></i>
            <span>Chat hỗ trợ</span>
            <span class="contact-fab__chat-badge" id="chatMenuBadge" hidden>0</span>
        </button>
    </div>
    <button type="button" class="contact-fab__toggle" id="contactFabToggle" aria-expanded="false" aria-controls="contactFabMenu" title="Liên hệ">
        <i class="bi bi-headset contact-fab__icon-open"></i>
        <i class="bi bi-x-lg contact-fab__icon-close"></i>
        <span class="contact-fab__pulse" id="chatFabPulse" hidden aria-hidden="true"></span>
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

<div class="chat-widget" id="chatWidget" hidden>
    <div class="chat-widget__head">
        <div>
            <strong>Trợ lý ảo 3D Shop</strong>
            <div class="chat-widget__sub" id="chatWidgetSub">Để lại SĐT/email — nhân viên sẽ trả lời</div>
            <div class="chat-widget__staff" id="chatStaffStatus" hidden></div>
        </div>
        <button type="button" class="chat-widget__close" id="chatClose" aria-label="Đóng chat">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="chat-widget__body">
        <div id="chatLead" class="chat-lead">
            <p class="chat-lead__intro">Chào bạn! Hãy để lại thông tin để bắt đầu — trợ lý sẽ tiếp nhận và nhân viên chat lại sớm.</p>
            <form id="chatStartForm" class="chat-lead__form" novalidate>
                <label class="chat-field">
                    <span>Họ tên *</span>
                    <input type="text" name="guest_name" required maxlength="120" placeholder="Nguyễn Văn A" autocomplete="name">
                </label>
                <label class="chat-field">
                    <span>Số điện thoại</span>
                    <input type="tel" name="guest_phone" maxlength="40" placeholder="09xx xxx xxx" autocomplete="tel">
                </label>
                <label class="chat-field">
                    <span>Email</span>
                    <input type="email" name="guest_email" maxlength="150" placeholder="email@domain.com" autocomplete="email">
                </label>
                <label class="chat-field">
                    <span>Nhu cầu (tuỳ chọn)</span>
                    <textarea name="message" rows="2" maxlength="2000" placeholder="Ví dụ: cần in mô hình rồng, vật liệu PLA..."></textarea>
                </label>
                <p class="chat-lead__hint">Cần ít nhất số điện thoại <em>hoặc</em> email.</p>
                <div class="chat-lead__error" id="chatStartError" hidden></div>
                <button type="submit" class="chat-btn-primary" id="chatStartBtn">Bắt đầu chat</button>
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
