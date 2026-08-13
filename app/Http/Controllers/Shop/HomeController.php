<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\SiteSetting;

class HomeController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::allCached();

        $sliders = Banner::where('is_active', true)
            ->where('position', 'home_slider')
            ->orderBy('sort_order')
            ->get();

        $promos = Banner::where('is_active', true)
            ->where('position', 'home_promo')
            ->orderBy('sort_order')
            ->take(3)
            ->get();

        $categories = Category::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $activeProducts = Product::query()->where('is_active', true);

        $featured = (clone $activeProducts)
            ->with('category')
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->latest()
            ->take(8)
            ->get();

        $latest = (clone $activeProducts)
            ->with('category')
            ->orderBy('sort_order')
            ->latest()
            ->take(8)
            ->get();

        // Sản phẩm giá dễ tiếp cận — kéo khách "chỉ xem một chút"
        $affordable = (clone $activeProducts)
            ->with('category')
            ->orderBy('price')
            ->take(6)
            ->get();

        // Dải ảnh nhanh ngay dưới hero
        $spotlight = (clone $activeProducts)
            ->with('category')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->latest()
            ->take(10)
            ->get();

        $onSale = (clone $activeProducts)
            ->with('category')
            ->onSale()
            ->orderBy('sort_order')
            ->latest()
            ->take(8)
            ->get();

        $orderProducts = (clone $activeProducts)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'sale_price', 'sale_starts_at', 'sale_ends_at', 'stock']);

        $posts = Post::published()->latest('published_at')->take(3)->get();

        $stats = [
            'products' => (clone $activeProducts)->count(),
            'categories' => $categories->count(),
            'materials' => 'PLA · PETG · Resin',
            'lead_time' => '1–3 ngày',
        ];

        $seo = [
            'title' => $settings['meta_title'] ?? ($settings['site_name'] ?? 'Cửa hàng in 3D'),
            'description' => $settings['meta_description'] ?? ($settings['site_tagline'] ?? ''),
            'keywords' => $settings['meta_keywords'] ?? '',
            'canonical' => route('shop.home'),
            'og_type' => 'website',
            'og_image' => ! empty($settings['og_image']) ? asset('storage/'.$settings['og_image']) : null,
        ];

        return view('shop.home', compact(
            'settings',
            'sliders',
            'promos',
            'categories',
            'featured',
            'latest',
            'affordable',
            'spotlight',
            'onSale',
            'orderProducts',
            'posts',
            'stats',
            'seo'
        ));
    }
}
