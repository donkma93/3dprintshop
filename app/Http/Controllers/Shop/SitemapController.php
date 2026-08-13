<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = collect();

        $urls->push([
            'loc' => route('shop.home'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ]);

        $urls->push([
            'loc' => route('shop.products.index'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.9',
        ]);

        $urls->push([
            'loc' => route('shop.posts.index'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ]);

        foreach (Category::where('is_active', true)->get() as $category) {
            $urls->push([
                'loc' => route('shop.products.index', ['category' => $category->slug]),
                'lastmod' => optional($category->updated_at)->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ]);
        }

        foreach (Product::where('is_active', true)->get() as $product) {
            $urls->push([
                'loc' => route('shop.products.show', $product->slug),
                'lastmod' => optional($product->updated_at)->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ]);
        }

        foreach (Post::published()->get() as $post) {
            $urls->push([
                'loc' => route('shop.posts.show', $post->slug),
                'lastmod' => optional($post->updated_at)->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ]);
        }

        foreach (Page::published()->get() as $page) {
            $urls->push([
                'loc' => route('shop.pages.show', $page->slug),
                'lastmod' => optional($page->updated_at)->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ]);
        }

        $xml = view('shop.sitemap', compact('urls'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $content = "User-agent: *\nAllow: /\nDisallow: /admin\nSitemap: ".url('/sitemap.xml')."\n";

        return response($content, 200)->header('Content-Type', 'text/plain');
    }
}
