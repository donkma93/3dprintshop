<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')->latest();

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data = $this->applySku($data);

        if ($request->hasFile('image')) {
            $data['image'] = ImageOptimizer::store(
                $request->file('image'),
                'products',
                ImageOptimizer::PRODUCT_MAX
            );
        }
        if ($request->hasFile('og_image')) {
            $data['og_image'] = ImageOptimizer::store(
                $request->file('og_image'),
                'products/og',
                ImageOptimizer::OG_MAX
            );
        }

        if (empty($data['slug'])) {
            $data['slug'] = Product::uniqueSlug($data['name']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Đã đăng sản phẩm in 3D thành công.');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);
        $data = $this->applySku($data, $product);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = ImageOptimizer::store(
                $request->file('image'),
                'products',
                ImageOptimizer::PRODUCT_MAX
            );
        }
        if ($request->hasFile('og_image')) {
            if ($product->og_image) {
                Storage::disk('public')->delete($product->og_image);
            }
            $data['og_image'] = ImageOptimizer::store(
                $request->file('og_image'),
                'products/og',
                ImageOptimizer::OG_MAX
            );
        }

        if (empty($data['slug'])) {
            $data['slug'] = Product::uniqueSlug($data['name'], $product->id);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Đã cập nhật sản phẩm.');
    }

    /**
     * Preview SKU tiếp theo khi chọn danh mục (AJAX).
     */
    public function nextSku(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'product_id' => ['nullable', 'exists:products,id'],
        ]);

        $category = Category::findOrFail($data['category_id']);
        $sku = Product::generateUniqueSku($category, $data['product_id'] ?? null);

        return response()->json([
            'success' => true,
            'sku' => $sku,
            'sku_prefix' => $category->sku_prefix,
        ]);
    }

    private function applySku(array $data, ?Product $product = null): array
    {
        $sku = isset($data['sku']) ? trim((string) $data['sku']) : '';
        $categoryId = $data['category_id'] ?? $product?->category_id;

        if ($sku === '' && $categoryId) {
            $category = Category::find($categoryId);
            if ($category) {
                $data['sku'] = Product::generateUniqueSku($category, $product?->id);
            }
        } elseif ($sku !== '') {
            $data['sku'] = Product::normalizeSku($sku);
        } else {
            $data['sku'] = $sku === '' ? null : $sku;
        }

        return $data;
    }

    public function destroy(Product $product)
    {
        // Soft delete: giữ ảnh để có thể khôi phục trong 30 ngày
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Đã chuyển sản phẩm vào thùng rác. Sẽ xóa vĩnh viễn sau 30 ngày.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug,'.($product?->id ?? 'NULL')],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($product?->id)->whereNull('deleted_at'),
            ],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'sale_starts_at' => ['nullable', 'date'],
            'sale_ends_at' => ['nullable', 'date', 'after_or_equal:sale_starts_at'],
            'promo_label' => ['nullable', 'string', 'max:80'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:12288'],
            'material_used' => ['nullable', 'string', 'max:255'],
            'weight_grams' => ['nullable', 'numeric', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'og_image' => ['nullable', 'image', 'max:12288'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');
        $data['cost_price'] = $data['cost_price'] ?? 0;
        $data['stock'] = $data['stock'] ?? 0;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['sale_price'] = ($data['sale_price'] ?? '') === '' || $data['sale_price'] === null
            ? null
            : $data['sale_price'];
        $data['promo_label'] = $data['promo_label'] ?? null;
        $data['sale_starts_at'] = $data['sale_starts_at'] ?? null;
        $data['sale_ends_at'] = $data['sale_ends_at'] ?? null;

        if ($data['sale_price'] !== null && (float) $data['sale_price'] >= (float) $data['price']) {
            $data['sale_price'] = null;
        }

        return $data;
    }
}
