# Hệ thống tự động nội dung Facebook + TikTok

Bộ tích hợp này dùng catalog sản phẩm của Laravel làm nguồn dữ kiện thật, n8n làm bộ điều phối, Gemini tạo nội dung/ảnh/video, Meta Graph API đăng ảnh và TikTok Content Posting API đăng video.

## Thành phần đã thêm

- API Laravel có khóa riêng tại `/api/v1/automation/social-jobs`.
- Bảng `social_content_jobs` lưu trạng thái, nội dung, media, post ID và lỗi.
- Bốn workflow importable trong `docs/n8n/workflows`.
- Stack n8n + PostgreSQL trong `docker-compose.n8n.yml`.
- TikTok mặc định đăng `SELF_ONLY` và gửi `is_aigc: true`.

## 1. Chuẩn bị Laravel

Tạo khóa ngẫu nhiên tối thiểu 32 ký tự và thêm vào `.env`:

```dotenv
N8N_API_KEY=your-long-random-secret
```

Sau đó chạy:

```bash
php artisan config:clear
php artisan migrate
```

Ảnh đưa vào Google Drive phải có tên `SKU__mo-ta.jpg`, ví dụ `RONG-0001__mau-do.jpg`. Phần trước `__` phải trùng SKU trong trang quản trị.

## 2. Chạy n8n

Sao chép `.env.n8n.example` thành `docs/n8n/.env` (tên `.env` đã được Git bỏ qua), thay toàn bộ giá trị `replace-with-...`, rồi chạy:

```bash
docker compose --env-file docs/n8n/.env -f docker-compose.n8n.yml up -d
```

Mở `http://localhost:5678`, tạo owner account và import lần lượt bốn file JSON.

## 3. Credentials cần tạo trong n8n

| Tên credential | Loại | Header |
|---|---|---|
| `Shop Automation API` | Header Auth | `X-N8N-Key: <N8N_API_KEY>` |
| `Gemini API Key` | Header Auth | `x-goog-api-key: <GEMINI_API_KEY>` |
| `Meta Page Access Token` | Header Auth | `Authorization: Bearer <PAGE_ACCESS_TOKEN>` |
| `TikTok Access Token` | Header Auth | `Authorization: Bearer <USER_ACCESS_TOKEN>` |
| `Approval Webhook Auth` | Header Auth | `X-Approval-Key: <ANOTHER_RANDOM_SECRET>` |
| `Google Drive account` | Google Drive OAuth2 | Chọn tài khoản chứa các thư mục media |

Không đưa token thật vào workflow JSON hoặc Git.

Docker Compose đang ghim n8n `2.32.7` để tránh một lần pull image tự động làm thay đổi hành vi workflow. Hãy thử trên staging trước khi nâng phiên bản.

## 4. Thư mục Google Drive

Tạo ba thư mục:

- `PRODUCT-INBOX`: ảnh gốc kích hoạt workflow.
- `GENERATED-IMAGES`: ảnh Facebook do Gemini tạo.
- `GENERATED-VIDEOS`: video TikTok đã tải từ Gemini.

Trong workflow 01 và 02, thay các giá trị `REPLACE_..._FOLDER_ID` bằng ID thư mục tương ứng. Workflow 03 dùng file ID đã lưu trong content job.

## 5. Cấu hình workflow

Tìm chuỗi `REPLACE_` trong từng workflow và thay:

- Domain HTTPS thực tế của website, không dùng `localhost` nếu n8n chạy trong container.
- Google Drive folder ID.
- Facebook Page ID.
- Phiên bản Meta Graph API đang dùng, ví dụ giá trị được Meta Dashboard hiển thị cho app của bạn.
- Credential được n8n yêu cầu chọn lại sau khi import.

Các workflow được import ở trạng thái **Inactive**. Chạy manual với một ảnh thử trước khi Activate.

## 6. Duyệt nội dung

Khi workflow 02 hoàn thành, job có trạng thái `waiting_approval`. Có thể duyệt bằng webhook của workflow 03:

```bash
curl -X POST "https://YOUR_N8N_DOMAIN/webhook/3dshop-social-approval" \
  -H "Content-Type: application/json" \
  -H "X-Approval-Key: YOUR_APPROVAL_SECRET" \
  -d '{"job_key":"UUID_FROM_JOB","decision":"approved","note":"OK"}'
```

Để từ chối, dùng `decision: rejected`. Webhook đã yêu cầu Header Auth; production vẫn nên đặt n8n sau HTTPS reverse proxy.

Nếu muốn đăng hoàn toàn tự động, mở workflow 03 và enable cả bốn node có tiền tố `AUTO -`, sau đó chạy thử manual trước khi Activate workflow. Khi đó job `waiting_approval` sẽ tự chuyển sang approved và được đăng; chỉ nên bật sau khi prompt và catalog đã ổn định.

## 7. TikTok production

Workflow đang dùng một chunk và phù hợp video ngắn dưới 64 MB. Nếu tăng thời lượng hoặc độ phân giải, cần chia chunk theo quy tắc TikTok.

Trước khi đổi `privacy_level` sang `PUBLIC_TO_EVERYONE`:

1. TikTok app phải được cấp `video.publish`.
2. App/client phải hoàn tất audit; client chưa audit chỉ đăng private.
3. Tài khoản phải trả về `PUBLIC_TO_EVERYONE` trong `creator_info`.
4. Tiếp tục giữ `is_aigc: true` cho video tạo bởi AI.

## 8. Trình tự bật production

1. Workflow 01: thử một ảnh, kiểm tra content JSON và ảnh/video prompt.
2. Workflow 02: xác nhận video được tải lên Drive và job chuyển `waiting_approval`.
3. Workflow 03: duyệt một job; Facebook đăng Page và TikTok đăng private.
4. Workflow 04: xác nhận job chuyển `published` khi TikTok hoàn tất.
5. Khi tất cả ổn định mới Activate schedule và Google Drive Trigger.

## API automation

```text
GET    /api/v1/automation/social-jobs
POST   /api/v1/automation/social-jobs/intake
GET    /api/v1/automation/social-jobs/{job_key}
PATCH  /api/v1/automation/social-jobs/{job_key}
POST   /api/v1/automation/social-jobs/{job_key}/approval
```

Mọi endpoint đều yêu cầu `Authorization: Bearer ...` hoặc `X-N8N-Key` khớp `N8N_API_KEY`.
