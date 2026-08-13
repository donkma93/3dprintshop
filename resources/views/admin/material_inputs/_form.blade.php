@php($item = $materialInput ?? null)
<div class="row g-3 mb-3">
    <div class="col-md-8">
        <label class="form-label">Nguyên liệu <span class="text-danger">*</span></label>
        <select name="material_id" class="form-select" required>
            <option value="">— Chọn nguyên liệu —</option>
            @foreach($materials as $material)
                <option value="{{ $material->id }}" @selected(old('material_id', $item->material_id ?? '') == $material->id)>
                    {{ $material->name }} (tồn: {{ $material->stock_quantity }} {{ $material->unit }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Ngày nhập <span class="text-danger">*</span></label>
        <input type="date" name="input_date" value="{{ old('input_date', optional($item->input_date ?? null)->format('Y-m-d') ?? date('Y-m-d')) }}" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Số lượng <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0.01" name="quantity" value="{{ old('quantity', $item->quantity ?? '') }}" class="form-control" required>
    </div>
    @if(auth()->user()->canViewRevenue())
    <div class="col-md-4">
        <label class="form-label">Đơn giá (VNĐ) <span class="text-danger">*</span></label>
        <input type="number" step="1000" min="0" name="unit_price" value="{{ old('unit_price', $item->unit_price ?? '') }}" class="form-control" required>
    </div>
    @else
    <div class="col-md-4">
        <label class="form-label">Đơn giá (nội bộ)</label>
        <input type="number" step="1000" min="0" name="unit_price" value="{{ old('unit_price', $item->unit_price ?? 0) }}" class="form-control" required>
        <div class="form-text">Số liệu thành tiền chỉ Quản trị viên xem được trên danh sách.</div>
    </div>
    @endif
    <div class="col-md-4">
        <label class="form-label">Số hóa đơn</label>
        <input type="text" name="invoice_number" value="{{ old('invoice_number', $item->invoice_number ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label">Nhà cung cấp</label>
        <input type="text" name="supplier" value="{{ old('supplier', $item->supplier ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label">Ghi chú</label>
        <input type="text" name="notes" value="{{ old('notes', $item->notes ?? '') }}" class="form-control">
    </div>
</div>
