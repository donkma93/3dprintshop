<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = Material::latest();

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        $materials = $query->paginate(12)->withQueryString();

        return view('admin.materials.index', compact('materials'));
    }

    public function create()
    {
        return view('admin.materials.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Material::create($data);

        return redirect()->route('admin.materials.index')->with('success', 'Đã thêm nguyên liệu.');
    }

    public function edit(Material $material)
    {
        return view('admin.materials.edit', compact('material'));
    }

    public function update(Request $request, Material $material)
    {
        $data = $this->validated($request);
        $material->update($data);

        return redirect()->route('admin.materials.index')->with('success', 'Đã cập nhật nguyên liệu.');
    }

    public function destroy(Material $material)
    {
        // Soft delete: cho phép đưa vào thùng rác dù còn lịch sử nhập
        $material->delete();

        return redirect()->route('admin.materials.index')->with('success', 'Đã chuyển nguyên liệu vào thùng rác. Sẽ xóa vĩnh viễn sau 30 ngày.');
    }

    private function validated(Request $request): array
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

        $data['is_active'] = $request->boolean('is_active');
        $data['stock_quantity'] = $data['stock_quantity'] ?? 0;
        $data['unit_price'] = $data['unit_price'] ?? 0;
        $data['min_stock'] = $data['min_stock'] ?? 0;

        return $data;
    }
}
