<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập quản trị — Shop3DPrinting</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #0f172a, #134e4a 60%, #0f766e);
        }
        .login-card {
            width: min(420px, 92vw);
            border: 0;
            border-radius: 1.25rem;
            box-shadow: 0 25px 60px rgba(0,0,0,.25);
        }
        .login-logo {
            height: 112px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
            margin-bottom: .85rem;
        }
    </style>
</head>
<body>
<div class="card login-card p-4 p-md-5">
    <div class="text-center mb-4">
        <img src="{{ asset('images/logo/Shop3DPrinting.png') }}" alt="Shop3DPrinting" class="login-logo">
        <h1 class="h4 fw-bold mb-1">Shop3DPrinting</h1>
        <p class="text-secondary mb-0">Tận tâm - từ tấm lòng</p>
        <p class="text-secondary small mb-0 mt-1">Đăng nhập trang quản trị</p>
    </div>

    <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email', 'admin@3dshop.local') }}" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label">Mật khẩu</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
        </div>
        <button class="btn btn-dark w-100" type="submit">Đăng nhập</button>
    </form>
    <div class="text-center mt-3">
        <a href="{{ route('shop.home') }}" class="small text-decoration-none">← Về cửa hàng</a>
    </div>
</div>
@include('partials.toastr')
</body>
</html>
