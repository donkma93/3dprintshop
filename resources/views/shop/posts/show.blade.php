@extends('layouts.shop')

@section('content')
<div class="wrap py-4 pb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('shop.home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="{{ route('shop.posts.index') }}">Tin tức</a></li>
            <li class="breadcrumb-item active">{{ $post->title }}</li>
        </ol>
    </nav>

    <article class="panel mb-5" style="max-width:820px">
        <div class="muted small mb-2">{{ optional($post->published_at)->format('d/m/Y H:i') }}</div>
        <h1 class="page-title mb-3">{{ $post->title }}</h1>
        @if($post->excerpt)
            <p class="lead text-secondary" style="font-size:1.05rem">{{ $post->excerpt }}</p>
        @endif
        @if($post->image)
            <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="w-100 rounded mb-4" style="max-height:420px;object-fit:cover">
        @endif
        <div class="content-html">{!! nl2br(e($post->content)) !!}</div>
    </article>

    @if($related->isNotEmpty())
    <section>
        <div class="section-head">
            <h2>Bài viết khác</h2>
        </div>
        <div class="row g-3 g-lg-4">
            @foreach($related as $item)
                <div class="col-md-6 col-lg-3">
                    <article class="news-card">
                        <a href="{{ route('shop.posts.show', $item->slug) }}" class="media d-block">
                            <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy">
                        </a>
                        <h3 class="h6 fw-semibold mb-0"><a href="{{ route('shop.posts.show', $item->slug) }}">{{ $item->title }}</a></h3>
                    </article>
                </div>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
