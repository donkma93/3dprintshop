@php($item = $banner ?? null)
<div class="row g-3 mb-3">
    <div class="col-md-8">
        <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control" value="{{ old('title', $item->title ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Vị trí</label>
        <select name="position" class="form-select" required>
            @foreach(\App\Models\Banner::positionOptions() as $value => $label)
                <option value="{{ $value }}" @selected(old('position', $item->position ?? 'home_slider') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Phụ đề</label>
        <input type="text" name="subtitle" class="form-control" value="{{ old('subtitle', $item->subtitle ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Ảnh banner</label>
        <input type="file" name="image" class="form-control" accept="image/*">
        @if(!empty($item?->image))
            <img src="{{ $item->image_url }}" class="mt-2 rounded" height="70" alt="">
        @endif
    </div>
    <div class="col-md-3">
        <label class="form-label">Nút bấm</label>
        <input type="text" name="button_text" class="form-control" value="{{ old('button_text', $item->button_text ?? '') }}" placeholder="Xem ngay">
    </div>
    <div class="col-md-3">
        <label class="form-label">Thứ tự</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $item->sort_order ?? 0) }}" min="0">
    </div>
    <div class="col-12">
        <label class="form-label">Link</label>
        <input type="text" name="link" class="form-control" value="{{ old('link', $item->link ?? '') }}" placeholder="/san-pham">
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $item->is_active ?? true))>
            <label class="form-check-label" for="is_active">Hiển thị</label>
        </div>
    </div>
</div>
