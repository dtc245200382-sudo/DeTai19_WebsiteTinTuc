# Đề tài 19 – Website Tin tức / Cổng thông tin

## 1. Giới thiệu

Dự án triển khai website tin tức bằng WordPress, MySQL, phpMyAdmin và Nginx Reverse Proxy thông qua Docker Compose.

Đây là bài thực hành thuộc học phần Triển khai và Quản trị Hệ thống Phần mềm.

## 2. Công nghệ sử dụng

- WordPress (PHP 8.3 / Apache)
- MySQL 8.4
- phpMyAdmin 5.2
- Nginx 1.27
- Docker Compose

## 3. Hướng dẫn triển khai

**Bước 1:** Clone repository.

**Bước 2:** Tạo file `.env` từ `.env.example` và thay mật khẩu mẫu bằng mật khẩu mạnh.

**Bước 3:** Khởi động:

```bash
docker compose config --quiet
docker compose up -d
```

**Bước 4:** Kiểm tra:

```bash
docker compose ps
```

## 4. Địa chỉ truy cập

| Thành phần | Địa chỉ |
|---|---|
| Website | http://localhost |
| WordPress Admin | http://localhost/wp-admin |
| phpMyAdmin | Cần cấu hình đường truy cập quản trị |

## 5. Kiểm tra Nginx

```bash
docker compose exec nginx nginx -t
curl -I http://localhost/
```

Nginx được cấu hình ba security headers:

- X-Frame-Options
- X-Content-Type-Options
- Referrer-Policy

## 6. Bảo vệ dữ liệu

MySQL và WordPress sử dụng Docker volumes để duy trì dữ liệu. Không sử dụng `docker compose down -v` khi muốn giữ dữ liệu.

Không đưa file `.env` hoặc mật khẩu thật lên GitHub.

## 7. Các giai đoạn

- Commit 1: WordPress + MySQL + phpMyAdmin + Nginx
- Commit 2: Prometheus + Grafana
- Commit 3: Loki + Promtail + LogQL
