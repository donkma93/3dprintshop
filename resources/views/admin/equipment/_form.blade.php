@php($item = $equipment ?? null)
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label">Tên thiết bị <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Loại thiết bị</label>
        <input type="text" name="type" value="{{ old('type', $item->type ?? '') }}" class="form-control" list="eq-types" placeholder="Máy in FDM, Resin, Phụ trợ...">
        <datalist id="eq-types">
            <option value="Máy in FDM"><option value="Máy in Resin"><option value="Phụ trợ"><option value="Máy rửa & sấy">
        </datalist>
    </div>
    <div class="col-md-4">
        <label class="form-label">Hãng</label>
        <input type="text" name="brand" value="{{ old('brand', $item->brand ?? '') }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Model</label>
        <input type="text" name="model" value="{{ old('model', $item->model ?? '') }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Số serial</label>
        <input type="text" name="serial_number" value="{{ old('serial_number', $item->serial_number ?? '') }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Ngày mua</label>
        <input type="date" name="purchase_date" value="{{ old('purchase_date', optional($item->purchase_date ?? null)->format('Y-m-d')) }}" class="form-control">
    </div>
    @if(auth()->user()->canViewRevenue())
    <div class="col-md-4">
        <label class="form-label">Giá mua (VNĐ)</label>
        <input type="number" step="1000" min="0" name="purchase_price" value="{{ old('purchase_price', $item->purchase_price ?? 0) }}" class="form-control">
    </div>
    @elseif(!empty($item))
        <input type="hidden" name="purchase_price" value="{{ $item->purchase_price ?? 0 }}">
    @endif
    <div class="col-md-4">
        <label class="form-label">Trạng thái</label>
        <select name="status" class="form-select" required>
            @foreach(\App\Models\Equipment::statusOptions() as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $item->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
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
