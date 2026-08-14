<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\MaterialInputResource;
use App\Models\Material;
use App\Models\MaterialInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialInputController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));
        $query = MaterialInput::with('material')->latest('input_date')->latest('id');

        if ($request->filled('material_id')) {
            $query->where('material_id', $request->integer('material_id'));
        }

        return $this->ok(MaterialInputResource::collection($query->paginate($perPage)->withQueryString()));
    }

    public function show(MaterialInput $materialInput): JsonResponse
    {
        $materialInput->load('material');

        return $this->ok(new MaterialInputResource($materialInput));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['total_price'] = round((float) $data['quantity'] * (float) $data['unit_price'], 2);

        $input = DB::transaction(function () use ($data) {
            $input = MaterialInput::create($data);

            $material = Material::query()->lockForUpdate()->findOrFail($data['material_id']);
            $material->stock_quantity = (float) $material->stock_quantity + (float) $data['quantity'];
            $material->unit_price = $data['unit_price'];
            $material->save();

            return $input;
        });

        $input->load('material');

        return $this->created(new MaterialInputResource($input), 'Đã ghi nhận phiếu nhập.');
    }

    public function update(Request $request, MaterialInput $materialInput): JsonResponse
    {
        $data = $this->validated($request);
        $data['total_price'] = round((float) $data['quantity'] * (float) $data['unit_price'], 2);

        DB::transaction(function () use ($materialInput, $data) {
            // NL cũ có thể đang soft-delete / đã mất hẳn — chỉ trừ tồn nếu còn bản ghi.
            $this->adjustStock($materialInput->material_id, -(float) $materialInput->quantity);

            $materialInput->update($data);

            $newMaterial = Material::query()->lockForUpdate()->findOrFail($data['material_id']);
            $newMaterial->stock_quantity = (float) $newMaterial->stock_quantity + (float) $data['quantity'];
            $newMaterial->unit_price = $data['unit_price'];
            $newMaterial->save();
        });

        $materialInput->load('material');

        return $this->ok(new MaterialInputResource($materialInput->fresh('material')), 'Đã cập nhật phiếu nhập.');
    }

    public function destroy(MaterialInput $materialInput): JsonResponse
    {
        DB::transaction(function () use ($materialInput) {
            // Trừ tồn nếu NL còn (kể cả soft-delete). NL đã xóa vĩnh viễn → vẫn xóa phiếu.
            $this->adjustStock($materialInput->material_id, -(float) $materialInput->quantity);
            $materialInput->delete();
        });

        return $this->ok(null, 'Đã chuyển phiếu nhập vào thùng rác và điều chỉnh tồn kho.');
    }

    /**
     * Cộng/trừ tồn nguyên liệu (kể cả soft-deleted). Không ném lỗi nếu NL không còn.
     */
    private function adjustStock(int $materialId, float $delta): void
    {
        $material = Material::withTrashed()->lockForUpdate()->find($materialId);
        if (! $material) {
            return;
        }

        $next = (float) $material->stock_quantity + $delta;
        $material->stock_quantity = max(0, $next);
        $material->save();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            // Chỉ cho phép NL đang active (không soft-delete) khi tạo/sửa phiếu.
            'material_id' => ['required', 'exists:materials,id,deleted_at,NULL'],
            'input_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
