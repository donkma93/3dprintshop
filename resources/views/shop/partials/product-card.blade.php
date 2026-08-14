@php
    $badge = $badge ?? null;
    if (!$badge && $product->is_on_sale) {
        $badge = $product->sale_badge;
    }
    if (!$badge && !empty($product->is_featured)) {
        $badge = 'Nổi bật';
    }
@endphp
<article class="product-card {{ $product->is_on_sale ? 'product-card--sale' : '' }}">
    <a href="{{ route('shop.products.show', $product->slug) }}" class="media">
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" width="400" height="400">
        @if($badge)
            <span class="product-card__badge {{ $product->is_on_sale ? 'product-card__badge--sale' : '' }}">{{ $badge }}</span>
        @endif
        <span class="product-card__cta">Xem chi tiết</span>
    </a>
    @if($product->category)
        <div class="cat">{{ $product->category->name }}</div>
    @endif
    <h3 class="name">
        <a href="{{ route('shop.products.show', $product->slug) }}">{{ $product->name }}</a>
    </h3>
    <div class="product-card__foot">
        <div class="price">
            @if($product->is_on_sale)
                <span class="price-old">{{ number_format($product->price, 0, ',', '.') }} đ</span>
                <em>{{ number_format($product->final_price, 0, ',', '.') }}</em> đ
            @else
                <em>{{ number_format($product->final_price, 0, ',', '.') }}</em> đ
            @endif
        </div>
        <div class="product-card__actions d-flex flex-column align-items-stretch align-items-sm-end gap-1">
            <button type="button"
                    class="product-card__link product-card__chat-btn border-0 bg-transparent p-0 text-start text-sm-end"
                    data-chat-product
                    data-product-id="{{ $product->id }}"
                    data-product-name="{{ $product->name }}"
                    data-product-sku="{{ $product->sku }}"
                    data-product-price="{{ number_format($product->final_price, 0, ',', '.') }} đ"
                    data-product-image="{{ $product->image_url }}"
                    data-product-url="{{ route('shop.products.show', $product->slug) }}">
                Chat
            </button>
            <a href="{{ route('shop.products.show', $product->slug) }}#order-form" class="product-card__link text-start text-sm-end">Đặt hàng</a>
        </div>
    </div>
</article>
