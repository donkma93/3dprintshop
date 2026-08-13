@php($item = $post ?? null)
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
        <label class="form-label">Tóm tắt</label>
        <textarea name="excerpt" rows="2" class="form-control">{{ old('excerpt', $item->excerpt ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">Nội dung</label>
        <textarea name="content" rows="10" class="form-control">{{ old('content', $item->content ?? '') }}</textarea>
    </div>
    <div class="col-md-4">
        <label class="form-label">Ảnh đại diện</label>
        <input type="file" name="image" class="form-control" accept="image/*">
        @if(!empty($item?->image))
            <img src="{{ $item->image_url }}" class="mt-2 rounded" height="70" alt="">
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label">Ngày xuất bản</label>
        <input type="datetime-local" name="published_at" class="form-control"
               value="{{ old('published_at', optional($item->published_at ?? null)->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_published" value="1" id="is_published" @checked(old('is_published', $item->is_published ?? true))>
            <label class="form-check-label" for="is_published">Xuất bản</label>
        </div>
    </div>

    <div class="col-12"><hr><h3 class="h6 fw-bold">SEO</h3></div>
    <div class="col-md-6">
        <label class="form-label">Meta title</label>
        <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $item->meta_title ?? '') }}" maxlength="255">
    </div>
    <div class="col-md-6">
        <label class="form-label">Meta keywords</label>
        <input type="text" name="meta_keywords" class="form-control" value="{{ old('meta_keywords', $item->meta_keywords ?? '') }}">
    </div>
    <div class="col-md-8">
        <label class="form-label">Meta description</label>
        <textarea name="meta_description" rows="2" class="form-control" maxlength="500">{{ old('meta_description', $item->meta_description ?? '') }}</textarea>
    </div>
    <div class="col-md-4">
        <label class="form-label">OG image</label>
        <input type="file" name="og_image" class="form-control" accept="image/*">
    </div>
</div>
