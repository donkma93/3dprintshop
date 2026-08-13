@extends('layouts.shop')

@section('content')
<div class="wrap py-4 pb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('shop.home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active">{{ $activeCategory->name ?? 'Sản phẩm' }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="page-title">{{ $activeCategory->name ?? 'Tất cả sản phẩm' }}</h1>
            <p class="muted mb-0 mt-1">{{ $products->total() }} sản phẩm</p>
        </div>
        <form class="search-box" style="width:min(100%,260px)" method="GET" role="search">
            @if($activeCategory)
                <input type="hidden" name="category" value="{{ $activeCategory->slug }}">
            @endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm trong danh mục">
            <button type="submit" aria-label="Tìm"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <div class="row g-4 products-layout">
        <aside class="products-layout__aside">
            <div class="panel filter">
                <div class="filter__title">Danh mục</div>
                <a href="{{ route('shop.products.index', request()->except('category')) }}"
                   class="{{ !$activeCategory ? 'is-active' : '' }}">Tất cả sản phẩm</a>
                @foreach($categories as $category)
                    <a href="{{ route('shop.products.index', array_filter(['category' => $category->slug, 'q' => request('q')])) }}"
                       class="{{ optional($activeCategory)->id === $category->id ? 'is-active' : '' }}"
                       title="{{ $category->name }}">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </aside>

        <div class="products-layout__main">
            @if($activeCategory && $activeCategory->description)
                <div class="panel mb-3 content-html small">{{ $activeCategory->description }}</div>
            @endif

            <div class="row g-3 g-md-4 g-xl-4 stagger product-grid product-grid--catalog">
                @forelse($products as $product)
                    <div class="col-6 col-md-6 col-lg-4">
                        @include('shop.partials.product-card', ['product' => $product])
                    </div>
                @empty
                    <div class="col-12">
                        <div class="panel text-center muted py-5">Không tìm thấy sản phẩm phù hợp.</div>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $products->links() }}</div>
        </div>
    </div>
</div>
@endsection
