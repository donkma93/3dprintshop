@extends('layouts.shop')

@section('content')
<div class="wrap py-4 pb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('shop.home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active">Tin tức</li>
        </ol>
    </nav>

    <h1 class="page-title mb-4">Tin tức</h1>

    <div class="row g-3 g-lg-4">
        @forelse($posts as $post)
            <div class="col-md-6 col-lg-4">
                <article class="news-card">
                    <a href="{{ route('shop.posts.show', $post->slug) }}" class="media d-block">
                        <img src="{{ $post->image_url }}" alt="{{ $post->title }}" loading="lazy">
                    </a>
                    <div class="date">{{ optional($post->published_at)->format('d/m/Y') }}</div>
                    <h2 class="h6 fw-semibold"><a href="{{ route('shop.posts.show', $post->slug) }}">{{ $post->title }}</a></h2>
                    <p>{{ Str::limit($post->excerpt ?: strip_tags($post->content), 120) }}</p>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="panel text-center muted py-5">Chưa có bài viết.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $posts->links() }}</div>
</div>
@endsection
