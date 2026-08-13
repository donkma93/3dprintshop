<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BannerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));
        $query = Banner::orderBy('sort_order')->latest();

        if ($request->filled('position')) {
            $query->where('position', $request->string('position')->toString());
        }

        return $this->ok(BannerResource::collection($query->paginate($perPage)->withQueryString()));
    }

    public function show(Banner $banner): JsonResponse
    {
        return $this->ok(new BannerResource($banner));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $banner = Banner::create($data);

        return $this->created(new BannerResource($banner), 'Đã thêm banner.');
    }

    public function update(Request $request, Banner $banner): JsonResponse
    {
        $data = $this->validated($request, $banner);

        if ($request->hasFile('image')) {
            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);

        return $this->ok(new BannerResource($banner->fresh()), 'Đã cập nhật banner.');
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();

        return $this->ok(null, 'Đã chuyển banner vào thùng rác.');
    }

    private function validated(Request $request, ?Banner $banner = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
            'link' => ['nullable', 'string', 'max:500'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'position' => ['required', Rule::in(array_keys(Banner::positionOptions()))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', $banner?->is_active ?? true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
