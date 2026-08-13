@extends('layouts.admin')

@section('title', 'Quét QR bán hàng')
@section('subtitle', 'Bán nội bộ — khách hàng, giao hàng, giảm tồn & doanh thu')

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card p-4">
            <h2 class="h5 fw-bold mb-3"><i class="bi bi-qr-code-scan"></i> Quét hoặc nhập mã sản phẩm</h2>
            <p class="small text-secondary mb-3">
                Dùng camera điện thoại để quét tem QR trên sản phẩm, hoặc nhập mã QR / SKU thủ công.
                Ghi thông tin khách (chat web, điện thoại, liên hệ…) và địa chỉ gửi hàng nếu cần in phiếu CPN.
            </p>

            <form method="GET" action="{{ route('admin.sales.scan') }}" class="mb-3" id="lookupForm">
                <label class="form-label">Mã quét / SKU</label>
                <div class="input-group">
                    <input type="text" name="code" id="scan_code" value="{{ $lookup }}"
                           class="form-control form-control-lg" placeholder="QLBH|v1|XXXX hoặc SKU" autocomplete="off" autofocus>
                    <button class="btn btn-dark" type="submit">Tìm</button>
                </div>
            </form>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-outline-dark btn-sm" id="btn_start_scanner">
                    <i class="bi bi-camera"></i> Bật camera quét QR
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="btn_stop_scanner">
                    Tắt camera
                </button>
            </div>

            <div id="qr_reader_wrap" class="d-none mb-3">
                <div id="qr_reader" class="rounded border overflow-hidden bg-dark" style="max-width:420px"></div>
                <div class="small text-secondary mt-1" id="scanner_status">Đang khởi động camera…</div>
            </div>

            @if($error)
                <div class="alert alert-warning">{{ $error }}</div>
            @endif

            @if($product)
                <div class="border rounded-3 p-3 bg-light" id="product_card">
                    <div class="d-flex gap-3 align-items-start mb-3">
                        <img src="{{ $product->image_url }}" alt="" width="88" height="88" class="rounded object-fit-cover" style="object-fit:cover">
                        <div class="flex-grow-1">
                            <div class="fw-bold fs-5">{{ $product->name }}</div>
                            <div class="small text-secondary mb-1">
                                SKU: <code>{{ $product->sku ?: '—' }}</code>
                                · QR: <code>{{ $product->qr_token }}</code>
                            </div>
                            <div>
                                Tồn kho:
                                <span class="badge {{ $product->stock > 0 ? 'badge-soft' : 'text-bg-danger' }} fs-6">
                                    {{ $product->stock }}
                                </span>
                                · Giá bán:
                                <strong>{{ number_format($product->final_price, 0, ',', '.') }} đ</strong>
                                @if($product->is_on_sale)
                                    <span class="badge text-bg-danger">Sale</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($product->stock <= 0)
                        <div class="alert alert-danger py-2 mb-0">Sản phẩm hết hàng — không thể bán.</div>
                    @else
                        <form method="POST" action="{{ route('admin.sales.sell') }}" id="sellForm">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="scan_payload" value="{{ $lookup }}">

                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-4 col-md-3">
                                    <label class="form-label">Số lượng <span class="text-danger">*</span></label>
                                    <input type="number" name="quantity" min="1" max="{{ $product->stock }}"
                                           value="{{ old('quantity', 1) }}" class="form-control" required>
                                </div>
                                <div class="col-8 col-md-4">
                                    <label class="form-label">Giá bán / sp (đ)</label>
                                    <input type="number" name="unit_price" min="0" step="1000"
                                           value="{{ old('unit_price', (int) $product->final_price) }}" class="form-control">
                                    <div class="form-text">Mặc định = giá hiện tại</div>
                                </div>
                                <div class="col-12 col-md-5">
                                    <label class="form-label">Ghi chú nội bộ</label>
                                    <input type="text" name="note" class="form-control" maxlength="500"
                                           value="{{ old('note') }}" placeholder="Ghi chú đơn…">
                                </div>
                            </div>

                            <div class="border rounded-3 p-3 bg-white mb-3">
                                <h3 class="h6 fw-bold mb-3"><i class="bi bi-person"></i> Thông tin khách hàng</h3>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Họ tên</label>
                                        <input type="text" name="customer_name" id="customer_name" class="form-control" maxlength="120"
                                               value="{{ old('customer_name') }}" placeholder="Nguyễn Văn A" autocomplete="name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="tel" name="customer_phone" id="customer_phone" class="form-control" maxlength="40"
                                               value="{{ old('customer_phone') }}" placeholder="09xx…" autocomplete="tel">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="customer_email" class="form-control" maxlength="120"
                                               value="{{ old('customer_email') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nguồn khách</label>
                                        <select name="customer_source" class="form-select">
                                            <option value="">— Chọn —</option>
                                            @foreach($sourceOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(old('customer_source') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Số nhà, đường / thôn xóm</label>
                                        <input type="text" name="customer_address" id="customer_address" class="form-control" maxlength="500"
                                               value="{{ old('customer_address') }}" placeholder="Số 12, ngõ 5, đường Nguyễn Trãi">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Phường / Xã</label>
                                        <input type="text" name="customer_ward" id="customer_ward" class="form-control" maxlength="120"
                                               value="{{ old('customer_ward') }}" placeholder="Phường…">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Quận / Huyện</label>
                                        <input type="text" name="customer_district" id="customer_district" class="form-control" maxlength="120"
                                               value="{{ old('customer_district') }}" placeholder="Quận / Huyện…">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tỉnh / Thành phố</label>
                                        <input type="text" name="customer_province" id="customer_province" class="form-control" maxlength="120"
                                               value="{{ old('customer_province') }}" placeholder="Hà Nội, TP.HCM…">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Mã bưu chính</label>
                                        <input type="text" name="customer_postal_code" id="customer_postal_code" class="form-control" maxlength="20"
                                               value="{{ old('customer_postal_code') }}" placeholder="Tùy chọn">
                                    </div>
                                </div>
                            </div>

                            <div class="border rounded-3 p-3 bg-white mb-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                    <div class="form-check form-switch mb-0">
                                        <input type="hidden" name="needs_shipping" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="needs_shipping"
                                               name="needs_shipping" value="1" @checked(old('needs_shipping'))>
                                        <label class="form-check-label fw-semibold" for="needs_shipping">
                                            <i class="bi bi-truck"></i> Cần gửi hàng (in phiếu dán kiện CPN)
                                        </label>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_copy_customer_addr">
                                        <i class="bi bi-copy"></i> Chép địa chỉ KH → người nhận
                                    </button>
                                </div>

                                <div id="shipping_fields" class="{{ old('needs_shipping') ? '' : 'd-none' }}">
                                    <p class="small text-secondary mb-3">
                                        Điền đủ địa chỉ giao để in phiếu gửi. Để trống người nhận / địa chỉ → hệ thống dùng thông tin khách.
                                        Người gửi lấy từ <a href="{{ route('admin.settings.edit') }}" target="_blank">Cài đặt shop</a>.
                                    </p>

                                    <h4 class="h6 fw-semibold text-secondary mb-2">Người nhận &amp; địa chỉ giao</h4>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Họ tên người nhận <span class="text-danger">*</span></label>
                                            <input type="text" name="receiver_name" id="receiver_name" class="form-control" maxlength="120"
                                                   value="{{ old('receiver_name') }}" placeholder="Mặc định = tên KH">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">SĐT người nhận <span class="text-danger">*</span></label>
                                            <input type="tel" name="receiver_phone" id="receiver_phone" class="form-control" maxlength="40"
                                                   value="{{ old('receiver_phone') }}" placeholder="Mặc định = SĐT KH">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Số nhà, đường / thôn xóm <span class="text-danger">*</span></label>
                                            <input type="text" name="receiver_address" id="receiver_address" class="form-control" maxlength="500"
                                                   value="{{ old('receiver_address') }}" placeholder="Số nhà, ngõ, đường…">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Phường / Xã</label>
                                            <input type="text" name="receiver_ward" id="receiver_ward" class="form-control" maxlength="120"
                                                   value="{{ old('receiver_ward') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Quận / Huyện</label>
                                            <input type="text" name="receiver_district" id="receiver_district" class="form-control" maxlength="120"
                                                   value="{{ old('receiver_district') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tỉnh / Thành phố</label>
                                            <input type="text" name="receiver_province" id="receiver_province" class="form-control" maxlength="120"
                                                   value="{{ old('receiver_province') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Mã bưu chính</label>
                                            <input type="text" name="receiver_postal_code" id="receiver_postal_code" class="form-control" maxlength="20"
                                                   value="{{ old('receiver_postal_code') }}">
                                        </div>
                                    </div>

                                    <h4 class="h6 fw-semibold text-secondary mb-2">Thông tin kiện &amp; vận chuyển</h4>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label">Đơn vị vận chuyển</label>
                                            <select name="carrier" class="form-select">
                                                <option value="">— Chọn —</option>
                                                @foreach($carrierOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('carrier') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Dịch vụ</label>
                                            <select name="shipping_service" class="form-select">
                                                <option value="">— Chọn —</option>
                                                @foreach($serviceOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('shipping_service', 'standard') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Thanh toán</label>
                                            <select name="payment_method" class="form-select" id="payment_method">
                                                <option value="">— Chọn —</option>
                                                @foreach($paymentOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Thu hộ COD (đ)</label>
                                            <input type="number" name="cod_amount" min="0" step="1000" class="form-control"
                                                   value="{{ old('cod_amount') }}" placeholder="Mặc định = tổng đơn">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Giá trị khai báo (đ)</label>
                                            <input type="number" name="declared_value" min="0" step="1000" class="form-control"
                                                   value="{{ old('declared_value') }}" placeholder="Mặc định = tổng đơn">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Số kiện</label>
                                            <input type="number" name="package_count" min="1" max="99" class="form-control"
                                                   value="{{ old('package_count', 1) }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">KL (gram)</label>
                                            <input type="number" name="package_weight" min="0" max="100000" class="form-control"
                                                   value="{{ old('package_weight', $product->weight_grams ? (int) $product->weight_grams : '') }}"
                                                   placeholder="g">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Nội dung hàng</label>
                                            <input type="text" name="goods_content" class="form-control" maxlength="255"
                                                   value="{{ old('goods_content', $product->name) }}"
                                                   placeholder="Mô tả hàng trên phiếu gửi">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Ghi chú gửi hàng / lưu ý shipper</label>
                                            <input type="text" name="shipping_note" class="form-control" maxlength="500"
                                                   value="{{ old('shipping_note') }}" placeholder="Hàng dễ vỡ, gọi trước khi giao…">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-success btn-lg"
                                        data-confirm="Xác nhận đánh dấu đã bán? Tồn kho sẽ giảm ngay."
                                        data-confirm-title="Bán sản phẩm">
                                    <i class="bi bi-check2-circle"></i> Đánh dấu đã bán
                                </button>
                                <a href="{{ route('admin.products.qr', $product) }}" class="btn btn-outline-secondary">Xem QR</a>
                            </div>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="h6 fw-bold mb-0">Giao dịch gần đây</h3>
                <a href="{{ route('admin.sales.history') }}" class="small">Xem tất cả</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th>SP / KH</th>
                        <th>SL</th>
                        <th>Tiền</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recent as $sale)
                        <tr>
                            <td>
                                <div class="small fw-semibold">{{ $sale->product?->name ?? '—' }}</div>
                                @if($sale->customer_name)
                                    <div class="text-secondary" style="font-size:.75rem">
                                        <i class="bi bi-person"></i> {{ $sale->customer_name }}
                                        @if($sale->customer_phone) · {{ $sale->customer_phone }} @endif
                                    </div>
                                @endif
                                <div class="text-secondary" style="font-size:.75rem">
                                    {{ optional($sale->sold_at)->format('d/m H:i') }}
                                    · {{ $sale->seller?->name ?? '—' }}
                                    @if($sale->needs_shipping)
                                        · <span class="text-primary">Gửi hàng</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $sale->quantity }}</td>
                            <td class="fw-semibold">{{ number_format($sale->total_price, 0, ',', '.') }}</td>
                            <td class="text-end">
                                @if($sale->needs_shipping)
                                    <a href="{{ route('admin.sales.print', $sale) }}" class="btn btn-sm btn-outline-dark" title="In phiếu gửi">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                @else
                                    <span class="small text-secondary">tồn {{ $sale->stock_after }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-secondary">Chưa có giao dịch bán.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if(auth()->user()->canViewRevenue())
        <div class="card p-3 mt-3">
            <a href="{{ route('admin.sales.report') }}" class="btn btn-outline-dark w-100">
                <i class="bi bi-graph-up-arrow"></i> Báo cáo doanh thu / lãi lỗ
            </a>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    var startBtn = document.getElementById('btn_start_scanner');
    var stopBtn = document.getElementById('btn_stop_scanner');
    var wrap = document.getElementById('qr_reader_wrap');
    var statusEl = document.getElementById('scanner_status');
    var codeInput = document.getElementById('scan_code');
    var form = document.getElementById('lookupForm');
    var scanner = null;
    var lastCode = '';
    var lastAt = 0;

    function setStatus(t) {
        if (statusEl) statusEl.textContent = t;
    }

    function onScan(decoded) {
        var now = Date.now();
        if (!decoded || (decoded === lastCode && now - lastAt < 2500)) return;
        lastCode = decoded;
        lastAt = now;
        if (codeInput) codeInput.value = decoded;
        setStatus('Đã quét: ' + decoded);
        if (navigator.vibrate) try { navigator.vibrate(80); } catch (e) {}
        stopScanner();
        if (form) form.submit();
    }

    function stopScanner() {
        if (scanner) {
            scanner.stop().then(function () {
                try { scanner.clear(); } catch (e) {}
                scanner = null;
            }).catch(function () { scanner = null; });
        }
        wrap && wrap.classList.add('d-none');
        startBtn && startBtn.classList.remove('d-none');
        stopBtn && stopBtn.classList.add('d-none');
    }

    function startScanner() {
        if (typeof Html5Qrcode === 'undefined') {
            setStatus('Không tải được thư viện quét QR. Hãy nhập mã thủ công.');
            return;
        }
        wrap && wrap.classList.remove('d-none');
        startBtn && startBtn.classList.add('d-none');
        stopBtn && stopBtn.classList.remove('d-none');
        setStatus('Đang bật camera…');

        scanner = new Html5Qrcode('qr_reader');
        scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 240, height: 240 } },
            onScan,
            function () {}
        ).then(function () {
            setStatus('Đưa tem QR vào khung hình.');
        }).catch(function (err) {
            setStatus('Không mở camera: ' + (err && err.message ? err.message : err) + '. Nhập mã thủ công.');
            stopBtn && stopBtn.classList.add('d-none');
            startBtn && startBtn.classList.remove('d-none');
        });
    }

    startBtn && startBtn.addEventListener('click', startScanner);
    stopBtn && stopBtn.addEventListener('click', stopScanner);
    window.addEventListener('beforeunload', stopScanner);

    var shipToggle = document.getElementById('needs_shipping');
    var shipFields = document.getElementById('shipping_fields');
    if (shipToggle && shipFields) {
        shipToggle.addEventListener('change', function () {
            shipFields.classList.toggle('d-none', !shipToggle.checked);
        });
    }

    var copyBtn = document.getElementById('btn_copy_customer_addr');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var map = [
                ['customer_name', 'receiver_name'],
                ['customer_phone', 'receiver_phone'],
                ['customer_address', 'receiver_address'],
                ['customer_ward', 'receiver_ward'],
                ['customer_district', 'receiver_district'],
                ['customer_province', 'receiver_province'],
                ['customer_postal_code', 'receiver_postal_code']
            ];
            map.forEach(function (pair) {
                var from = document.getElementById(pair[0]);
                var to = document.getElementById(pair[1]);
                if (from && to) to.value = from.value;
            });
            if (shipToggle && !shipToggle.checked) {
                shipToggle.checked = true;
                shipFields && shipFields.classList.remove('d-none');
            }
        });
    }
})();
</script>
@endpush
