@php($item = $page ?? null)
<div class="row g-3 mb-3">
    <div class="col-md-8">
        <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control" value="{{ old('title', $item->title ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Slug</label>
        <input type="text" name="slug" class="form-control" value="{{ old('slug', $item->slug ?? '') }}">
    </div>
    <div class="col-12">
        <label class="form-label">Nội dung</label>
        <textarea name="content" rows="12" class="form-control">{{ old('content', $item->content ?? '') }}</textarea>
    </div>
    <div class="col-md-3">
        <label class="form-label">Thứ tự menu</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $item->sort_order ?? 0) }}" min="0">
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_published" value="1" id="is_published" @checked(old('is_published', $item->is_published ?? true))>
            <label class="form-check-label" for="is_published">Xuất bản</label>
        </div>
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="show_in_menu" value="1" id="show_in_menu" @checked(old('show_in_menu', $item->show_in_menu ?? true))>
            <label class="form-check-label" for="show_in_menu">Hiện trên menu</label>
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
    <div class="col-md-8">
        <label class="form-label">Meta description</label>
        <textarea name="meta_description" rows="2" class="form-control">{{ old('meta_description', $item->meta_description ?? '') }}</textarea>
    </div>
    <div class="col-md-4">
        <label class="form-label">OG image</label>
        <input type="file" name="og_image" class="form-control" accept="image/*">
    </div>
</div>
