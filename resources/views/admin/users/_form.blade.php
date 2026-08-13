@php($item = $user ?? null)
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label">Họ tên <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $item->name ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email đăng nhập <span class="text-danger">*</span></label>
        <input type="email" name="email" value="{{ old('email', $item->email ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Vai trò <span class="text-danger">*</span></label>
        <select name="role" class="form-select" required @disabled(isset($item) && $item->id === auth()->id())>
            @foreach($roles as $key => $label)
                <option value="{{ $key }}" @selected(old('role', $item->role ?? 'staff') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @if(isset($item) && $item->id === auth()->id())
            <input type="hidden" name="role" value="{{ $item->role }}">
            <div class="form-text">Không thể tự đổi vai trò của chính mình.</div>
        @else
            <div class="form-text">
                Chỉ <strong>Quản trị viên</strong> được xem doanh thu/doanh số. Vai trò khác bị ẩn số liệu tài chính.
            </div>
        @endif
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                   @checked(old('is_active', $item->is_active ?? true))
                   @disabled(isset($item) && $item->id === auth()->id())>
            <label class="form-check-label" for="is_active">Tài khoản đang hoạt động</label>
        </div>
        @if(isset($item) && $item->id === auth()->id())
            <input type="hidden" name="is_active" value="1">
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label">Mật khẩu {{ $item ? '(để trống nếu không đổi)' : '' }} @if(!$item)<span class="text-danger">*</span>@endif</label>
        <input type="password" name="password" class="form-control" autocomplete="new-password" @if(!$item) required @endif>
    </div>
    <div class="col-md-6">
        <label class="form-label">Xác nhận mật khẩu</label>
        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" @if(!$item) required @endif>
    </div>
</div>
