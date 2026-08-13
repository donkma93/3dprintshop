<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('sort_order')->latest()->paginate(12);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
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

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Đã thêm danh mục thành công.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
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

        return redirect()->route('admin.categories.index')->with('success', 'Đã cập nhật danh mục.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Không thể đưa vào thùng rác: danh mục còn sản phẩm. Hãy chuyển sản phẩm sang danh mục khác trước.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Đã chuyển danh mục vào thùng rác. Sẽ xóa vĩnh viễn sau 30 ngày.');
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
        ], [
            'sku_prefix.unique' => 'Mã SKU danh mục này đã được dùng.',
            'sku_prefix.regex' => 'Mã SKU danh mục chỉ gồm chữ và số (A–Z, 0–9).',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if (! empty($data['sku_prefix'])) {
            $data['sku_prefix'] = Category::normalizeSkuPrefix($data['sku_prefix']);
        } else {
            unset($data['sku_prefix']); // model auto-generate on create/update
        }

        return $data;
    }
}
