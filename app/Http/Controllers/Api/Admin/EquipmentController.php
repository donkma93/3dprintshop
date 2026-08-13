<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));
        $query = Equipment::latest();

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return $this->ok(EquipmentResource::collection($query->paginate($perPage)->withQueryString()));
    }

    public function show(Equipment $equipment): JsonResponse
    {
        return $this->ok(new EquipmentResource($equipment));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $equipment = Equipment::create($data);

        return $this->created(new EquipmentResource($equipment), 'Đã thêm thiết bị.');
    }

    public function update(Request $request, Equipment $equipment): JsonResponse
    {
        $data = $this->validated($request);
        $equipment->update($data);

        return $this->ok(new EquipmentResource($equipment->fresh()), 'Đã cập nhật thiết bị.');
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        $equipment->delete();

        return $this->ok(null, 'Đã chuyển thiết bị vào thùng rác.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(Equipment::statusOptions()))],
            'notes' => ['nullable', 'string'],
        ]);

        $data['purchase_price'] = $data['purchase_price'] ?? 0;

        return $data;
    }
}
