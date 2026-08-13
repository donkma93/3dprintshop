@php($item = $category ?? null)
<div class="row g-3 mb-3">
    <div class="col-md-8">
        <label class="form-label">Tên danh mục <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Slug (để trống sẽ tự tạo)</label>
        <input type="text" name="slug" value="{{ old('slug', $item->slug ?? '') }}" class="form-control" placeholder="mo-hinh-trang-tri">
    </div>
    <div class="col-md-4">
        <label class="form-label">Mã SKU danh mục</label>
        <input type="text" name="sku_prefix" value="{{ old('sku_prefix', $item->sku_prefix ?? '') }}"
               class="form-control text-uppercase" maxlength="8" pattern="[A-Za-z0-9]*"
               placeholder="Tự tạo từ tên (vd: MHT)">
        <div class="form-text">Prefix cho SKU sản phẩm: <code>PREFIX-0001</code>. Để trống sẽ tự sinh, không trùng.</div>
    </div>
    <div class="col-12">
        <label class="form-label">Mô tả</label>
        <textarea name="description" rows="3" class="form-control">{{ old('description', $item->description ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">Ảnh danh mục</label>
        <input type="file" name="image" class="form-control" accept="image/*">
        @if(!empty($item?->image))
            <img src="{{ $item->image_url }}" class="mt-2 rounded" height="60" alt="">
        @endif
    </div>
    <div class="col-md-3">
        <label class="form-label">Thứ tự</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $item->sort_order ?? 0) }}" min="0">
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                   @checked(old('is_active', $item->is_active ?? true))>
            <label class="form-check-label" for="is_active">Hiển thị trên cửa hàng</label>
        </div>
    </div>

    <div class="col-12"><hr><h3 class="h6 fw-bold">SEO</h3></div>
    <div class="col-md-6">
        <label class="form-label">Meta title</label>
        <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $item->meta_title ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Meta keywords</label>
        <input type="text" name="meta_keywords" class="form-control" value="{{ old('meta_keywords', $item->meta_keywords ?? '') }}">
    </div>
    <div class="col-12">
        <label class="form-label">Meta description</label>
        <textarea name="meta_description" rows="2" class="form-control">{{ old('meta_description', $item->meta_description ?? '') }}</textarea>
    </div>
</div>
