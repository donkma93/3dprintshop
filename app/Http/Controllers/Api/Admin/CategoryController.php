<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));
        $categories = Category::withCount('products')
            ->orderBy('sort_order')
            ->latest()
            ->paginate($perPage);

        return $this->ok(CategoryResource::collection($categories));
    }

    public function show(Category $category): JsonResponse
    {
        $category->loadCount('products');

        return $this->ok(new CategoryResource($category));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }
        if (empty($data['slug'])) {
            $data['slug'] = Category::uniqueSlug($data['name']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        $category = Category::create($data);
        $category->loadCount('products');

        return $this->created(new CategoryResource($category), 'Đã thêm danh mục.');
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $this->validated($request, $category);

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        }
        if (empty($data['slug'])) {
            $data['slug'] = Category::uniqueSlug($data['name'], $category->id);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        $category->update($data);
        $category->loadCount('products');

        return $this->ok(new CategoryResource($category->fresh()), 'Đã cập nhật danh mục.');
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return $this->fail('Không thể đưa vào thùng rác: danh mục còn sản phẩm.', 422);
        }

        $category->delete();

        return $this->ok(null, 'Đã chuyển danh mục vào thùng rác.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug,'.($category?->id ?? 'NULL')],
            'sku_prefix' => [
                'nullable',
                'string',
                'max:8',
                'regex:/^[A-Za-z0-9]+$/',
                'unique:categories,sku_prefix,'.($category?->id ?? 'NULL'),
            ],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', $category?->is_active ?? true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if (! empty($data['sku_prefix'])) {
            $data['sku_prefix'] = Category::normalizeSkuPrefix($data['sku_prefix']);
        } else {
            unset($data['sku_prefix']);
        }

        return $data;
    }
}
