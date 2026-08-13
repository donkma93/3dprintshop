<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(50, max(1, (int) $request->query('per_page', 16)));
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

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $products = $query->paginate($perPage)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        $resource = ProductResource::collection($products);
        $response = $this->ok($resource);
        $body = $response->getData(true);
        $body['filters'] = [
            'categories' => CategoryResource::collection($categories)->resolve(),
            'active_category' => $activeCategory ? (new CategoryResource($activeCategory))->resolve() : null,
        ];

        return response()->json($body);
    }

    public function show(string $slug): JsonResponse
    {
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

        return $this->ok([
            'product' => new ProductResource($product),
            'related' => ProductResource::collection($related),
        ]);
    }
}
