<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\ProductResource;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class HomeController extends ApiController
{
    public function index(): JsonResponse
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

        $affordable = (clone $activeProducts)
            ->with('category')
            ->orderBy('price')
            ->take(6)
            ->get();

        $onSale = (clone $activeProducts)
            ->with('category')
            ->onSale()
            ->orderBy('sort_order')
            ->latest()
            ->take(8)
            ->get();

        $posts = Post::published()->latest('published_at')->take(3)->get();

        return $this->ok([
            'settings' => $this->publicSettings($settings),
            'sliders' => BannerResource::collection($sliders),
            'promos' => BannerResource::collection($promos),
            'categories' => CategoryResource::collection($categories),
            'featured_products' => ProductResource::collection($featured),
            'latest_products' => ProductResource::collection($latest),
            'affordable_products' => ProductResource::collection($affordable),
            'sale_products' => ProductResource::collection($onSale),
            'posts' => PostResource::collection($posts),
            'stats' => [
                'products' => (clone $activeProducts)->count(),
                'categories' => $categories->count(),
                'materials' => 'PLA · PETG · Resin',
                'lead_time' => '1–3 ngày',
            ],
        ]);
    }

    private function publicSettings(array $settings): array
    {
        $keys = [
            'site_name', 'site_tagline', 'phone', 'hotline', 'email', 'address',
            'working_hours', 'facebook', 'zalo', 'youtube', 'footer_about',
            'footer_copyright', 'home_about_title', 'home_about_content',
            'home_why_title', 'home_why_content',
        ];

        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $settings[$key] ?? null;
        }

        foreach (['logo', 'favicon', 'og_image'] as $fileField) {
            $path = $settings[$fileField] ?? null;
            $out[$fileField] = $path;
            $out[$fileField.'_url'] = $path ? asset('storage/'.$path) : null;
        }

        return $out;
    }
}
