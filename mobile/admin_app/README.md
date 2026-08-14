# 3D Print Shop — Admin App (Flutter)

Ứng dụng quản trị **Android + iOS** gọi REST API backend Laravel.

## Điểm chính

- **Không hard-code server:** màn đăng nhập yêu cầu **URL API** + email + mật khẩu.
- URL được chuẩn hóa thành `{host}/api/v1` rồi dùng làm **BASE** cho mọi request.
- Token Sanctum lưu **secure storage**; BASE URL lưu preferences.
- Menu theo **RBAC** (`permissions` từ API).

## Yêu cầu

- Flutter **3.22+** / Dart 3.3+
- Android Studio / Xcode (iOS)
- Backend: `php artisan serve` hoặc domain có HTTPS

Cài Flutter: https://docs.flutter.dev/get-started/install

## Chạy

```bash
cd mobile/admin_app
flutter pub get
flutter run
```

### URL API khi test local

| Môi trường | URL nhập trên app |
|------------|-------------------|
| Emulator Android → máy host | `http://10.0.2.2:8000` |
| iOS simulator | `http://127.0.0.1:8000` |
| Máy thật cùng Wi‑Fi | `http://192.168.x.x:8000` |
| Production | `https://your-domain.com` |

App tự thành `…/api/v1`.

Tài khoản seed mặc định backend:

- `admin@3dshop.local` / `admin@123`

## Module (khớp menu admin web)

| Nhóm | Màn | API |
|------|-----|-----|
| Auth | Login + BASE URL | `POST /admin/login` |
| | Tổng quan | `GET /admin/dashboard` |
| Kho | Sản phẩm CRUD + QR | `/admin/products`, `/qr` |
| | Danh mục CRUD | `/admin/categories` |
| | Nguyên liệu CRUD | `/admin/materials` |
| | Nhập nguyên liệu | `/admin/material-inputs` |
| | Thiết bị CRUD | `/admin/equipment` |
| CMS | Banner / Bài viết / Trang | `/admin/banners`, `posts`, `pages` |
| Bán hàng | Scan QR, lịch sử, phiếu gửi, P&L | `/admin/sales/*` |
| Chat / Đơn | Chat poll, đơn liên hệ | `/admin/chat/*`, `/orders` |
| Thuế HKD | Overview, ledger, kỳ, hồ sơ | `/admin/tax/*` |
| Hệ thống | Users, Settings & SEO, Thùng rác | `/admin/users`, `settings`, `trash` |

### Đa ngôn ngữ (i18n)

- **Tiếng Việt** (mặc định) và **English**
- Đổi ngôn ngữ: màn **Menu / Thêm** hoặc icon 🌐 trên Login
- Lưu locale trên máy (`shared_preferences`)

Thiết kế đầy đủ: [docs/mobile/THIET-KE-APP-ADMIN.md](../../docs/mobile/THIET-KE-APP-ADMIN.md)

## Android cleartext (HTTP LAN)

File `android/app/src/main/AndroidManifest.xml` (sau `flutter create`) cần:

```xml
<application android:usesCleartextTraffic="true" ...>
```

và quyền:

```xml
<uses-permission android:name="android.permission.INTERNET"/>
<uses-permission android:name="android.permission.CAMERA"/>
```

Chạy `flutter create .` trong thư mục này nếu thiếu folder `android/` / `ios/`.

## Cấu trúc

```
lib/
  core/          # ApiClient, session, theme, permissions
  features/      # auth, dashboard, sales, chat, orders, products, tax, more
  router/        # go_router + shell
  main.dart
```

## Build release

```bash
flutter build apk --release
flutter build ios --release
flutter build appbundle
```

## License

Cùng repo 3D Print Shop.
