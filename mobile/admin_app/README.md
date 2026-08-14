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

## Module đã có

| Màn | API |
|-----|-----|
| Login + BASE URL | `POST /admin/login` |
| Tổng quan | `GET /admin/dashboard` |
| Bán QR (scan/lookup/sell) | `/admin/sales/*` |
| Lịch sử + phiếu gửi (share/copy) | history, print |
| Báo cáo lãi lỗ | `/admin/sales/report` |
| Chat (poll) | `/admin/chat/*` |
| Đơn hàng | `/admin/orders` |
| Sản phẩm (list) | `/admin/products` |
| Thuế HKD summary + sync | `/admin/tax/*` |

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
