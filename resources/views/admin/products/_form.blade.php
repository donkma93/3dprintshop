@php($item = $product ?? null)
<div class="row g-3 mb-3">
    <div class="col-md-8">
        <label class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Danh mục</label>
        <select name="category_id" id="product_category_id" class="form-select">
            <option value="">— Chọn danh mục —</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}"
                        data-sku-prefix="{{ $cat->sku_prefix }}"
                        @selected(old('category_id', $item->category_id ?? '') == $cat->id)>
                    {{ $cat->name }}@if($cat->sku_prefix) ({{ $cat->sku_prefix }})@endif
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">SKU</label>
        <div class="input-group">
            <input type="text" name="sku" id="product_sku" value="{{ old('sku', $item->sku ?? '') }}"
                   class="form-control text-uppercase" placeholder="Tự sinh khi chọn danh mục"
                   autocomplete="off">
            <button type="button" class="btn btn-outline-secondary" id="btn_regen_sku" title="Sinh lại SKU theo danh mục">
                Sinh lại
            </button>
        </div>
        <div class="form-text" id="sku_hint">Chọn danh mục để tự sinh mã SKU (vd: MHT-0001). Mã không trùng.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $item->slug ?? '') }}" class="form-control" placeholder="tự tạo nếu để trống">
    </div>
    <div class="col-12">
        <label class="form-label">Ảnh sản phẩm</label>
        <div class="product-image-uploader border rounded-3 p-3 bg-white" id="product_image_uploader"
             data-max-edge="1200" data-quality="0.82" data-max-bytes="10485760">
            <div class="row g-3 align-items-start">
                <div class="col-md-4 col-lg-3">
                    <div class="product-image-preview position-relative rounded-3 border bg-light overflow-hidden"
                         style="aspect-ratio:1; min-height:180px; width:100%;">
                        <img id="product_image_preview"
                             @if(!empty($item?->image)) src="{{ $item->image_url }}" @endif
                             alt="Xem trước ảnh sản phẩm"
                             class="{{ empty($item?->image) ? 'd-none' : '' }}"
                             style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block;">
                        <div id="product_image_placeholder"
                             class="position-absolute top-50 start-50 translate-middle text-center text-secondary p-3 {{ !empty($item?->image) ? 'd-none' : '' }}">
                            <i class="bi bi-image fs-2 d-block mb-1"></i>
                            <span class="small">Chưa có ảnh</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 col-lg-9">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-dark btn-sm" id="btn_pick_gallery">
                            <i class="bi bi-images"></i> Chọn từ thư viện
                        </button>
                        <button type="button" class="btn btn-outline-dark btn-sm" id="btn_open_camera">
                            <i class="bi bi-camera"></i> Chụp bằng camera
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="btn_clear_image">
                            <i class="bi bi-x-lg"></i> Xóa ảnh chọn
                        </button>
                    </div>
                    <input type="file" name="image" id="product_image_input" class="d-none"
                           accept="image/*" capture="environment">
                    <input type="file" id="product_image_gallery" class="d-none" accept="image/*">
                    <div class="small text-secondary" id="product_image_hint">
                        Chọn ảnh từ thư viện hoặc chụp camera. Ảnh sẽ tự resize tối đa 1200px (giữ tỉ lệ) và nén trước khi lưu.
                    </div>
                    <div class="small text-secondary mt-1" id="product_image_meta"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal camera (desktop / trình duyệt hỗ trợ getUserMedia) --}}
    <div class="modal fade" id="productCameraModal" tabindex="-1" aria-labelledby="productCameraModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="productCameraModalLabel">Chụp ảnh sản phẩm</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="position-relative bg-dark rounded overflow-hidden" style="aspect-ratio:4/3;">
                        <video id="product_camera_video" class="w-100 h-100" playsinline autoplay muted
                               style="object-fit:cover;"></video>
                        <canvas id="product_camera_canvas" class="d-none"></canvas>
                    </div>
                    <div class="small text-secondary mt-2" id="product_camera_status">
                        Cho phép trình duyệt dùng camera để chụp ảnh sản phẩm.
                    </div>
                </div>
                <div class="modal-footer flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="btn_switch_camera" title="Đổi camera">
                        <i class="bi bi-arrow-repeat"></i> Đổi camera
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-dark" id="btn_capture_photo">
                        <i class="bi bi-camera-fill"></i> Chụp ảnh
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label">Mô tả ngắn</label>
        <input type="text" name="short_description" value="{{ old('short_description', $item->short_description ?? '') }}" class="form-control" maxlength="500">
    </div>
    <div class="col-12">
        <label class="form-label">Mô tả chi tiết</label>
        <textarea name="description" rows="5" class="form-control">{{ old('description', $item->description ?? '') }}</textarea>
    </div>

    <div class="col-12"><hr><h3 class="h6 fw-bold mb-0">Giá &amp; khuyến mãi</h3>
        <div class="small text-secondary mb-2">Giá bán là giá gốc. Giá khuyến mãi (nếu có và thấp hơn giá gốc) sẽ hiển thị gạch giá trên cửa hàng.</div>
    </div>
    <div class="col-md-3">
        <label class="form-label">Giá bán (VNĐ) <span class="text-danger">*</span></label>
        <input type="number" step="1000" min="0" name="price" value="{{ old('price', $item->price ?? 0) }}" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Giá khuyến mãi (VNĐ)</label>
        <input type="number" step="1000" min="0" name="sale_price" value="{{ old('sale_price', $item->sale_price ?? '') }}" class="form-control" placeholder="Để trống = không giảm">
    </div>
    <div class="col-md-3">
        <label class="form-label">Nhãn KM</label>
        <input type="text" name="promo_label" value="{{ old('promo_label', $item->promo_label ?? '') }}" class="form-control" placeholder="Sale, Flash sale, -20%..." maxlength="80">
    </div>
    <div class="col-md-3">
        <label class="form-label">Tồn kho thành phẩm</label>
        <input type="number" min="0" name="stock" value="{{ old('stock', $item->stock ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-3">
        <label class="form-label">Bắt đầu KM</label>
        <input type="datetime-local" name="sale_starts_at" class="form-control"
               value="{{ old('sale_starts_at', optional($item->sale_starts_at ?? null)->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Kết thúc KM</label>
        <input type="datetime-local" name="sale_ends_at" class="form-control"
               value="{{ old('sale_ends_at', optional($item->sale_ends_at ?? null)->format('Y-m-d\TH:i')) }}">
    </div>
    @if(auth()->user()->canViewRevenue())
    <div class="col-md-3">
        <label class="form-label">Giá thành ước tính</label>
        <input type="number" step="1000" min="0" name="cost_price" value="{{ old('cost_price', $item->cost_price ?? 0) }}" class="form-control">
    </div>
    @elseif(!empty($item))
        <input type="hidden" name="cost_price" value="{{ $item->cost_price ?? 0 }}">
    @endif
    <div class="col-md-3">
        <label class="form-label">Khối lượng (gram)</label>
        <input type="number" step="0.01" min="0" name="weight_grams" value="{{ old('weight_grams', $item->weight_grams ?? '') }}" class="form-control">
    </div>
    <div class="col-md-8">
        <label class="form-label">Nguyên liệu / nhựa sử dụng</label>
        <input type="text" name="material_used" value="{{ old('material_used', $item->material_used ?? '') }}" class="form-control" placeholder="PLA Trắng, PETG...">
    </div>
    <div class="col-md-4">
        <label class="form-label">Thứ tự hiển thị</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}" class="form-control">
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                   @checked(old('is_active', $item->is_active ?? true))>
            <label class="form-check-label" for="is_active">Hiển thị trên cửa hàng</label>
        </div>
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured"
                   @checked(old('is_featured', $item->is_featured ?? false))>
            <label class="form-check-label" for="is_featured">Sản phẩm nổi bật</label>
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
        <input type="file" name="og_image" id="product_og_image" class="form-control" accept="image/*">
        @if(!empty($item?->og_image))
            <img src="{{ asset('storage/'.$item->og_image) }}" class="mt-2 rounded" height="60" alt="">
        @endif
        <div class="form-text">Ảnh mạng xã hội cũng được resize tối đa 1200px.</div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    // —— SKU theo danh mục ——
    var categorySelect = document.getElementById('product_category_id');
    var skuInput = document.getElementById('product_sku');
    var regenBtn = document.getElementById('btn_regen_sku');
    var hint = document.getElementById('sku_hint');

    if (categorySelect && skuInput) {
        var nextSkuUrl = @json(route('admin.products.next-sku'));
        var productId = @json($item->id ?? null);
        var initialSku = (skuInput.value || '').trim();
        var autoManaged = !initialSku;
        var lastFetched = '';
        var requestSeq = 0;

        function setHint(text, isError) {
            if (!hint) return;
            hint.textContent = text;
            hint.classList.toggle('text-danger', !!isError);
            hint.classList.toggle('text-secondary', !isError);
        }

        function fetchNextSku(force) {
            var categoryId = categorySelect.value;
            if (!categoryId) {
                if (autoManaged || force) {
                    skuInput.value = '';
                    setHint('Chọn danh mục để tự sinh mã SKU (vd: MHT-0001). Mã không trùng.');
                }
                return;
            }

            var prefix = categorySelect.options[categorySelect.selectedIndex]?.getAttribute('data-sku-prefix') || '';
            var seq = ++requestSeq;
            setHint('Đang sinh SKU' + (prefix ? ' theo mã ' + prefix : '') + '…');

            var url = nextSkuUrl + '?category_id=' + encodeURIComponent(categoryId);
            if (productId) {
                url += '&product_id=' + encodeURIComponent(productId);
            }

            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            }).then(function (r) {
                if (!r.ok) throw new Error('next-sku failed');
                return r.json();
            }).then(function (data) {
                if (seq !== requestSeq) return;
                if (!data || !data.sku) throw new Error('empty sku');
                lastFetched = data.sku;
                if (force || autoManaged || !skuInput.value.trim()) {
                    skuInput.value = data.sku;
                    autoManaged = true;
                }
                setHint('SKU theo danh mục' + (data.sku_prefix ? ' ' + data.sku_prefix : '') + ': ' + data.sku + ' (duy nhất).');
            }).catch(function () {
                if (seq !== requestSeq) return;
                setHint('Không lấy được SKU. Bạn có thể nhập thủ công hoặc bấm Sinh lại.', true);
            });
        }

        categorySelect.addEventListener('change', function () {
            autoManaged = true;
            fetchNextSku(true);
        });

        regenBtn?.addEventListener('click', function () {
            autoManaged = true;
            fetchNextSku(true);
        });

        skuInput.addEventListener('input', function () {
            var val = skuInput.value.trim();
            if (val && val !== lastFetched) autoManaged = false;
            if (!val) autoManaged = true;
        });

        if (categorySelect.value && (!initialSku || autoManaged)) {
            fetchNextSku(true);
        }
    }
})();

(function () {
    // —— Upload ảnh: thư viện + camera + resize client ——
    var root = document.getElementById('product_image_uploader');
    if (!root) return;

    var maxEdge = parseInt(root.getAttribute('data-max-edge') || '1200', 10) || 1200;
    var quality = parseFloat(root.getAttribute('data-quality') || '0.82') || 0.82;
    var maxBytes = parseInt(root.getAttribute('data-max-bytes') || '10485760', 10) || 10485760;

    var fileInput = document.getElementById('product_image_input');
    var galleryInput = document.getElementById('product_image_gallery');
    var preview = document.getElementById('product_image_preview');
    var placeholder = document.getElementById('product_image_placeholder');
    var metaEl = document.getElementById('product_image_meta');
    var hintEl = document.getElementById('product_image_hint');
    var clearBtn = document.getElementById('btn_clear_image');
    var pickBtn = document.getElementById('btn_pick_gallery');
    var openCamBtn = document.getElementById('btn_open_camera');
    var captureBtn = document.getElementById('btn_capture_photo');
    var switchBtn = document.getElementById('btn_switch_camera');
    var video = document.getElementById('product_camera_video');
    var canvas = document.getElementById('product_camera_canvas');
    var camStatus = document.getElementById('product_camera_status');
    var modalEl = document.getElementById('productCameraModal');
    var ogInput = document.getElementById('product_og_image');

    var stream = null;
    var facingMode = 'environment';
    var objectUrl = null;
    var modal = null;
    try {
        if (modalEl && window.bootstrap && bootstrap.Modal) {
            modal = bootstrap.Modal.getOrCreateInstance
                ? bootstrap.Modal.getOrCreateInstance(modalEl)
                : new bootstrap.Modal(modalEl);
        }
    } catch (e) {
        modal = null;
    }

    var existingImageUrl = @json(!empty($item?->image) ? $item->image_url : '');

    function formatBytes(n) {
        if (n < 1024) return n + ' B';
        if (n < 1048576) return (n / 1024).toFixed(1) + ' KB';
        return (n / 1048576).toFixed(2) + ' MB';
    }

    function setHint(text, isError) {
        if (!hintEl) return;
        hintEl.textContent = text;
        hintEl.classList.toggle('text-danger', !!isError);
        hintEl.classList.toggle('text-secondary', !isError);
    }

    function revokePreviewUrl() {
        if (objectUrl) {
            try { URL.revokeObjectURL(objectUrl); } catch (e) {}
            objectUrl = null;
        }
    }

    function showPreview(url) {
        if (!preview || !url) return;

        // Revoke blob cũ trước khi gán blob mới (không revoke chính url đang gán)
        if (objectUrl && objectUrl !== url) {
            try { URL.revokeObjectURL(objectUrl); } catch (e) {}
            objectUrl = null;
        }
        if (String(url).indexOf('blob:') === 0) {
            objectUrl = url;
        }

        preview.onerror = function () {
            setHint('Không hiển thị được ảnh xem trước. Thử chọn lại.', true);
        };
        preview.src = url;
        preview.classList.remove('d-none');
        preview.removeAttribute('hidden');
        preview.style.display = 'block';
        preview.style.visibility = 'visible';
        preview.style.opacity = '1';
        if (placeholder) {
            placeholder.classList.add('d-none');
            placeholder.style.display = 'none';
        }
        if (clearBtn) clearBtn.classList.remove('d-none');
    }

    function clearSelection() {
        if (fileInput) fileInput.value = '';
        if (galleryInput) galleryInput.value = '';
        revokePreviewUrl();
        if (preview) {
            if (existingImageUrl) {
                preview.src = existingImageUrl;
                preview.classList.remove('d-none');
                preview.style.display = 'block';
                if (placeholder) {
                    placeholder.classList.add('d-none');
                    placeholder.style.display = 'none';
                }
            } else {
                preview.removeAttribute('src');
                preview.classList.add('d-none');
                if (placeholder) {
                    placeholder.classList.remove('d-none');
                    placeholder.style.display = '';
                }
            }
        }
        if (clearBtn) clearBtn.classList.add('d-none');
        if (metaEl) metaEl.textContent = '';
        setHint('Chọn ảnh từ thư viện hoặc chụp camera. Ảnh sẽ tự resize tối đa ' + maxEdge + 'px (giữ tỉ lệ) và nén trước khi lưu.');
    }

    function loadImageFromFile(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () {
                // Không revoke ngay — giữ đến khi decode xong drawImage
                resolve({ img: img, url: url });
            };
            img.onerror = function () {
                try { URL.revokeObjectURL(url); } catch (e) {}
                // Fallback FileReader (một số trình duyệt / HEIC đổi tên)
                var reader = new FileReader();
                reader.onload = function () {
                    var img2 = new Image();
                    img2.onload = function () { resolve({ img: img2, url: null }); };
                    img2.onerror = function () { reject(new Error('Không đọc được ảnh.')); };
                    img2.src = reader.result;
                };
                reader.onerror = function () { reject(new Error('Không đọc được ảnh.')); };
                reader.readAsDataURL(file);
            };
            img.src = url;
        });
    }

    function canvasToBlob(cvs, type, q) {
        return new Promise(function (resolve) {
            if (cvs.toBlob) {
                cvs.toBlob(function (blob) { resolve(blob || null); }, type, q);
                return;
            }
            try {
                var dataUrl = cvs.toDataURL(type, q);
                var parts = dataUrl.split(',');
                var bin = atob(parts[1] || '');
                var arr = new Uint8Array(bin.length);
                for (var i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
                resolve(new Blob([arr], { type: type }));
            } catch (e) {
                resolve(null);
            }
        });
    }

    function makeUploadFile(blob, originalName) {
        var base = (originalName || 'product').replace(/\.[^.]+$/, '');
        var name = base.replace(/[^\w\-]+/g, '_').slice(0, 60) || 'product';
        name = name + '.jpg';
        try {
            if (typeof File !== 'undefined') {
                return new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() });
            }
        } catch (e) {}
        // Fallback Blob có name (DataTransfer chấp nhận Blob + gán name)
        blob.name = name;
        blob.lastModified = Date.now();
        return blob;
    }

    function resizeImageFile(file) {
        return loadImageFromFile(file).then(function (loaded) {
            var img = loaded.img;
            var tempUrl = loaded.url;
            var w = img.naturalWidth || img.width;
            var h = img.naturalHeight || img.height;
            if (!w || !h) {
                if (tempUrl) try { URL.revokeObjectURL(tempUrl); } catch (e) {}
                throw new Error('Kích thước ảnh không hợp lệ.');
            }

            var scale = Math.min(1, maxEdge / Math.max(w, h));
            var nw = Math.max(1, Math.round(w * scale));
            var nh = Math.max(1, Math.round(h * scale));

            var cvs = document.createElement('canvas');
            cvs.width = nw;
            cvs.height = nh;
            var ctx = cvs.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, nw, nh);
            ctx.drawImage(img, 0, 0, nw, nh);
            if (tempUrl) try { URL.revokeObjectURL(tempUrl); } catch (e) {}

            return canvasToBlob(cvs, 'image/jpeg', quality).then(function (blob) {
                if (!blob) throw new Error('Không nén được ảnh trên trình duyệt.');
                var out = makeUploadFile(blob, file.name || 'product.jpg');
                return {
                    file: out,
                    width: nw,
                    height: nh,
                    original: { width: w, height: h, size: file.size || 0 }
                };
            });
        });
    }

    function assignToInput(file) {
        if (!fileInput) return false;
        try {
            var dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            return !!(fileInput.files && fileInput.files.length);
        } catch (e) {
            return false;
        }
    }

    function isLikelyImage(file) {
        if (!file) return false;
        if (file.type && file.type.indexOf('image/') === 0) return true;
        // Một số máy ảnh / HEIC đổi type rỗng — kiểm tra extension
        var name = (file.name || '').toLowerCase();
        return /\.(jpe?g|png|gif|webp|bmp|heic|heif)$/i.test(name);
    }

    function processFile(file) {
        if (!file) return;
        if (!isLikelyImage(file)) {
            setHint('File không phải ảnh. Chọn JPG, PNG, WEBP…', true);
            return;
        }
        if (file.size && file.size > maxBytes) {
            setHint('Ảnh quá lớn (tối đa ' + formatBytes(maxBytes) + ').', true);
            return;
        }

        // Hiện preview ngay từ file gốc (để user thấy liền), rồi mới resize
        try {
            var quickUrl = URL.createObjectURL(file);
            showPreview(quickUrl);
        } catch (e) {}

        setHint('Đang resize & nén ảnh…');
        resizeImageFile(file).then(function (result) {
            var assigned = assignToInput(result.file);
            var previewUrl = URL.createObjectURL(result.file);
            showPreview(previewUrl);
            if (metaEl) {
                metaEl.textContent = 'Gốc: ' + result.original.width + '×' + result.original.height +
                    ' (' + formatBytes(result.original.size) + ') → Web: ' +
                    result.width + '×' + result.height + ' (' + formatBytes(result.file.size || 0) + ', JPEG)';
            }
            if (!assigned) {
                setHint('Đã xem trước & tối ưu, nhưng trình duyệt hạn chế gán file. Hãy dùng nút Chọn từ thư viện rồi gửi form.', true);
            } else {
                setHint('Đã tối ưu ảnh cho web (tối đa ' + maxEdge + 'px, JPEG). Server sẽ kiểm tra lại khi lưu.');
            }
        }).catch(function (err) {
            // Nếu resize lỗi nhưng đã có preview gốc — vẫn giữ preview
            setHint(err && err.message ? err.message : 'Không xử lý được ảnh.', true);
        });
    }

    if (pickBtn) {
        pickBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (galleryInput) {
                galleryInput.value = '';
                galleryInput.click();
            }
        });
    }

    if (galleryInput) {
        galleryInput.addEventListener('change', function () {
            var f = galleryInput.files && galleryInput.files[0];
            if (f) processFile(f);
        });
    }

    // capture input: mobile native camera / file picker with camera option
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var f = fileInput.files && fileInput.files[0];
            if (f) processFile(f);
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function (e) {
            e.preventDefault();
            clearSelection();
        });
    }

    // OG image: resize nhẹ trước submit
    ogInput?.addEventListener('change', function () {
        var f = ogInput.files && ogInput.files[0];
        if (!f || !f.type || f.type.indexOf('image/') !== 0) return;
        resizeImageFile(f).then(function (result) {
            try {
                var dt = new DataTransfer();
                dt.items.add(result.file);
                ogInput.files = dt.files;
            } catch (e) {}
        }).catch(function () {});
    });

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video) video.srcObject = null;
    }

    function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return Promise.reject(new Error('Trình duyệt không hỗ trợ camera API.'));
        }
        stopCamera();
        return navigator.mediaDevices.getUserMedia({
            audio: false,
            video: {
                facingMode: { ideal: facingMode },
                width: { ideal: 1280 },
                height: { ideal: 960 }
            }
        }).then(function (s) {
            stream = s;
            if (video) {
                video.srcObject = s;
                return video.play().catch(function () {});
            }
        });
    }

    function openNativeCameraFallback() {
        // Mobile: capture=environment mở camera trực tiếp
        if (fileInput) {
            fileInput.setAttribute('capture', 'environment');
            fileInput.click();
        }
    }

    openCamBtn?.addEventListener('click', function () {
        var hasMedia = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
        var isTouch = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;

        // Ưu tiên camera native trên mobile (ổn định hơn getUserMedia trong file form)
        if (isTouch || !hasMedia || !modal) {
            openNativeCameraFallback();
            return;
        }

        if (camStatus) camStatus.textContent = 'Đang bật camera…';
        modal.show();
        startCamera().then(function () {
            if (camStatus) camStatus.textContent = 'Căn khung ảnh sản phẩm rồi bấm Chụp ảnh.';
        }).catch(function (err) {
            if (camStatus) {
                camStatus.textContent = (err && err.message)
                    ? err.message + ' Thử chọn từ thư viện hoặc camera hệ thống.'
                    : 'Không mở được camera.';
            }
            // fallback native
            try { modal.hide(); } catch (e) {}
            openNativeCameraFallback();
        });
    });

    switchBtn?.addEventListener('click', function () {
        facingMode = facingMode === 'environment' ? 'user' : 'environment';
        startCamera().catch(function () {
            if (camStatus) camStatus.textContent = 'Không đổi được camera.';
        });
    });

    captureBtn?.addEventListener('click', function () {
        if (!video || !canvas) return;
        var vw = video.videoWidth || 0;
        var vh = video.videoHeight || 0;
        if (!vw || !vh) {
            if (camStatus) camStatus.textContent = 'Camera chưa sẵn sàng, thử lại.';
            return;
        }
        canvas.width = vw;
        canvas.height = vh;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, vw, vh);
        canvas.toBlob(function (blob) {
            if (!blob) {
                if (camStatus) camStatus.textContent = 'Chụp thất bại.';
                return;
            }
            var file = new File([blob], 'camera-' + Date.now() + '.jpg', {
                type: 'image/jpeg',
                lastModified: Date.now()
            });
            processFile(file);
            try { modal.hide(); } catch (e) {}
            stopCamera();
        }, 'image/jpeg', 0.92);
    });

    modalEl?.addEventListener('hidden.bs.modal', function () {
        stopCamera();
    });
})();
</script>
@endpush
