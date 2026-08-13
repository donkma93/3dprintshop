<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));
        $pages = Page::orderBy('sort_order')->latest()->paginate($perPage);

        return $this->ok(PageResource::collection($pages));
    }

    public function show(Page $page): JsonResponse
    {
        return $this->ok(new PageResource($page));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('pages/og', 'public');
        }
        if (empty($data['slug'])) {
            $data['slug'] = Page::uniqueSlug($data['title']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        $page = Page::create($data);

        return $this->created(new PageResource($page), 'Đã tạo trang.');
    }

    public function update(Request $request, Page $page): JsonResponse
    {
        $data = $this->validated($request, $page);

        if ($request->hasFile('og_image')) {
            if ($page->og_image) {
                Storage::disk('public')->delete($page->og_image);
            }
            $data['og_image'] = $request->file('og_image')->store('pages/og', 'public');
        }
        if (empty($data['slug'])) {
            $data['slug'] = Page::uniqueSlug($data['title'], $page->id);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        $page->update($data);

        return $this->ok(new PageResource($page->fresh()), 'Đã cập nhật trang.');
    }

    public function destroy(Page $page): JsonResponse
    {
        $page->delete();

        return $this->ok(null, 'Đã chuyển trang vào thùng rác.');
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:pages,slug,'.($page?->id ?? 'NULL')],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'og_image' => ['nullable', 'image', 'max:4096'],
            'is_published' => ['nullable', 'boolean'],
            'show_in_menu' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_published'] = $request->boolean('is_published', $page?->is_published ?? false);
        $data['show_in_menu'] = $request->boolean('show_in_menu', $page?->show_in_menu ?? false);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
