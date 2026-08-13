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
                                    <a href="#featured-products" class="btn-ghost-hero">Xem sản phẩm nổi bật</a>
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
    <a href="#product-spotlight" class="hero-scroll-hint" aria-label="Cuộn xuống xem sản phẩm">
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
                    {{ $settings['site_name'] ?? 'Cửa hàng in 3D' }}
                </h1>
                <p style="color:rgba(255,255,255,.75);margin:0 0 1.5rem;max-width:36ch">
                    {{ $settings['site_tagline'] ?? 'Sản phẩm in 3D chất lượng cao' }}
                </p>
                <div class="hero-actions">
                    <a href="{{ route('shop.products.index') }}" class="btn-primary-shop pulse-soft">Xem sản phẩm</a>
                    <a href="#featured-products" class="btn-ghost-hero">Sản phẩm nổi bật</a>
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
<section id="product-spotlight" class="spotlight-rail section pb-0">
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

<section class="benefits-bar reveal-fade">
    <div class="wrap">
        <div class="row g-0 stagger">
            <div class="col-6 col-lg-3">
                <div class="benefit">
                    <i class="bi bi-printer"></i>
                    <div>
                        <strong>In 3D chuyên nghiệp</strong>
                        <p>FDM & Resin chi tiết cao</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="benefit">
                    <i class="bi bi-box-seam"></i>
                    <div>
                        <strong>Nhiều vật liệu</strong>
                        <p>PLA, PETG, ABS, Resin</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="benefit">
                    <i class="bi bi-truck"></i>
                    <div>
                        <strong>Giao hàng toàn quốc</strong>
                        <p>Đóng gói cẩn thận</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="benefit">
                    <i class="bi bi-headset"></i>
                    <div>
                        <strong>Tư vấn tận tâm</strong>
                        <p>Thiết kế theo yêu cầu</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="wrap">
    {{-- Intent paths: khách tự chọn lý do vào shop --}}
    <section class="section intent-paths">
        <div class="section-head reveal">
            <div>
                <h2>Bạn đang tìm gì?</h2>
                <p class="section-sub">Chọn hướng phù hợp — vào thẳng nhóm sản phẩm cần xem</p>
            </div>
        </div>
        <div class="row g-3 stagger">
            <div class="col-6 col-lg-3">
                <a href="{{ route('shop.products.index', ['category' => 'mo-hinh-trang-tri']) }}" class="intent-card intent-card--a">
                    <i class="bi bi-stars"></i>
                    <strong>Trang trí & quà tặng</strong>
                    <span>Rồng mini, tượng, bình hoa</span>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="{{ route('shop.products.index', ['category' => 'phu-kien']) }}" class="intent-card intent-card--b">
                    <i class="bi bi-phone"></i>
                    <strong>Phụ kiện desk</strong>
                    <span>Giá đỡ, móc khóa, setup</span>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="{{ route('shop.products.index', ['category' => 'linh-kien-ky-thuat']) }}" class="intent-card intent-card--c">
                    <i class="bi bi-gear-wide-connected"></i>
                    <strong>Linh kiện kỹ thuật</strong>
                    <span>Bánh răng, prototype</span>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="{{ route('shop.products.index', ['category' => 'do-choi']) }}" class="intent-card intent-card--d">
                    <i class="bi bi-controller"></i>
                    <strong>Đồ chơi & miniature</strong>
                    <span>Robot, mô hình sưu tầm</span>
                </a>
            </div>
        </div>
    </section>

    @if($promos->isNotEmpty())
    <section class="section pb-0 pt-0">
        <div class="row g-3 stagger">
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

    @if($categories->isNotEmpty())
    <section class="section">
        <div class="section-head reveal">
            <h2>Danh mục</h2>
            <a href="{{ route('shop.products.index') }}">Xem tất cả</a>
        </div>
        <div class="row g-2 g-md-3 justify-content-center stagger">
            @foreach($categories as $category)
                <div class="col-4 col-md-3 col-lg-2">
                    <a href="{{ route('shop.products.index', ['category' => $category->slug]) }}" class="cat-pill">
                        <div class="avatar">
                            <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy">
                        </div>
                        <div class="label">{{ $category->name }}</div>
                        <div class="meta">{{ $category->products_count }} SP</div>
                    </a>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    @if(isset($onSale) && $onSale->isNotEmpty())
    <section id="sale-products" class="section section-products {{ $categories->isNotEmpty() ? 'pt-0' : '' }}">
        <div class="surface-soft surface-soft--softgold">
            <div class="section-head">
                <div>
                    <h2>Đang giảm giá</h2>
                    <p class="section-sub">Giá ưu đãi có hạn — đặt sớm để shop giữ mẫu</p>
                </div>
                <a href="{{ route('shop.products.index') }}">Xem catalog</a>
            </div>
            <div class="row g-3 g-lg-4 stagger">
                @foreach($onSale as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        @include('shop.partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @if($featured->isNotEmpty())
    <section id="featured-products" class="section section-products {{ (isset($onSale) && $onSale->isNotEmpty()) || $categories->isNotEmpty() ? 'pt-0' : '' }}">
        <div class="surface-soft">
            <div class="section-head">
                <div>
                    <h2>Sản phẩm nổi bật</h2>
                    <p class="section-sub">Những mẫu được chọn kỹ — dễ bắt mắt ngay lần đầu</p>
                </div>
                <a href="{{ route('shop.products.index') }}">Xem tất cả</a>
            </div>
            <div class="row g-3 g-lg-4 stagger">
                @foreach($featured as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        @include('shop.partials.product-card', ['product' => $product, 'badge' => $product->is_on_sale ? null : 'Hot'])
                    </div>
                @endforeach
            </div>
            <div class="section-cta">
                <a href="{{ route('shop.products.index') }}" class="btn-primary-shop">Xem toàn bộ sản phẩm</a>
            </div>
        </div>
    </section>
    @endif

    @if($affordable->isNotEmpty())
    <section class="section section-products pt-0">
        <div class="surface-soft surface-soft--softgold">
            <div class="section-head">
                <div>
                    <h2>Giá dễ thử</h2>
                    <p class="section-sub">Bắt đầu từ mẫu nhỏ — xem chất lượng in trước khi đặt lớn</p>
                </div>
                <a href="{{ route('shop.products.index') }}">Xem thêm</a>
            </div>
            <div class="row g-3 g-lg-4 stagger">
                @foreach($affordable as $product)
                    <div class="col-6 col-md-4 col-lg-4">
                        @include('shop.partials.product-card', ['product' => $product, 'badge' => 'Dễ thử'])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @if($latest->isNotEmpty())
    <section class="section section-products pt-0">
        <div class="surface-soft">
            <div class="section-head">
                <div>
                    <h2>Sản phẩm mới</h2>
                    <p class="section-sub">Cập nhật gần đây từ xưởng</p>
                </div>
                <a href="{{ route('shop.products.index') }}">Xem tất cả</a>
            </div>
            <div class="row g-3 g-lg-4 stagger">
                @foreach($latest as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        @include('shop.partials.product-card', ['product' => $product, 'badge' => 'Mới'])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Đặt hàng / để lại thông tin liên hệ --}}
    <section id="order-form" class="section pt-0">
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
    <section class="section pt-0">
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
    <section class="section pt-0">
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
    <section class="section section-news pt-0 pb-5">
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
@endsection
