@extends('layouts.admin')

@section('title', 'Hồ sơ thuế HKD')
@section('subtitle', 'Thông tin hộ kinh doanh & tham số ước tính thuế')

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.tax.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Tổng quan thuế</a>
</div>

<div class="card p-4">
    <form method="POST" action="{{ route('admin.tax.profile.update') }}">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-12"><h3 class="h6 fw-bold text-uppercase">Thông tin hộ kinh doanh</h3></div>
            <div class="col-md-6">
                <label class="form-label">Tên hộ / cửa hàng <span class="text-danger">*</span></label>
                <input type="text" name="business_name" class="form-control" value="{{ old('business_name', $profile->business_name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Chủ hộ</label>
                <input type="text" name="owner_name" class="form-control" value="{{ old('owner_name', $profile->owner_name) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Mã số thuế (MST)</label>
                <input type="text" name="tax_code" class="form-control" value="{{ old('tax_code', $profile->tax_code) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">CCCD / CMND</label>
                <input type="text" name="id_number" class="form-control" value="{{ old('id_number', $profile->id_number) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Ngành nghề đăng ký</label>
                <input type="text" name="business_line" class="form-control" value="{{ old('business_line', $profile->business_line) }}" placeholder="VD: Bán lẻ hàng hóa in 3D">
            </div>
            <div class="col-md-4">
                <label class="form-label">Điện thoại</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $profile->phone) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $profile->email) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Cơ quan thuế quản lý</label>
                <input type="text" name="tax_office" class="form-control" value="{{ old('tax_office', $profile->tax_office) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Địa chỉ (số nhà, đường)</label>
                <input type="text" name="address" class="form-control" value="{{ old('address', $profile->address) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Phường/Xã</label>
                <input type="text" name="ward" class="form-control" value="{{ old('ward', $profile->ward) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Quận/Huyện</label>
                <input type="text" name="district" class="form-control" value="{{ old('district', $profile->district) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Tỉnh/TP</label>
                <input type="text" name="province" class="form-control" value="{{ old('province', $profile->province) }}">
            </div>

            <div class="col-12"><hr><h3 class="h6 fw-bold text-uppercase">Tham số ước tính</h3></div>
            <div class="col-md-4">
                <label class="form-label">Phương pháp</label>
                <select name="method" class="form-select">
                    @foreach($methodOptions as $k => $label)
                        <option value="{{ $k }}" @selected(old('method', $profile->method) === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Chu kỳ kê khai</label>
                <select name="filing_cycle" class="form-select">
                    @foreach($cycleOptions as $k => $label)
                        <option value="{{ $k }}" @selected(old('filing_cycle', $profile->filing_cycle) === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">% GTGT ước tính</label>
                <input type="number" step="0.0001" min="0" max="100" name="vat_rate" class="form-control"
                       value="{{ old('vat_rate', $profile->vat_rate) }}" required>
                <div class="form-text">Mặc định TM ~1% (tham chiếu)</div>
            </div>
            <div class="col-md-2">
                <label class="form-label">% TNCN ước tính</label>
                <input type="number" step="0.0001" min="0" max="100" name="pit_rate" class="form-control"
                       value="{{ old('pit_rate', $profile->pit_rate) }}" required>
                <div class="form-text">Mặc định TM ~0.5% (tham chiếu)</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Ngưỡng doanh thu cảnh báo / năm (đ)</label>
                <input type="number" step="1" min="0" name="revenue_threshold" class="form-control"
                       value="{{ old('revenue_threshold', $profile->revenue_threshold) }}" placeholder="100000000">
            </div>
            <div class="col-md-4">
                <label class="form-label">Ngày hạn (trong tháng sau kỳ)</label>
                <input type="number" min="1" max="28" name="filing_day" class="form-control"
                       value="{{ old('filing_day', $profile->filing_day) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Offset tháng sau kỳ</label>
                <input type="number" min="0" max="6" name="filing_month_offset" class="form-control"
                       value="{{ old('filing_month_offset', $profile->filing_month_offset) }}" required>
                <div class="form-text">VD: kỳ Q1 kết thúc 31/3, offset=1, day=30 → hạn 30/4</div>
            </div>
            <div class="col-12">
                <label class="form-label">Ghi chú nội bộ</label>
                <textarea name="notes" rows="2" class="form-control">{{ old('notes', $profile->notes) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Disclaimer hiển thị</label>
                <textarea name="disclaimer" rows="2" class="form-control">{{ old('disclaimer', $profile->disclaimer) }}</textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-dark" type="submit"><i class="bi bi-check2"></i> Lưu hồ sơ</button>
            </div>
        </div>
    </form>
</div>
@endsection
