<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $settings = SiteSetting::allCached();
        $query = Product::with('category')->where('is_active', true)->orderBy('sort_order')->latest();

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%")
                    ->orWhere('material_used', 'like', "%{$search}%")
                    ->orWhere('meta_keywords', 'like', "%{$search}%");
            });
        }

        $activeCategory = null;
        if ($request->filled('category')) {
            $activeCategory = Category::where('is_active', true)
                ->where('slug', $request->string('category')->toString())
                ->first();

            if ($activeCategory) {
                $query->where('category_id', $activeCategory->id);
            }
        }

        $products = $query->paginate(16)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        $siteName = $settings['site_name'] ?? 'Cửa hàng in 3D';
        $seo = [
            'title' => $activeCategory
                ? ($activeCategory->seo_title.' | '.$siteName)
                : ('Sản phẩm in 3D | '.$siteName),
            'description' => $activeCategory
                ? ($activeCategory->seo_description ?: 'Danh mục '.$activeCategory->name)
                : ($settings['meta_description'] ?? 'Danh sách sản phẩm in 3D'),
            'keywords' => $activeCategory?->meta_keywords ?: ($settings['meta_keywords'] ?? ''),
            'canonical' => $activeCategory
                ? route('shop.products.index', ['category' => $activeCategory->slug])
                : route('shop.products.index'),
            'og_type' => 'website',
        ];

        return view('shop.products.index', compact('products', 'categories', 'activeCategory', 'seo', 'settings'));
    }

    public function show(string $slug)
    {
        $settings = SiteSetting::allCached();
        $product = Product::with('category')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $related = Product::with('category')
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->latest()
            ->take(8)
            ->get();

        $siteName = $settings['site_name'] ?? 'Cửa hàng in 3D';
        $seo = [
            'title' => $product->seo_title.($product->meta_title ? '' : ' | '.$siteName),
            'description' => $product->seo_description,
            'keywords' => $product->meta_keywords,
            'canonical' => route('shop.products.show', $product->slug),
            'og_type' => 'product',
            'og_image' => $product->og_image_url,
        ];

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->seo_description,
            'image' => [$product->image_url],
            'sku' => $product->sku,
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'VND',
                'price' => (float) $product->final_price,
                'availability' => $product->stock > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'url' => route('shop.products.show', $product->slug),
            ],
        ];

        return view('shop.products.show', compact('product', 'related', 'seo', 'jsonLd', 'settings'));
    }
}
