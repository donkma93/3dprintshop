<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\SiteSetting;

class PostController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::allCached();
        $posts = Post::published()->latest('published_at')->paginate(9);
        $siteName = $settings['site_name'] ?? 'Shop3DPrinting';

        $seo = [
            'title' => 'Tin tức & bài viết | '.$siteName,
            'description' => 'Tin tức, kiến thức in 3D và cập nhật từ '.$siteName,
            'keywords' => $settings['meta_keywords'] ?? 'tin tức in 3D',
            'canonical' => route('shop.posts.index'),
            'og_type' => 'website',
        ];

        return view('shop.posts.index', compact('posts', 'seo', 'settings'));
    }

    public function show(string $slug)
    {
        $settings = SiteSetting::allCached();
        $post = Post::published()->where('slug', $slug)->firstOrFail();
        $related = Post::published()->where('id', '!=', $post->id)->latest('published_at')->take(4)->get();
        $siteName = $settings['site_name'] ?? 'Shop3DPrinting';

        $seo = [
            'title' => $post->seo_title.($post->meta_title ? '' : ' | '.$siteName),
            'description' => $post->seo_description,
            'keywords' => $post->meta_keywords,
            'canonical' => route('shop.posts.show', $post->slug),
            'og_type' => 'article',
            'og_image' => $post->og_image_url,
        ];

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->seo_description,
            'image' => [$post->image_url],
            'datePublished' => optional($post->published_at)->toIso8601String(),
            'dateModified' => optional($post->updated_at)->toIso8601String(),
            'mainEntityOfPage' => route('shop.posts.show', $post->slug),
        ];

        return view('shop.posts.show', compact('post', 'related', 'seo', 'jsonLd', 'settings'));
    }
}
