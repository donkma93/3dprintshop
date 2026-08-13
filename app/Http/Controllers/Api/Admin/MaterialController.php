<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\MaterialResource;
use App\Models\Material;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));
        $query = Material::latest();

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock_quantity', '<=', 'min_stock');
        }

        return $this->ok(MaterialResource::collection($query->paginate($perPage)->withQueryString()));
    }

    public function show(Material $material): JsonResponse
    {
        return $this->ok(new MaterialResource($material));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $material = Material::create($data);

        return $this->created(new MaterialResource($material), 'Đã thêm nguyên liệu.');
    }

    public function update(Request $request, Material $material): JsonResponse
    {
        $data = $this->validated($request, $material);
        $material->update($data);

        return $this->ok(new MaterialResource($material->fresh()), 'Đã cập nhật nguyên liệu.');
    }

    public function destroy(Material $material): JsonResponse
    {
        $material->delete();

        return $this->ok(null, 'Đã chuyển nguyên liệu vào thùng rác.');
    }

    private function validated(Request $request, ?Material $material = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', $material?->is_active ?? true);
        $data['stock_quantity'] = $data['stock_quantity'] ?? 0;
        $data['unit_price'] = $data['unit_price'] ?? 0;
        $data['min_stock'] = $data['min_stock'] ?? 0;

        return $data;
    }
}
