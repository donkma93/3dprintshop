@php($item = $video ?? null)
<div class="row g-3 mb-3">
    <div class="col-12">
        <label class="form-label">Link video / live stream <span class="text-danger">*</span></label>
        <div class="input-group">
            <input type="url" name="url" id="socialVideoUrl" class="form-control" value="{{ old('url', $item->url ?? '') }}" required
                   placeholder="https://www.youtube.com/watch?v=… · /live · TikTok @user/video/… · @user/live">
            <button type="button" class="btn btn-outline-secondary" id="socialVideoFetchBtn">
                Lấy thông tin
            </button>
        </div>
        <div class="form-text">
            Chỉ link online: YouTube (video, Shorts, Live), TikTok (video, Live), Facebook.
            Thumbnail <strong>tự lấy</strong> từ mạng xã hội — không cần upload.
        </div>
        <div id="socialVideoFetchStatus" class="small mt-1 text-secondary" hidden></div>
    </div>

    <div class="col-12" id="socialVideoPreviewWrap" @if(empty($item?->thumbnail_url) && !old('url')) hidden @endif>
        <div class="d-flex align-items-center gap-3 p-2 border rounded bg-light">
            <img id="socialVideoThumbPreview"
                 src="{{ $item->thumbnail_url ?? '' }}"
                 alt=""
                 width="120" height="68"
                 class="rounded border"
                 style="object-fit:cover;background:#e2e8f0;{{ empty($item?->thumbnail_url) ? 'display:none' : '' }}">
            <div class="small text-secondary">
                <div class="fw-semibold text-dark">Xem trước thumbnail (tự động)</div>
                <div id="socialVideoMetaLine">
                    @if($item)
                        {{ $item->platform_label }}@if($item->channel_name) · {{ $item->channel_name }}@endif
                    @else
                        Dán link rồi bấm “Lấy thông tin” hoặc lưu form.
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <label class="form-label">Tiêu đề hiển thị</label>
        <input type="text" name="title" id="socialVideoTitle" class="form-control"
               value="{{ old('title', $item->title ?? '') }}" maxlength="255"
               placeholder="Để trống = dùng tiêu đề lấy từ mạng xã hội">
    </div>
    <div class="col-md-4">
        <label class="form-label">Nền tảng</label>
        <select name="platform" id="socialVideoPlatform" class="form-select">
            <option value="">— Tự nhận từ link —</option>
            @foreach(\App\Models\SocialVideo::platformOptions() as $value => $label)
                <option value="{{ $value }}" @selected(old('platform', $item->platform ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Tên kênh / tài khoản</label>
        <input type="text" name="channel_name" id="socialVideoChannel" class="form-control"
               value="{{ old('channel_name', $item->channel_name ?? '') }}"
               placeholder="Tự điền nếu API trả về">
    </div>
    <div class="col-md-3">
        <label class="form-label">Ngày đăng (sắp xếp mới nhất)</label>
        <input type="datetime-local" name="published_at" class="form-control"
               value="{{ old('published_at', isset($item->published_at) ? $item->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Thứ tự (phụ)</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $item->sort_order ?? 0) }}" min="0">
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $item->is_active ?? true))>
            <label class="form-check-label" for="is_active">Hiển thị trên dải video trang shop</label>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var urlInput = document.getElementById('socialVideoUrl');
    var btn = document.getElementById('socialVideoFetchBtn');
    var statusEl = document.getElementById('socialVideoFetchStatus');
    var wrap = document.getElementById('socialVideoPreviewWrap');
    var thumb = document.getElementById('socialVideoThumbPreview');
    var metaLine = document.getElementById('socialVideoMetaLine');
    var titleInput = document.getElementById('socialVideoTitle');
    var platformSelect = document.getElementById('socialVideoPlatform');
    var channelInput = document.getElementById('socialVideoChannel');
    if (!urlInput || !btn) return;

    var previewUrl = @json(route('admin.videos.preview'));
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';

    function setStatus(text, isError) {
        if (!statusEl) return;
        statusEl.hidden = !text;
        statusEl.textContent = text || '';
        statusEl.className = 'small mt-1 ' + (isError ? 'text-danger' : 'text-secondary');
    }

    function applyMeta(data) {
        if (wrap) wrap.hidden = false;
        if (data.thumbnail && thumb) {
            thumb.src = data.thumbnail;
            thumb.style.display = '';
        }
        if (metaLine) {
            var bits = [data.platform_label || data.platform || ''];
            if (data.channel_name) bits.push(data.channel_name);
            metaLine.textContent = bits.filter(Boolean).join(' · ');
        }
        if (data.title && titleInput && !titleInput.value.trim()) {
            titleInput.value = data.title;
        }
        if (data.channel_name && channelInput && !channelInput.value.trim()) {
            channelInput.value = data.channel_name;
        }
        if (data.platform && platformSelect && !platformSelect.value) {
            platformSelect.value = data.platform;
        }
        if (data.url && data.url !== urlInput.value) {
            urlInput.value = data.url;
        }
    }

    function fetchMeta() {
        var url = (urlInput.value || '').trim();
        if (!url) {
            setStatus('Nhập link video/live trước.', true);
            return;
        }
        btn.disabled = true;
        setStatus('Đang lấy thumbnail & thông tin từ mạng xã hội…');

        fetch(previewUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ url: url })
        })
            .then(function (r) {
                return r.json().then(function (j) {
                    if (!r.ok) throw new Error(j.message || 'Không lấy được metadata');
                    return j;
                });
            })
            .then(function (j) {
                if (!j.ok) throw new Error('Không lấy được metadata');
                applyMeta(j);
                setStatus('Đã lấy thumbnail tự động. Có thể chỉnh tiêu đề/kênh rồi lưu.');
            })
            .catch(function (err) {
                setStatus(err.message || 'Lỗi khi lấy thông tin. Vẫn có thể lưu — hệ thống sẽ thử lại khi lưu.', true);
            })
            .finally(function () {
                btn.disabled = false;
            });
    }

    btn.addEventListener('click', fetchMeta);

    // Auto-fetch when paste URL (debounced)
    var pasteTimer = null;
    urlInput.addEventListener('paste', function () {
        clearTimeout(pasteTimer);
        pasteTimer = setTimeout(fetchMeta, 350);
    });
})();
</script>
@endpush
