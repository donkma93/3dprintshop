@extends('layouts.admin')
@section('title', 'Cài đặt website')
@section('subtitle', 'Thông tin công ty, SEO mặc định, liên hệ')
@section('content')
<div class="card p-4">
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-12"><h3 class="h6 fw-bold text-uppercase">Thông tin chung</h3></div>
            <div class="col-md-6">
                <label class="form-label">Tên website <span class="text-danger">*</span></label>
                <input type="text" name="site_name" class="form-control" value="{{ old('site_name', $settings['site_name'] ?? '') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Khẩu hiệu</label>
                <input type="text" name="site_tagline" class="form-control" value="{{ old('site_tagline', $settings['site_tagline'] ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Logo</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
                @if(!empty($settings['logo']))
                    <img src="{{ asset('storage/'.$settings['logo']) }}" class="mt-2" height="40" alt="">
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label">Favicon</label>
                <input type="file" name="favicon" class="form-control" accept="image/*">
            </div>
            <div class="col-md-4">
                <label class="form-label">OG image mặc định</label>
                <input type="file" name="og_image" class="form-control" accept="image/*">
            </div>

            <div class="col-12"><hr><h3 class="h6 fw-bold text-uppercase">SEO mặc định</h3></div>
            <div class="col-md-6">
                <label class="form-label">Meta title trang chủ</label>
                <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $settings['meta_title'] ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Meta keywords</label>
                <input type="text" name="meta_keywords" class="form-control" value="{{ old('meta_keywords', $settings['meta_keywords'] ?? '') }}">
            </div>
            <div class="col-12">
                <label class="form-label">Meta description</label>
                <textarea name="meta_description" rows="2" class="form-control">{{ old('meta_description', $settings['meta_description'] ?? '') }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Google Analytics ID</label>
                <input type="text" name="google_analytics" class="form-control" value="{{ old('google_analytics', $settings['google_analytics'] ?? '') }}" placeholder="G-XXXXXXXX">
            </div>
            <div class="col-md-2">
                <label class="form-label">Geo region</label>
                <input type="text" name="geo_region" class="form-control" value="{{ old('geo_region', $settings['geo_region'] ?? 'VN') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Geo placename</label>
                <input type="text" name="geo_placename" class="form-control" value="{{ old('geo_placename', $settings['geo_placename'] ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Geo position</label>
                <input type="text" name="geo_position" class="form-control" value="{{ old('geo_position', $settings['geo_position'] ?? '') }}" placeholder="21.02;105.77">
            </div>

            <div class="col-12"><hr><h3 class="h6 fw-bold text-uppercase">Liên hệ</h3></div>
            <div class="col-md-4">
                <label class="form-label">Hotline</label>
                <input type="text" name="hotline" class="form-control" value="{{ old('hotline', $settings['hotline'] ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Điện thoại</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $settings['phone'] ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $settings['email'] ?? '') }}">
            </div>
            <div class="col-md-8">
                <label class="form-label">Địa chỉ</label>
                <input type="text" name="address" class="form-control" value="{{ old('address', $settings['address'] ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Giờ làm việc</label>
                <input type="text" name="working_hours" class="form-control" value="{{ old('working_hours', $settings['working_hours'] ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Facebook</label>
                <input type="text" name="facebook" class="form-control" value="{{ old('facebook', $settings['facebook'] ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Zalo</label>
                <input type="text" name="zalo" class="form-control" value="{{ old('zalo', $settings['zalo'] ?? '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">YouTube</label>
                <input type="text" name="youtube" class="form-control" value="{{ old('youtube', $settings['youtube'] ?? '') }}">
            </div>

            <div class="col-12"><hr><h3 class="h6 fw-bold text-uppercase">Nội dung trang chủ</h3></div>
            <div class="col-md-6">
                <label class="form-label">Tiêu đề giới thiệu</label>
                <input type="text" name="home_about_title" class="form-control" value="{{ old('home_about_title', $settings['home_about_title'] ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Tiêu đề vì sao chọn</label>
                <input type="text" name="home_why_title" class="form-control" value="{{ old('home_why_title', $settings['home_why_title'] ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Nội dung giới thiệu</label>
                <textarea name="home_about_content" rows="4" class="form-control">{{ old('home_about_content', $settings['home_about_content'] ?? '') }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nội dung vì sao chọn</label>
                <textarea name="home_why_content" rows="4" class="form-control">{{ old('home_why_content', $settings['home_why_content'] ?? '') }}</textarea>
            </div>
            <div class="col-md-8">
                <label class="form-label">Giới thiệu footer</label>
                <textarea name="footer_about" rows="3" class="form-control">{{ old('footer_about', $settings['footer_about'] ?? '') }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Copyright</label>
                <input type="text" name="footer_copyright" class="form-control" value="{{ old('footer_copyright', $settings['footer_copyright'] ?? '') }}">
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-dark" type="submit">Lưu cài đặt</button>
        </div>
    </form>
</div>
@endsection
