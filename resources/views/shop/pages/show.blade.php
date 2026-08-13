@extends('layouts.shop')

@section('content')
<div class="wrap py-4 pb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('shop.home') }}">Trang chủ</a></li>
            <li class="breadcrumb-item active">{{ $page->title }}</li>
        </ol>
    </nav>

    <article class="panel" style="max-width:860px">
        <h1 class="page-title mb-4">{{ $page->title }}</h1>
        <div class="content-html">{!! nl2br(e($page->content)) !!}</div>
    </article>
</div>
@endsection
