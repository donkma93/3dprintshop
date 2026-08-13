# REST API v1 — Cửa hàng in 3D

Base URL: `{APP_URL}/api/v1`

Mọi response dùng envelope:

```json
{
  "success": true,
  "message": "OK",
  "data": { },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 0 },
  "links": { "first": "...", "last": "...", "prev": null, "next": null }
}
```

Lỗi:

```json
{
  "success": false,
  "message": "Mô tả lỗi",
  "errors": { }
}
```

Upload file (ảnh): gửi `multipart/form-data`. Cập nhật JSON thuần: `Content-Type: application/json` hoặc `application/x-www-form-urlencoded`.

Boolean form: gửi `1`/`0` hoặc `true`/`false`.

---

## 1. Public (không cần token)

| Method | Path | Mô tả |
|--------|------|--------|
| GET | `/home` | Trang chủ: banner, danh mục, SP nổi bật, tin, settings |
| GET | `/settings` | Thông tin liên hệ / branding công khai |
| GET | `/categories` | Danh mục active |
| GET | `/categories/{slug}` | Chi tiết danh mục |
| GET | `/products` | DS sản phẩm (`q`, `category` slug, `category_id`, `per_page`) |
| GET | `/products/{slug}` | Chi tiết + related |
| GET | `/posts` | Tin đã publish |
| GET | `/posts/{slug}` | Chi tiết tin + related |
| GET | `/pages` | Trang menu |
| GET | `/pages/{slug}` | Chi tiết trang |
| POST | `/orders` | Đặt hàng / để lại SĐT để shop gọi lại |

### Đặt hàng (public)

`POST /orders` (throttle 12/phút)

```json
{
  "customer_name": "Nguyễn A",
  "customer_phone": "0901...",
  "customer_email": "optional",
  "customer_address": "optional",
  "product_id": 1,
  "quantity": 1,
  "note": "Ghi chú",
  "source": "api"
}
```

Sản phẩm public có thêm: `sale_price`, `is_on_sale`, `final_price`, `discount_percent`, `sale_badge`, `promo_label`, `sale_starts_at`, `sale_ends_at`. Không còn `print_time`.

Home public có `sale_products` (đang giảm giá).

### Guest chat

| Method | Path | Body / Query |
|--------|------|----------------|
| GET | `/chat?token=&after_id=` | Poll tin nhắn |
| POST | `/chat/start` | `guest_name`, `guest_phone` hoặc `guest_email`, `message?` → trả `token` |
| POST | `/chat/send` | `token`, `message` |
| POST | `/chat/typing` | `token`, `typing` (bool) |

Lưu `token` hội thoại trên client (app khách) để tiếp tục chat.

---

## 2. Admin auth (Sanctum Bearer)

### Đăng nhập

`POST /admin/login`

```json
{
  "email": "admin@3dshop.local",
  "password": "...",
  "device_name": "android-pixel-7"
}
```

Response `data`:

```json
{
  "token": "1|xxxx",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "...",
    "email": "...",
    "is_admin": true,
    "is_active": true,
    "role": "super_admin",
    "role_label": "Quản trị viên",
    "permissions": ["dashboard.view", "..."],
    "can_view_revenue": true
  }
}
```

Header các request admin:

```
Authorization: Bearer {token}
Accept: application/json
```

| Method | Path | Mô tả |
|--------|------|--------|
| GET | `/admin/me` | User hiện tại (+ role, permissions) |
| POST | `/admin/logout` | Xóa token hiện tại |
| POST | `/admin/logout-all` | Xóa mọi token |

### Phân quyền (RBAC)

Vai trò: `super_admin` (toàn quyền + doanh thu), `manager`, `staff`, `content`.

- API trả **403** nếu role không có permission tương ứng (map theo path `/admin/products` → `products.manage`, …).
- Trường tài chính (`cost_price`, `unit_price`, `stock_value`, `purchase_price`, `total_price`, stats doanh số) **chỉ có khi** `can_view_revenue = true` (super_admin).
- Dashboard: `data.can_view_revenue`, `data.charts`, `data.stats` (stats tài chính ẩn nếu không đủ quyền).

| Method | Path | Quyền | Mô tả |
|--------|------|--------|--------|
| GET | `/admin/roles` | `users.manage` | Danh sách vai trò + permissions |
| * | `/admin/users` | `users.manage` | CRUD tài khoản admin |

Body tạo/sửa user: `name`, `email`, `role`, `password`, `password_confirmation`, `is_active`.

---

## 3. Admin — Dashboard & CRUD

| Method | Path | Ghi chú |
|--------|------|---------|
| GET | `/admin/dashboard` | stats + charts; revenue chỉ super_admin |
| * | `/admin/categories` | apiResource · `categories.manage` |
| * | `/admin/products` | `q`, `category_id`, `is_active` · ẩn `cost_price` nếu không revenue |
| * | `/admin/materials` | `q`, `low_stock=1` · ẩn giá nếu không revenue |
| * | `/admin/material-inputs` | cập nhật tồn kho · ẩn đơn giá/thành tiền nếu không revenue |
| * | `/admin/equipment` | ẩn `purchase_price` nếu không revenue |
| * | `/admin/banners` | |
| * | `/admin/posts` | |
| * | `/admin/pages` | |
| * | `/admin/users` | chỉ super_admin |
| GET/PUT/DELETE | `/admin/orders` | `orders.manage` — yêu cầu đặt hàng / liên hệ lại |

`apiResource` = `GET /`, `POST /`, `GET /{id}`, `PUT|PATCH /{id}`, `DELETE /{id}`.

`DELETE` = soft delete (thùng rác 30 ngày).

### Settings

| Method | Path |
|--------|------|
| GET | `/admin/settings` |
| PUT / POST | `/admin/settings` (POST khi upload logo/favicon/og) |

### Trash

| Method | Path |
|--------|------|
| GET | `/admin/trash?type=` |
| POST | `/admin/trash/{type}/{id}/restore` |
| DELETE | `/admin/trash/{type}/{id}` |
| DELETE | `/admin/trash?type=` | empty |

`type`: `categories`, `products`, `materials`, `material_inputs`, `equipment`, `banners`, `posts`, `pages`.

---

## 4. Admin — Chat

| Method | Path | Mô tả |
|--------|------|--------|
| GET | `/admin/chat?status=open\|closed\|all` | Danh sách hội thoại |
| GET | `/admin/chat/{id}` | Chi tiết + đánh dấu đã đọc |
| POST | `/admin/chat/{id}/reply` | `{ "message": "..." }` |
| POST | `/admin/chat/{id}/typing` | `{ "typing": true }` |
| GET | `/admin/chat/{id}/poll?after_id=` | Tin mới + typing |
| POST | `/admin/chat/{id}/close` | Đóng |
| POST | `/admin/chat/{id}/reopen` | Mở lại |
| GET | `/admin/chat/notifications?after_id=&with_list=1` | Poll thông báo toàn cục |
| POST | `/admin/chat/notifications/read` | `{ "all": true }` hoặc `conversation_id` / `message_id` |

App admin: poll `notifications` mỗi vài giây; khi mở hội thoại dùng `poll` với `after_id`.

---

## 5. Admin — Bán hàng QR (khách + gửi hàng)

Quyền: `sales.sell` (báo cáo `sales/report` cần `revenue.view`).

| Method | Path | Mô tả |
|--------|------|--------|
| GET | `/admin/sales/lookup?code=` | Tìm SP theo QR payload / token / SKU |
| POST | `/admin/sales/sell` | Ghi nhận bán: giảm tồn + doanh thu + KH / giao hàng |
| GET | `/admin/sales/history` | Lịch sử (`q`, `from`, `to`, `shipping_only`, `per_page`) |
| GET | `/admin/sales/{id}` | Chi tiết 1 giao dịch |
| GET | `/admin/sales/{id}/print` | Dữ liệu phiếu gửi hàng (người gửi/nhận/hàng/COD) |
| GET | `/admin/sales/report` | Báo cáo lãi lỗ (super_admin) |

### POST `/admin/sales/sell`

```json
{
  "product_id": 1,
  "quantity": 1,
  "unit_price": 150000,
  "note": "Ghi chú nội bộ",
  "scan_payload": "QLBH|v1|...",
  "customer_name": "Nguyễn A",
  "customer_phone": "0901...",
  "customer_email": "optional",
  "customer_address": "Số 12, ngõ 5, Nguyễn Trãi",
  "customer_ward": "Phường Thanh Xuân Trung",
  "customer_district": "Quận Thanh Xuân",
  "customer_province": "Hà Nội",
  "customer_postal_code": "100000",
  "customer_source": "web_chat",
  "needs_shipping": true,
  "receiver_name": "optional (mặc định = KH)",
  "receiver_phone": "optional",
  "receiver_address": "optional",
  "receiver_ward": "optional",
  "receiver_district": "optional",
  "receiver_province": "optional",
  "receiver_postal_code": "optional",
  "carrier": "ghn",
  "shipping_service": "standard",
  "shipping_note": "Hàng dễ vỡ",
  "payment_method": "cod",
  "cod_amount": 150000,
  "package_weight": 350,
  "package_count": 1,
  "declared_value": 150000,
  "goods_content": "Mô hình in 3D"
}
```

`customer_source`: `walk_in` | `phone` | `web_chat` | `contact` | `order_request` | `other`  
`payment_method`: `cash` | `transfer` | `cod`  
`carrier`: `ghn` | `ghtk` | `viettel_post` | `jt` | `vnpost` | `ninjavan` | `shopee_express` | `grab` | `other`  
`shipping_service`: `standard` | `express` | `economy`  
`needs_shipping=true` bắt buộc tên + SĐT + địa chỉ giao đầy đủ (số nhà/đường + phường/quận/tỉnh, hoặc copy từ KH).  
COD / giá trị khai báo trống → mặc định = tổng đơn.  
`GET /admin/sales/{id}/print` trả địa chỉ tách field + `full_address` để app in form. Web: `/admin/sales/{id}/print`.

---

## 6. Admin — Module chuẩn bị thuế HKD

Module **riêng biệt**, quyền `tax.manage` (mặc định chỉ `super_admin` qua `*`).  
Chỉ phục vụ **chuẩn bị / quản trị nội bộ** (sổ doanh thu, ước GTGT/TNCN, khóa kỳ, xuất CSV). **Không** nộp tờ khai điện tử.

| Method | Path | Mô tả |
|--------|------|--------|
| GET | `/admin/tax/summary?period=` | Tổng hợp kỳ (`2026-Q3`, `2026-08`, `2026`) |
| GET | `/admin/tax/periods` | Options kỳ + danh sách kỳ đã lưu |
| GET | `/admin/tax/profile` | Hồ sơ HKD + options phương pháp/chu kỳ |
| PUT | `/admin/tax/profile` | Cập nhật hồ sơ, % GTGT/TNCN, ngưỡng, hạn |
| GET | `/admin/tax/ledger?period=&q=&source=&per_page=` | Sổ doanh thu |
| POST | `/admin/tax/entries` | Ghi thủ công / điều chỉnh |
| PUT | `/admin/tax/entries/{id}` | Sửa dòng (bán hàng: chỉ HĐ / loại trừ / note) |
| DELETE | `/admin/tax/entries/{id}` | Xóa dòng thủ công (không xóa `product_sale`) |
| POST | `/admin/tax/sync` | Đồng bộ `product_sales` → sổ (`from`, `to` optional) |
| POST | `/admin/tax/period/close` | Khóa sổ kỳ (`period`, `admin_note`) |
| POST | `/admin/tax/period/reopen` | Mở lại kỳ (`period`) |
| POST | `/admin/tax/period/paid` | Ghi đã nộp (`period`, `paid_amount`, `paid_on`, `payment_ref`) |

Web: `/admin/tax`, `/admin/tax/ledger`, `/admin/tax/report`, `/admin/tax/export` (CSV), `/admin/tax/profile`.

---

## 7. Gợi ý client mobile

1. Login → lưu token secure storage.
2. Mọi request admin: `Authorization: Bearer …` + `Accept: application/json`.
3. 401 → login lại; 403 → không phải admin.
4. Upload: `multipart/form-data` với field ảnh giống form web.
5. Chat guest: lưu `token` riêng; chat admin: Sanctum token.
6. Bán hàng: scan QR → sell kèm KH/giao hàng → nếu `print` khác null thì hiển thị/in phiếu gửi.
7. Thuế: `tax/summary` theo kỳ → `tax/sync` → khóa kỳ khi chốt số.

Web Blade vẫn hoạt động độc lập (session). API song song cho app.
