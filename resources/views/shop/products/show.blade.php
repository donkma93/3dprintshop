@extends('layouts.shop')

@section('content')
<div class="wrap py-4 pb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('shop.home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="{{ route('shop.products.index') }}">Sản phẩm</a></li>
            @if($product->category)
                <li class="breadcrumb-item">
                    <a href="{{ route('shop.products.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a>
                </li>
            @endif
            <li class="breadcrumb-item active">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="row g-4 g-lg-5 mb-5 product-detail">
        <div class="col-lg-6">
            <div class="product-detail__media position-relative">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                @if($product->is_on_sale)
                    <span class="product-card__badge product-card__badge--sale" style="position:absolute;top:1rem;left:1rem">{{ $product->sale_badge }}</span>
                @endif
            </div>
        </div>
        <div class="col-lg-6">
            @if($product->category)
                <div class="muted small mb-2">{{ $product->category->name }}</div>
            @endif
            <h1 class="page-title product-detail__title mb-3">{{ $product->name }}</h1>
            <div class="price-lg mb-3">
                @if($product->is_on_sale)
                    <span class="price-old d-inline-block me-2" style="font-size:1rem;font-weight:500">{{ number_format($product->price, 0, ',', '.') }} đ</span>
                    <span class="text-danger">{{ number_format($product->final_price, 0, ',', '.') }} đ</span>
                    <span class="badge text-bg-danger ms-1 align-middle">-{{ $product->discount_percent }}%</span>
                @else
                    {{ number_format($product->final_price, 0, ',', '.') }} đ
                @endif
            </div>

            @if($product->short_description)
                <p class="text-secondary mb-4">{{ $product->short_description }}</p>
            @endif

            <ul class="specs">
                @if($product->sku)<li><span>Mã SP</span><span>{{ $product->sku }}</span></li>@endif
                @if($product->material_used)<li><span>Vật liệu</span><span>{{ $product->material_used }}</span></li>@endif
                @if($product->weight_grams)<li><span>Khối lượng</span><span>{{ $product->weight_grams }} g</span></li>@endif
                <li>
                    <span>Tình trạng</span>
                    <span>{{ $product->stock > 0 ? 'Còn hàng ('.$product->stock.')' : 'Hết hàng' }}</span>
                </li>
            </ul>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="#order-form" class="btn-primary-shop">
                    <i class="bi bi-cart-plus"></i> Đặt hàng / để lại SĐT
                </a>
                @if(!empty($settings['hotline'] ?? $settings['phone'] ?? null))
                <a href="tel:{{ preg_replace('/\s+/', '', $settings['hotline'] ?? $settings['phone'] ?? '') }}" class="btn-secondary-shop">
                    <i class="bi bi-telephone"></i> Gọi đặt hàng
                </a>
                @endif
                <a href="{{ route('shop.products.index') }}" class="btn-secondary-shop">Xem thêm sản phẩm</a>
            </div>
        </div>
    </div>

    <div id="order-form" class="panel mb-5 order-lead-panel">
        <h2 class="page-title mb-2" style="font-size:1.15rem">Đặt hàng — shop sẽ liên hệ lại</h2>
        <p class="text-secondary small mb-3">Điền thông tin liên hệ. Không cần thanh toán online — nhân viên gọi xác nhận đơn.</p>
        <form action="{{ route('shop.orders.store') }}" method="POST" class="row g-3">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="source" value="product">
            <div class="col-md-6">
                <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}" required maxlength="120">
            </div>
            <div class="col-md-6">
                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                <input type="tel" name="customer_phone" class="form-control" value="{{ old('customer_phone') }}" required maxlength="40" placeholder="09xx...">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email') }}" maxlength="190">
            </div>
            <div class="col-md-3">
                <label class="form-label">Số lượng</label>
                <input type="number" name="quantity" class="form-control" value="{{ old('quantity', 1) }}" min="1" max="999">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="small text-secondary w-100 pb-2">
                    SP: <strong>{{ $product->name }}</strong>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Địa chỉ / ghi chú</label>
                <textarea name="note" rows="2" class="form-control" maxlength="2000" placeholder="Địa chỉ giao, màu sắc, yêu cầu in...">{{ old('note') }}</textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="btn-primary-shop">Gửi yêu cầu đặt hàng</button>
            </div>
        </form>
    </div>

    @if($product->description)
    <div class="panel mb-5">
        <h2 class="page-title mb-3" style="font-size:1.1rem">Mô tả sản phẩm</h2>
        <div class="content-html">{!! nl2br(e($product->description)) !!}</div>
    </div>
    @endif

    @if($related->isNotEmpty())
    <section>
        <div class="section-head">
            <h2>Sản phẩm liên quan</h2>
        </div>
        <div class="row g-3 g-md-4 product-grid product-grid--related">
            @foreach($related as $item)
                <div class="col-6 col-md-6 col-lg-4">
                    @include('shop.partials.product-card', ['product' => $item])
                </div>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
