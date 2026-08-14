@extends('layouts.shop')

@section('content')
@if($sliders->isNotEmpty())
<section class="hero">
    <div id="homeSlider" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5500">
        @if($sliders->count() > 1)
        <div class="carousel-indicators">
            @foreach($sliders as $i => $slide)
                <button type="button" data-bs-target="#homeSlider" data-bs-slide-to="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}" aria-label="Slide {{ $i + 1 }}"></button>
            @endforeach
        </div>
        @endif
        <div class="carousel-inner">
            @foreach($sliders as $i => $slide)
                <div class="carousel-item {{ $i === 0 ? 'active' : '' }} {{ $slide->image ? 'has-custom-image' : '' }}">
                    <img src="{{ $slide->image_url }}" alt="{{ $slide->title }}" loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                    <div class="hero-content">
                        <div class="wrap">
                            <div class="inner">
                                <div class="eyebrow">In 3D · Thiết kế · Sáng tạo</div>
                                @if($i === 0)
                                    <h1>{{ $slide->title }}</h1>
                                @else
                                    <h2 class="h1" style="font-size:clamp(1.85rem,3.6vw,2.85rem);font-weight:700;letter-spacing:-.03em;line-height:1.12;margin:0 0 .75rem">{{ $slide->title }}</h2>
                                @endif
                                @if($slide->subtitle)
                                    <p>{{ $slide->subtitle }}</p>
                                @endif
                                <div class="hero-actions">
                                    @if($slide->button_text && $slide->link)
                                        <a href="{{ $slide->link }}" class="btn-primary-shop pulse-soft">{{ $slide->button_text }}</a>
                                    @endif
                                    <a href="#home-products" class="btn-ghost-hero">Xem sản phẩm</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if($sliders->count() > 1)
            <button class="carousel-control-prev" type="button" data-bs-target="#homeSlider" data-bs-slide="prev" aria-label="Trước">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#homeSlider" data-bs-slide="next" aria-label="Sau">
                <span class="carousel-control-next-icon"></span>
            </button>
        @endif
    </div>
    <a href="#home-products" class="hero-scroll-hint" aria-label="Cuộn xuống xem sản phẩm">
        <span>Khám phá sản phẩm</span>
        <i class="bi bi-chevron-down"></i>
    </a>
</section>
@else
<section class="hero-fallback">
    <div class="wrap">
        <div class="hero-content" style="position:relative">
            <div class="inner">
                <div class="eyebrow" style="display:inline-flex;align-items:center;gap:.5rem;font-size:11px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#ffe48a;margin-bottom:.85rem">
                    In 3D · Thiết kế · Sáng tạo
                </div>
                <h1 style="font-size:clamp(1.85rem,3.6vw,2.85rem);font-weight:700;letter-spacing:-.03em;max-width:14ch;margin:0 0 .75rem">
                    {{ $settings['site_name'] ?? 'Shop3DPrinting' }}
                </h1>
                <p style="color:rgba(255,255,255,.75);margin:0 0 1.5rem;max-width:36ch">
                    {{ $settings['site_tagline'] ?? 'Tận tâm - từ tấm lòng' }}
                </p>
                <div class="hero-actions">
                    <a href="{{ route('shop.products.index') }}" class="btn-primary-shop pulse-soft">Xem sản phẩm</a>
                    <a href="#home-products" class="btn-ghost-hero">Xem sản phẩm</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- Trust strip: số liệu ngắn gọn --}}
<section class="trust-strip reveal-fade">
    <div class="wrap">
        <div class="trust-strip__grid">
            <div class="trust-item">
                <strong>{{ $stats['products'] }}+</strong>
                <span>Mẫu sẵn sàng</span>
            </div>
            <div class="trust-item">
                <strong>{{ $stats['categories'] }}</strong>
                <span>Danh mục chính</span>
            </div>
            <div class="trust-item">
                <strong>{{ $stats['lead_time'] }}</strong>
                <span>Thời gian in TB</span>
            </div>
            <div class="trust-item">
                <strong>{{ $stats['materials'] }}</strong>
                <span>Vật liệu phổ biến</span>
            </div>
        </div>
    </div>
</section>

{{-- Lộ sản phẩm ngay: dải ảnh ngang có thể cuộn --}}
@if($spotlight->isNotEmpty())
<section id="product-spotlight" class="spotlight-rail section">
    <div class="wrap">
        <div class="section-head reveal">
            <div>
                <h2>Mới về — chạm để xem</h2>
                <p class="section-sub">Vuốt ngang hoặc bấm vào ảnh để mở chi tiết sản phẩm</p>
            </div>
            <a href="{{ route('shop.products.index') }}">Tất cả sản phẩm</a>
        </div>
    </div>
    <div class="spotlight-rail__track" tabindex="0" aria-label="Dải sản phẩm nổi bật">
        @foreach($spotlight as $product)
            <a href="{{ route('shop.products.show', $product->slug) }}" class="spotlight-card">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                <div class="spotlight-card__meta">
                    <span class="spotlight-card__name">{{ $product->name }}</span>
                    <span class="spotlight-card__price">
                        @if($product->is_on_sale)
                            <span class="price-old" style="opacity:.75;text-decoration:line-through;margin-right:.35rem;font-size:.8em">{{ number_format($product->price, 0, ',', '.') }}</span>
                        @endif
                        {{ number_format($product->final_price, 0, ',', '.') }} đ
                    </span>
                </div>
            </a>
        @endforeach
        <a href="{{ route('shop.products.index') }}" class="spotlight-card spotlight-card--more">
            <span>Xem toàn bộ<br>catalog</span>
            <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</section>
@endif

<section class="benefits-bar reveal-fade" aria-label="Cam kết dịch vụ">
    <div class="wrap">
        <div class="benefits-grid stagger">
            <article class="benefit benefit--print">
                <span class="benefit__icon" aria-hidden="true">
                    <i class="bi bi-printer-fill"></i>
                </span>
                <div class="benefit__body">
                    <strong>In 3D chuyên nghiệp</strong>
                    <p>FDM &amp; Resin — chi tiết sắc nét, bề mặt mịn</p>
                </div>
            </article>
            <article class="benefit benefit--material">
                <span class="benefit__icon" aria-hidden="true">
                    <i class="bi bi-layers-fill"></i>
                </span>
                <div class="benefit__body">
                    <strong>Nhiều vật liệu</strong>
                    <p>PLA · PETG · ABS · Resin theo nhu cầu</p>
                </div>
            </article>
            <article class="benefit benefit--ship">
                <span class="benefit__icon" aria-hidden="true">
                    <i class="bi bi-truck"></i>
                </span>
                <div class="benefit__body">
                    <strong>Giao hàng toàn quốc</strong>
                    <p>Đóng gói chắc chắn, theo dõi đơn dễ dàng</p>
                </div>
            </article>
            <article class="benefit benefit--care">
                <span class="benefit__icon" aria-hidden="true">
                    <i class="bi bi-chat-heart-fill"></i>
                </span>
                <div class="benefit__body">
                    <strong>Tư vấn tận tâm</strong>
                    <p>Thiết kế theo yêu cầu — chat chốt đơn nhanh</p>
                </div>
            </article>
        </div>
    </div>
</section>

<div class="wrap">
    @if($promos->isNotEmpty())
    <section class="section section--promos">
        <div class="row g-2 g-md-3 stagger">
            @foreach($promos as $promo)
                <div class="col-md-4">
                    <a href="{{ $promo->link ?: route('shop.products.index') }}" class="promo-tile">
                        <img src="{{ $promo->image_url }}" alt="{{ $promo->title }}" loading="lazy">
                        <span>{{ $promo->title }}</span>
                    </a>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    @php
        $productTabs = collect([
            [
                'id' => 'sale',
                'label' => 'Đang giảm giá',
                'icon' => 'bi-lightning-charge-fill',
                'sub' => 'Giá ưu đãi có hạn — đặt sớm để shop giữ mẫu',
                'items' => $onSale ?? collect(),
                'badge' => null,
                'col' => 'col-6 col-md-4 col-lg-3',
                'anchor' => 'sale-products',
            ],
            [
                'id' => 'featured',
                'label' => 'Nổi bật',
                'icon' => 'bi-stars',
                'sub' => 'Những mẫu được chọn kỹ — dễ bắt mắt ngay lần đầu',
                'items' => $featured ?? collect(),
                'badge' => 'Hot',
                'col' => 'col-6 col-md-4 col-lg-3',
                'anchor' => 'featured-products',
            ],
            [
                'id' => 'affordable',
                'label' => 'Giá dễ thử',
                'icon' => 'bi-tag-fill',
                'sub' => 'Bắt đầu từ mẫu nhỏ — xem chất lượng in trước khi đặt lớn',
                'items' => $affordable ?? collect(),
                'badge' => 'Dễ thử',
                'col' => 'col-6 col-md-4 col-lg-3',
                'anchor' => null,
            ],
            [
                'id' => 'latest',
                'label' => 'Sản phẩm mới',
                'icon' => 'bi-clock-history',
                'sub' => 'Cập nhật gần đây từ xưởng',
                'items' => $latest ?? collect(),
                'badge' => 'Mới',
                'col' => 'col-6 col-md-4 col-lg-3',
                'anchor' => null,
            ],
        ])->filter(fn ($tab) => $tab['items']->isNotEmpty())->values();
        $activeProductTab = $productTabs->first();
    @endphp

    @if($productTabs->isNotEmpty())
    <section id="home-products" class="section section-products">
        <div class="surface-soft product-tabs" id="productTabs" data-product-tabs>
            <div class="section-head product-tabs__head">
                <div>
                    <h2>Sản phẩm</h2>
                    <p class="section-sub" id="productTabsSub">{{ $activeProductTab['sub'] }}</p>
                </div>
                <a href="{{ route('shop.products.index') }}">Xem catalog</a>
            </div>

            <div class="product-tabs__nav" role="tablist" aria-label="Nhóm sản phẩm">
                @foreach($productTabs as $i => $tab)
                    <button
                        type="button"
                        class="product-tabs__btn {{ $i === 0 ? 'is-active' : '' }}"
                        role="tab"
                        id="product-tab-{{ $tab['id'] }}"
                        aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                        aria-controls="product-panel-{{ $tab['id'] }}"
                        data-tab="{{ $tab['id'] }}"
                        data-sub="{{ $tab['sub'] }}"
                        @if($tab['anchor']) data-anchors="{{ $tab['anchor'] }}" @endif
                    >
                        <i class="bi {{ $tab['icon'] }}" aria-hidden="true"></i>
                        <span class="product-tabs__btn-label">{{ $tab['label'] }}</span>
                        <span class="product-tabs__count">{{ $tab['items']->count() }}</span>
                    </button>
                @endforeach
            </div>

            <div class="product-tabs__panels">
                @foreach($productTabs as $i => $tab)
                    <div
                        class="product-tabs__panel {{ $i === 0 ? 'is-active' : '' }}"
                        role="tabpanel"
                        id="product-panel-{{ $tab['id'] }}"
                        aria-labelledby="product-tab-{{ $tab['id'] }}"
                        @if($tab['anchor']) data-panel-anchor="{{ $tab['anchor'] }}" @endif
                        @if($i !== 0) hidden @endif
                    >
                        <div class="row g-3 g-lg-4 stagger">
                            @foreach($tab['items'] as $product)
                                <div class="{{ $tab['col'] }}">
                                    @php
                                        $cardBadge = $tab['badge'];
                                        if ($tab['id'] === 'featured' && $product->is_on_sale) {
                                            $cardBadge = null;
                                        }
                                    @endphp
                                    @include('shop.partials.product-card', ['product' => $product, 'badge' => $cardBadge])
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="section-cta">
                <a href="{{ route('shop.products.index') }}" class="btn-primary-shop">Xem toàn bộ sản phẩm</a>
            </div>
        </div>
    </section>
    @endif

    {{-- Đặt hàng / để lại thông tin liên hệ --}}
    <section id="order-form" class="section">
        <div class="order-lead-home reveal">
            <div class="row g-4 align-items-start">
                <div class="col-lg-5">
                    <div class="eyebrow" style="font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#8a6b14;margin-bottom:.65rem">Đặt hàng nhanh</div>
                    <h2 class="mb-2" style="font-size:clamp(1.35rem,2.4vw,1.75rem);font-weight:700;letter-spacing:-.02em">Để lại thông tin — shop gọi lại</h2>
                    <p class="text-secondary mb-3" style="max-width:36ch">Không cần thanh toán online. Chọn sản phẩm (tuỳ chọn), ghi SĐT — nhân viên xác nhận đơn và báo giá giao.</p>
                    <ul class="order-lead-home__bullets">
                        <li><i class="bi bi-check2-circle"></i> Phản hồi nhanh trong giờ làm việc</li>
                        <li><i class="bi bi-check2-circle"></i> Tư vấn vật liệu &amp; số lượng</li>
                        <li><i class="bi bi-check2-circle"></i> Giữ mẫu khi đang giảm giá</li>
                    </ul>
                    @if(!empty($settings['hotline'] ?? $settings['phone'] ?? null))
                        <a href="tel:{{ preg_replace('/\s+/', '', $settings['hotline'] ?? $settings['phone']) }}" class="btn-secondary-shop mt-2">
                            <i class="bi bi-telephone"></i> {{ $settings['hotline'] ?? $settings['phone'] }}
                        </a>
                    @endif
                </div>
                <div class="col-lg-7">
                    <form action="{{ route('shop.orders.store') }}" method="POST" class="order-lead-home__form">
                        @csrf
                        <input type="hidden" name="source" value="home">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}" required maxlength="120" placeholder="Nguyễn Văn A">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="tel" name="customer_phone" class="form-control" value="{{ old('customer_phone') }}" required maxlength="40" placeholder="09xx xxx xxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email') }}" maxlength="190" placeholder="tuỳ chọn">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sản phẩm quan tâm</label>
                                <select name="product_id" class="form-select">
                                    <option value="">— Chưa chọn / tư vấn chung —</option>
                                    @foreach($orderProducts ?? [] as $op)
                                        <option value="{{ $op->id }}" @selected(old('product_id') == $op->id)>
                                            {{ $op->name }}
                                            @if($op->is_on_sale)
                                                ({{ number_format($op->final_price, 0, ',', '.') }}đ · Sale)
                                            @else
                                                ({{ number_format($op->final_price, 0, ',', '.') }}đ)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Số lượng</label>
                                <input type="number" name="quantity" class="form-control" value="{{ old('quantity', 1) }}" min="1" max="999">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Địa chỉ / ghi chú</label>
                                <input type="text" name="note" class="form-control" value="{{ old('note') }}" maxlength="2000" placeholder="Địa chỉ giao, màu, yêu cầu...">
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                                <button type="submit" class="btn-primary-shop pulse-soft">Gửi yêu cầu đặt hàng</button>
                                <span class="small text-secondary">Shop sẽ liên hệ lại qua SĐT của bạn</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA giữa trang --}}
    <section class="section">
        <div class="engage-cta reveal-zoom">
            <div class="engage-cta__text">
                <h2>Chưa biết chọn mẫu nào?</h2>
                <p>Lọc theo danh mục, xem giá khuyến mãi, hoặc để lại SĐT để được gợi ý mô hình phù hợp.</p>
            </div>
            <div class="engage-cta__actions">
                <a href="{{ route('shop.products.index') }}" class="btn-primary-shop">Mở catalog</a>
                <a href="#order-form" class="btn-secondary-shop">
                    <i class="bi bi-person-lines-fill"></i> Để lại liên hệ
                </a>
            </div>
        </div>
    </section>

    @if(!empty($settings['home_about_content']))
    <section class="section">
        <div class="showcase-band">
            <div class="gold-line"></div>
            <h2>{{ $settings['home_about_title'] ?? 'Giới thiệu' }}</h2>
            <div class="content-html" style="color:rgba(255,255,255,.78)">{!! nl2br(e($settings['home_about_content'])) !!}</div>
            <div class="mt-4">
                <a href="{{ route('shop.products.index') }}" class="btn-primary-shop pulse-soft">Khám phá sản phẩm</a>
            </div>
        </div>
    </section>
    @endif

    @if($posts->isNotEmpty())
    <section class="section section-news">
        <div class="surface-soft">
            <div class="section-head">
                <div>
                    <h2>Góc mô hình in 3D</h2>
                    <p class="section-sub">Đọc nhanh — mỗi bài dẫn về một nhóm sản phẩm cụ thể</p>
                </div>
                <a href="{{ route('shop.posts.index') }}">Xem tất cả bài viết</a>
            </div>
            <div class="row g-3 g-lg-4 stagger">
                @foreach($posts as $post)
                    <div class="col-md-6 col-lg-4">
                        <article class="news-card">
                            <a href="{{ route('shop.posts.show', $post->slug) }}" class="media d-block">
                                <img src="{{ $post->image_url }}" alt="{{ $post->title }}" loading="lazy">
                            </a>
                            <div class="date">{{ optional($post->published_at)->format('d/m/Y') }}</div>
                            <h3><a href="{{ route('shop.posts.show', $post->slug) }}">{{ $post->title }}</a></h3>
                            <p>{{ Str::limit($post->excerpt ?: strip_tags($post->content), 110) }}</p>
                        </article>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
</div>

{{-- Nút nổi: luôn có đường vào sản phẩm --}}
<div class="float-engage" id="floatEngage">
    <a href="#order-form" class="float-engage__btn">
        <i class="bi bi-bag-plus"></i>
        <span>Đặt hàng</span>
    </a>
    <a href="{{ route('shop.products.index') }}" class="float-engage__btn" style="background:#0f172a">
        <i class="bi bi-grid-3x3-gap"></i>
        <span>Sản phẩm</span>
    </a>
    <button type="button" class="float-engage__top" id="backToTop" aria-label="Lên đầu trang" hidden>
        <i class="bi bi-arrow-up"></i>
    </button>
</div>

@php
    // Gợi ý popup: ưu tiên sale → nổi bật → mới, unique theo id, tối đa 4
    $welcomePool = collect()
        ->merge($onSale ?? [])
        ->merge($featured ?? [])
        ->merge($latest ?? [])
        ->unique('id')
        ->take(4)
        ->values();
@endphp

{{-- Popup gợi ý sản phẩm: lần đầu / sau 3 giờ (localStorage) --}}
@if($welcomePool->isNotEmpty())
<div class="welcome-popup" id="welcomePopup" hidden role="dialog" aria-modal="true" aria-labelledby="welcomePopupTitle" aria-describedby="welcomePopupSub">
    <div class="welcome-popup__backdrop" id="welcomePopupBackdrop" data-welcome-dismiss></div>
    <div class="welcome-popup__panel">
        <button type="button" class="welcome-popup__close" id="welcomePopupClose" aria-label="Đóng" data-welcome-dismiss>
            <i class="bi bi-x-lg"></i>
        </button>
        <div class="welcome-popup__head">
            <span class="welcome-popup__badge"><i class="bi bi-compass" aria-hidden="true"></i> Cùng thăm quan web</span>
            <h2 id="welcomePopupTitle">Gợi ý sản phẩm cho bạn</h2>
            <p id="welcomePopupSub">Chọn nhóm / mẫu yêu thích — shop sẵn sàng tư vấn và chốt đơn khi bạn cần.</p>
        </div>
        <div class="welcome-popup__grid">
            @foreach($welcomePool as $product)
                <a href="{{ route('shop.products.show', $product->slug) }}" class="welcome-popup__card">
                    <span class="welcome-popup__thumb">
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" width="96" height="96">
                    </span>
                    <span class="welcome-popup__meta">
                        <strong>{{ $product->name }}</strong>
                        <em>
                            @if($product->is_on_sale)
                                <span class="welcome-popup__old">{{ number_format($product->price, 0, ',', '.') }}</span>
                            @endif
                            {{ number_format($product->final_price, 0, ',', '.') }} đ
                        </em>
                    </span>
                </a>
            @endforeach
        </div>
        <div class="welcome-popup__paths">
            <a href="{{ route('shop.products.index', ['category' => 'mo-hinh-trang-tri']) }}" class="welcome-popup__chip"><i class="bi bi-stars"></i> Trang trí</a>
            <a href="{{ route('shop.products.index', ['category' => 'phu-kien']) }}" class="welcome-popup__chip"><i class="bi bi-phone"></i> Phụ kiện</a>
            <a href="{{ route('shop.products.index', ['category' => 'linh-kien-ky-thuat']) }}" class="welcome-popup__chip"><i class="bi bi-gear-wide-connected"></i> Kỹ thuật</a>
            <a href="{{ route('shop.products.index', ['category' => 'do-choi']) }}" class="welcome-popup__chip"><i class="bi bi-controller"></i> Đồ chơi</a>
        </div>
        <div class="welcome-popup__actions">
            <button type="button" class="welcome-popup__later" data-welcome-dismiss>Để sau</button>
            <a href="#home-products" class="btn-primary-shop welcome-popup__cta" id="welcomePopupCta">Xem sản phẩm</a>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
    /* Welcome product popup — always viewport-centered (portaled to body) */
    .welcome-popup {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        height: 100dvh !important;
        margin: 0 !important;
        z-index: 10050 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 1.25rem;
        box-sizing: border-box;
        pointer-events: auto;
    }
    .welcome-popup[hidden] { display: none !important; }
    .welcome-popup__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, .52);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }
    .welcome-popup__panel {
        position: relative;
        z-index: 1;
        width: min(720px, calc(100vw - 2.5rem));
        max-height: min(90vh, 860px);
        max-height: min(90dvh, 860px);
        overflow: auto;
        margin: 0 auto;
        background: #fff;
        border-radius: 24px;
        border: 1px solid rgba(201, 162, 39, .3);
        box-shadow: 0 32px 80px rgba(15, 23, 42, .32);
        padding: 1.65rem 1.75rem 1.5rem;
        animation: welcomePopIn .28s ease;
        flex-shrink: 0;
    }
    @keyframes welcomePopIn {
        from { opacity: 0; transform: translateY(12px) scale(.97); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .welcome-popup__close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        width: 40px;
        height: 40px;
        border: 0;
        border-radius: 50%;
        background: #f1f5f9;
        color: #475569;
        font-size: 1.05rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .welcome-popup__close:hover { background: #e2e8f0; color: #0f172a; }
    .welcome-popup__head { padding-right: 2.5rem; margin-bottom: 1.15rem; }
    .welcome-popup__badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #8a6b14;
        background: rgba(201, 162, 39, .14);
        border-radius: 999px;
        padding: .35rem .75rem;
        margin-bottom: .65rem;
    }
    .welcome-popup__head h2 {
        margin: 0 0 .45rem;
        font-size: clamp(1.35rem, 2.4vw, 1.65rem);
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -.02em;
    }
    .welcome-popup__head p {
        margin: 0;
        font-size: .95rem;
        color: #64748b;
        line-height: 1.5;
        max-width: 42ch;
    }
    .welcome-popup__grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    .welcome-popup__card {
        display: flex;
        gap: .75rem;
        align-items: center;
        padding: .75rem;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        text-decoration: none;
        color: inherit;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        min-height: 88px;
    }
    .welcome-popup__card:hover {
        border-color: rgba(201, 162, 39, .55);
        box-shadow: 0 10px 22px rgba(120, 90, 20, .12);
        transform: translateY(-2px);
        color: inherit;
    }
    .welcome-popup__thumb {
        width: 76px;
        height: 76px;
        border-radius: 12px;
        overflow: hidden;
        flex-shrink: 0;
        background: #e2e8f0;
    }
    .welcome-popup__thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .welcome-popup__meta {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: .25rem;
    }
    .welcome-popup__meta strong {
        font-size: .95rem;
        font-weight: 700;
        line-height: 1.3;
        color: #0f172a;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .welcome-popup__meta em {
        font-style: normal;
        font-size: .92rem;
        font-weight: 800;
        color: #a8841a;
    }
    .welcome-popup__old {
        text-decoration: line-through;
        opacity: .55;
        font-weight: 600;
        margin-right: .3rem;
        color: #94a3b8;
        font-size: .85rem;
    }
    .welcome-popup__paths {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: 1.25rem;
    }
    .welcome-popup__chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .5rem .85rem;
        border-radius: 999px;
        background: #fff;
        border: 1px solid rgba(201, 162, 39, .28);
        color: #475569;
        font-size: .84rem;
        font-weight: 700;
        text-decoration: none;
    }
    .welcome-popup__chip:hover {
        background: rgba(201, 162, 39, .1);
        color: #0f172a;
        border-color: rgba(201, 162, 39, .5);
    }
    .welcome-popup__actions {
        display: flex;
        gap: .7rem;
        justify-content: flex-end;
        align-items: center;
    }
    .welcome-popup__later {
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        border-radius: 999px;
        padding: .7rem 1.2rem;
        font-size: .92rem;
        font-weight: 700;
        cursor: pointer;
    }
    .welcome-popup__later:hover { background: #f8fafc; color: #0f172a; }
    .welcome-popup__cta {
        text-decoration: none;
        padding: .75rem 1.35rem !important;
        font-size: .95rem !important;
    }
    body.welcome-popup-open {
        overflow: hidden !important;
        touch-action: none;
    }
    @media (min-width: 992px) {
        .welcome-popup__panel {
            width: min(800px, calc(100vw - 2.5rem));
            padding: 1.85rem 2rem 1.65rem;
        }
        .welcome-popup__thumb { width: 84px; height: 84px; }
        .welcome-popup__card { min-height: 100px; padding: .85rem; }
    }
    @media (max-width: 575.98px) {
        .welcome-popup { padding: .65rem; }
        .welcome-popup__grid { grid-template-columns: 1fr; gap: .65rem; }
        .welcome-popup__panel {
            width: min(100%, calc(100vw - 1.3rem));
            max-height: min(88dvh, 92vh);
            padding: 1.2rem 1rem 1.15rem;
            border-radius: 18px;
        }
        .welcome-popup__thumb { width: 68px; height: 68px; }
        .welcome-popup__actions { flex-direction: column-reverse; align-items: stretch; }
        .welcome-popup__cta { text-align: center; }
    }

    .product-tabs__head { margin-bottom: .75rem; }
    .product-tabs__nav {
        display: flex;
        flex-wrap: nowrap;
        gap: .5rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding: .15rem 0 .85rem;
        margin: 0 0 .35rem;
        border-bottom: 1px solid rgba(201, 162, 39, .18);
    }
    .product-tabs__nav::-webkit-scrollbar { display: none; }
    .product-tabs__btn {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        flex: 0 0 auto;
        border: 1px solid rgba(201, 162, 39, .22);
        background: #fff;
        color: #475569;
        border-radius: 999px;
        padding: .55rem .9rem;
        font-size: .84rem;
        font-weight: 700;
        cursor: pointer;
        transition: background .18s ease, color .18s ease, border-color .18s ease, box-shadow .18s ease, transform .15s ease;
        white-space: nowrap;
        -webkit-tap-highlight-color: transparent;
    }
    .product-tabs__btn i { font-size: 1rem; opacity: .9; }
    .product-tabs__btn:hover {
        border-color: rgba(201, 162, 39, .45);
        color: #0f172a;
        transform: translateY(-1px);
    }
    .product-tabs__btn.is-active {
        background: linear-gradient(160deg, #f0d878, #c9a227 55%, #a8841a);
        border-color: transparent;
        color: #1a1408;
        box-shadow: 0 8px 18px rgba(120, 90, 20, .22);
    }
    .product-tabs__count {
        min-width: 1.35rem;
        height: 1.35rem;
        padding: 0 .35rem;
        border-radius: 999px;
        background: rgba(15, 23, 42, .08);
        font-size: .72rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .product-tabs__btn.is-active .product-tabs__count {
        background: rgba(26, 20, 8, .14);
        color: #1a1408;
    }
    .product-tabs__panel[hidden] { display: none !important; }
    .product-tabs__panel.is-active {
        animation: productTabIn .28s ease;
    }
    @keyframes productTabIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @media (max-width: 575.98px) {
        .product-tabs__btn {
            padding: .5rem .7rem;
            font-size: .78rem;
            gap: .3rem;
        }
        .product-tabs__btn-label { max-width: 9.5ch; overflow: hidden; text-overflow: ellipsis; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-product-tabs]');
    if (!root) return;

    var nav = root.querySelector('.product-tabs__nav');
    var buttons = Array.prototype.slice.call(root.querySelectorAll('.product-tabs__btn'));
    var panels = Array.prototype.slice.call(root.querySelectorAll('.product-tabs__panel'));
    var subEl = document.getElementById('productTabsSub');

    function activate(tabId, opts) {
        opts = opts || {};
        var btn = buttons.find(function (b) { return b.getAttribute('data-tab') === tabId; });
        var panel = panels.find(function (p) { return p.id === 'product-panel-' + tabId; });
        if (!btn || !panel) return;

        buttons.forEach(function (b) {
            var on = b === btn;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
            b.tabIndex = on ? 0 : -1;
        });
        panels.forEach(function (p) {
            var on = p === panel;
            p.classList.toggle('is-active', on);
            if (on) p.removeAttribute('hidden');
            else p.setAttribute('hidden', 'hidden');
        });
        if (subEl) {
            subEl.textContent = btn.getAttribute('data-sub') || '';
        }
        if (opts.scrollIntoView) {
            root.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        if (btn && typeof btn.scrollIntoView === 'function') {
            btn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activate(btn.getAttribute('data-tab'));
        });
        btn.addEventListener('keydown', function (e) {
            var idx = buttons.indexOf(btn);
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                var next = buttons[(idx + 1) % buttons.length];
                next.focus();
                activate(next.getAttribute('data-tab'));
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                var prev = buttons[(idx - 1 + buttons.length) % buttons.length];
                prev.focus();
                activate(prev.getAttribute('data-tab'));
            } else if (e.key === 'Home') {
                e.preventDefault();
                buttons[0].focus();
                activate(buttons[0].getAttribute('data-tab'));
            } else if (e.key === 'End') {
                e.preventDefault();
                buttons[buttons.length - 1].focus();
                activate(buttons[buttons.length - 1].getAttribute('data-tab'));
            }
        });
    });

    // Deep-link: #sale-products / #featured-products / #home-products
    function fromHash() {
        var hash = (location.hash || '').replace(/^#/, '');
        if (!hash) return;
        if (hash === 'home-products') {
            root.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        var byAnchor = buttons.find(function (b) {
            var a = (b.getAttribute('data-anchors') || '').split(',').map(function (s) { return s.trim(); });
            return a.indexOf(hash) !== -1;
        });
        if (byAnchor) {
            activate(byAnchor.getAttribute('data-tab'), { scrollIntoView: true });
        }
    }
    fromHash();
    window.addEventListener('hashchange', fromHash);
})();

/* Welcome product popup — first visit / every 3 hours (same spirit as chat proactive) */
(function () {
    var popup = document.getElementById('welcomePopup');
    if (!popup) return;

    // Detach from <main> so position:fixed always centers on the viewport
    if (popup.parentElement !== document.body) {
        document.body.appendChild(popup);
    }

    var KEY = 'shop_welcome_popup_dismiss_at';
    var COOLDOWN_MS = 3 * 60 * 60 * 1000; // 3 giờ
    var showTimer = null;
    var lastFocus = null;

    function isDismissed() {
        try {
            var until = Number(localStorage.getItem(KEY) || 0) || 0;
            return until > Date.now();
        } catch (e) {
            return false;
        }
    }

    function dismiss(hours) {
        hide();
        try {
            var until = Date.now() + (Number(hours) || 3) * 3600 * 1000;
            localStorage.setItem(KEY, String(until));
        } catch (e) {}
    }

    function hide() {
        popup.setAttribute('hidden', 'hidden');
        document.body.classList.remove('welcome-popup-open');
        if (lastFocus && typeof lastFocus.focus === 'function') {
            try { lastFocus.focus(); } catch (e) {}
        }
        document.removeEventListener('keydown', onKey);
    }

    function show() {
        if (isDismissed()) return;
        // Không đè chat widget nếu đang mở
        var chat = document.getElementById('chatWidget');
        if (chat && !chat.hasAttribute('hidden')) return;

        if (popup.parentElement !== document.body) {
            document.body.appendChild(popup);
        }

        lastFocus = document.activeElement;
        popup.removeAttribute('hidden');
        document.body.classList.add('welcome-popup-open');
        var closeBtn = document.getElementById('welcomePopupClose');
        if (closeBtn) closeBtn.focus();
        document.addEventListener('keydown', onKey);
    }

    function onKey(e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            dismiss(3);
        }
    }

    popup.querySelectorAll('[data-welcome-dismiss]').forEach(function (el) {
        el.addEventListener('click', function () {
            dismiss(3);
        });
    });

    var cta = document.getElementById('welcomePopupCta');
    cta?.addEventListener('click', function () {
        dismiss(3);
    });

    // Click product / chip: still remember dismiss so reopening page soon won't re-spam
    popup.querySelectorAll('.welcome-popup__card, .welcome-popup__chip').forEach(function (el) {
        el.addEventListener('click', function () {
            try {
                localStorage.setItem(KEY, String(Date.now() + COOLDOWN_MS));
            } catch (e) {}
        });
    });

    // Hiện sau 1.2s khi vào trang (chỉ homepage có popup này)
    if (!isDismissed()) {
        showTimer = setTimeout(show, 1200);
    }

    window.addEventListener('beforeunload', function () {
        if (showTimer) clearTimeout(showTimer);
    });
})();
</script>
@endpush
