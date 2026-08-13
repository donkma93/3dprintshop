<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialInputController extends Controller
{
    public function index()
    {
        $inputs = MaterialInput::with('material')->latest('input_date')->latest('id')->paginate(15);

        return view('admin.material_inputs.index', compact('inputs'));
    }

    public function create()
    {
        $materials = Material::where('is_active', true)->orderBy('name')->get();

        return view('admin.material_inputs.create', compact('materials'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'material_id' => ['required', 'exists:materials,id'],
            'input_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['total_price'] = round((float) $data['quantity'] * (float) $data['unit_price'], 2);

        DB::transaction(function () use ($data) {
            MaterialInput::create($data);

            $material = Material::lockForUpdate()->findOrFail($data['material_id']);
            $material->stock_quantity = (float) $material->stock_quantity + (float) $data['quantity'];
            $material->unit_price = $data['unit_price'];
            $material->save();
        });

        return redirect()->route('admin.material-inputs.index')->with('success', 'Đã ghi nhận phiếu nhập nguyên liệu và cập nhật tồn kho.');
    }

    public function edit(MaterialInput $materialInput)
    {
        $materials = Material::orderBy('name')->get();

        return view('admin.material_inputs.edit', compact('materialInput', 'materials'));
    }

    public function update(Request $request, MaterialInput $materialInput)
    {
        $data = $request->validate([
            'material_id' => ['required', 'exists:materials,id'],
            'input_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

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

        return redirect()->route('admin.material-inputs.index')->with('success', 'Đã cập nhật phiếu nhập.');
    }

    public function destroy(MaterialInput $materialInput)
    {
        DB::transaction(function () use ($materialInput) {
            // Trừ tồn khi đưa vào thùng rác; khôi phục sẽ cộng lại
            $material = Material::lockForUpdate()->findOrFail($materialInput->material_id);
            $material->stock_quantity = max(0, (float) $material->stock_quantity - (float) $materialInput->quantity);
            $material->save();
            $materialInput->delete();
        });

        return redirect()->route('admin.material-inputs.index')->with('success', 'Đã chuyển phiếu nhập vào thùng rác và điều chỉnh tồn kho. Sẽ xóa vĩnh viễn sau 30 ngày.');
    }
}
