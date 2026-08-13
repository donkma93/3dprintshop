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

            $material = Material::lockForUpdate()->findOrFail($data['material_id']);
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
            $oldMaterial = Material::lockForUpdate()->findOrFail($materialInput->material_id);
            $oldMaterial->stock_quantity = max(0, (float) $oldMaterial->stock_quantity - (float) $materialInput->quantity);
            $oldMaterial->save();

            $materialInput->update($data);

            $newMaterial = Material::lockForUpdate()->findOrFail($data['material_id']);
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
            $material = Material::lockForUpdate()->findOrFail($materialInput->material_id);
            $material->stock_quantity = max(0, (float) $material->stock_quantity - (float) $materialInput->quantity);
            $material->save();
            $materialInput->delete();
        });

        return $this->ok(null, 'Đã chuyển phiếu nhập vào thùng rác và điều chỉnh tồn kho.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'material_id' => ['required', 'exists:materials,id'],
            'input_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
