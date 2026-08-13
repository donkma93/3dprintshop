@php($item = $material ?? null)
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label">Tên nguyên liệu <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Loại (PLA/ABS/...)</label>
        <input type="text" name="type" value="{{ old('type', $item->type ?? '') }}" class="form-control" list="material-types">
        <datalist id="material-types">
            <option value="PLA"><option value="ABS"><option value="PETG"><option value="TPU"><option value="Resin">
        </datalist>
    </div>
    <div class="col-md-3">
        <label class="form-label">Màu</label>
        <input type="text" name="color" value="{{ old('color', $item->color ?? '') }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Hãng</label>
        <input type="text" name="brand" value="{{ old('brand', $item->brand ?? '') }}" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label">Đơn vị</label>
        <input type="text" name="unit" value="{{ old('unit', $item->unit ?? 'kg') }}" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Tồn kho hiện tại</label>
        <input type="number" step="0.01" min="0" name="stock_quantity" value="{{ old('stock_quantity', $item->stock_quantity ?? 0) }}" class="form-control">
        <div class="form-text">Nên cập nhật qua phiếu nhập.</div>
    </div>
    @if(auth()->user()->canViewRevenue())
    <div class="col-md-3">
        <label class="form-label">Đơn giá</label>
        <input type="number" step="1000" min="0" name="unit_price" value="{{ old('unit_price', $item->unit_price ?? 0) }}" class="form-control">
    </div>
    @elseif(!empty($item))
        <input type="hidden" name="unit_price" value="{{ $item->unit_price ?? 0 }}">
    @endif
    <div class="col-md-3">
        <label class="form-label">Tồn tối thiểu</label>
        <input type="number" step="0.01" min="0" name="min_stock" value="{{ old('min_stock', $item->min_stock ?? 0) }}" class="form-control">
    </div>
    <div class="col-12">
        <label class="form-label">Ghi chú</label>
        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $item->notes ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                   @checked(old('is_active', $item->is_active ?? true))>
            <label class="form-check-label" for="is_active">Đang sử dụng</label>
        </div>
    </div>
</div>
