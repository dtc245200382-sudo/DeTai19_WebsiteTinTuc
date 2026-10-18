# Đề tài 19 – Website Tin tức / Cổng thông tin

Triển khai website tin tức WordPress bằng Docker Compose, kết hợp Nginx Reverse Proxy, MySQL, phpMyAdmin, giám sát Prometheus/Grafana, thu thập log Loki/Promtail và các biện pháp hardening cơ bản.

> Dự án thực hành học phần Triển khai và Quản trị Hệ thống Phần mềm. Cấu hình hiện tại ưu tiên môi trường local/lab; chưa phải cấu hình production hoàn chỉnh.

## 1. Kiến trúc hệ thống

```text
Trình duyệt ──HTTP :80──> Nginx ──> WordPress ──> MySQL
                              │
                              └── access/error logs ──> Promtail ──> Loki ──> Grafana

Nginx /stub_status ──> Nginx Exporter ──┐
MySQL ──> MySQL Exporter ────────────────┼──> Prometheus ──> Grafana
Docker ──> cAdvisor ────────────────────┘
```

Docker Compose quản lý 11 dịch vụ, chia thành các mạng `edge`, `data`, `monitoring`. phpMyAdmin nằm trong mạng Docker và chưa có đường truy cập công khai qua Nginx.

## 2. Công nghệ

| Thành phần | Image / phiên bản | Vai trò |
|---|---|---|
| WordPress | `wordpress:php8.3-apache` | Website tin tức |
| MySQL | `mysql:8.4` | Cơ sở dữ liệu |
| phpMyAdmin | `phpmyadmin:5.2-apache` | Quản trị MySQL trong mạng nội bộ |
| Nginx | `nginx:1.27-alpine` | Reverse proxy, security headers, rate limiting |
| Prometheus | `prom/prometheus:v3.5.0` | Thu thập metrics |
| Grafana | `grafana/grafana:12.1.1` | Dashboard metrics và tra cứu log |
| cAdvisor | `ghcr.io/google/cadvisor:v0.53.0` | Metrics môi trường container |
| Nginx Exporter | `nginx/nginx-prometheus-exporter:1.4.2` | Metrics Nginx |
| MySQL Exporter | `prom/mysqld-exporter:v0.17.2` | Metrics MySQL |
| Loki | `grafana/loki:3.5.0` | Lưu trữ và truy vấn log |
| Promtail | `grafana/promtail:3.5.0` | Thu thập log Nginx và chuyển đến Loki |

> Trên Docker Desktop/Windows, cAdvisor có thể chỉ hiển thị metrics ở mức môi trường Docker thay vì từng container theo tên.

## 3. Cấu trúc thư mục

```text
DeTai19_WebsiteTinTuc/
├── .env.example
├── .gitignore
├── docker-compose.yml
├── README.md
├── nginx/
│   └── nginx.conf
├── prometheus/
│   └── prometheus.yml
├── grafana/
│   └── detai19-dashboard.json
├── loki/
│   └── loki-config.yml
├── promtail/
│   └── promtail-config.yml
└── mysql-exporter/
    └── .my.cnf             # Tạo cục bộ, không commit
```

## 4. Yêu cầu trước khi chạy

- Docker Desktop với Docker Compose V2.
- Git và trình duyệt web.
- Cổng `80`, `3000`, `9090` trên máy không bị ứng dụng khác chiếm dụng.
- PowerShell nếu chạy trên Windows.

## 5. Cài đặt và khởi động

### Bước 1: Clone repository

```powershell
git clone https://github.com/dtc245200382-sudo/DeTai19_WebsiteTinTuc.git
cd DeTai19_WebsiteTinTuc
```

### Bước 2: Tạo biến môi trường

```powershell
Copy-Item .env.example .env
```

Mở `.env`, thay mật khẩu mẫu bằng mật khẩu mạnh. File `docker-compose.yml` hiện tại cũng yêu cầu hai biến Grafana; thêm vào `.env`:

```dotenv
GRAFANA_ADMIN_USER=admin
GRAFANA_ADMIN_PASSWORD=THAY_BANG_MAT_KHAU_MANH
```

Giữ các biến `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` trong `.env.example` và thay giá trị mật khẩu mẫu. Không commit `.env`.

### Bước 3: Cấu hình MySQL Exporter

Compose yêu cầu file cục bộ `mysql-exporter/.my.cnf` (không có trong Git). Tạo thư mục và file này, dùng thông tin tài khoản MySQL có quyền đọc metrics phù hợp:

```ini
[client]
user=TEN_TAI_KHOAN_EXPORTER
password=MAT_KHAU_EXPORTER
host=mysql
port=3306
```

Tài khoản exporter cần được tạo trong MySQL trước khi khởi động exporter. Nên dùng tài khoản chuyên dụng với quyền tối thiểu, không dùng tài khoản root. Không commit `.my.cnf` hoặc đưa mật khẩu thật vào tài liệu.

### Bước 4: Kiểm tra và khởi động

```powershell
docker compose config --quiet
docker compose up -d
docker compose ps
```

Trên môi trường mới, đợi MySQL healthy và hoàn tất cài đặt WordPress trong trình duyệt.

## 6. Địa chỉ truy cập

| Dịch vụ | Địa chỉ |
|---|---|
| Website | http://localhost |
| WordPress Admin | http://localhost/wp-admin |
| Grafana | http://localhost:3000 |
| Prometheus | http://localhost:9090 |
| Prometheus Targets | http://localhost:9090/targets |

Grafana và Prometheus chỉ được bind tới `127.0.0.1` trên máy chủ. MySQL, phpMyAdmin và Loki không được publish trực tiếp ra host trong cấu hình Compose hiện tại. Đường dẫn phpMyAdmin qua reverse proxy chưa được cấu hình.

## 7. Monitoring với Prometheus và Grafana

Prometheus thu thập bốn nhóm target: `prometheus`, `cadvisor`, `nginx`, `mysql`.

1. Mở http://localhost:9090/targets và kiểm tra các target `UP`.
2. Mở Grafana tại http://localhost:3000 và đăng nhập bằng thông tin trong `.env`.
3. Cấu hình Prometheus datasource URL: `http://prometheus:9090`.
4. Import dashboard từ `grafana/detai19-dashboard.json` và chọn datasource Prometheus.

Một số PromQL dùng để xác minh:

```promql
up
nginx_up
mysql_up
```

`nginx_up = 1` thể hiện Nginx Exporter đọc được trạng thái Nginx. `mysql_up = 1` thể hiện MySQL Exporter kết nối được MySQL.

## 8. Centralized Logging với Loki và Promtail

Nginx ghi access/error log vào volume `nginx_logs`. Promtail đọc volume này ở chế độ read-only, gắn nhãn `job="nginx"`, `service="nginx"` rồi gửi log tới Loki.

Trong Grafana:

1. Thêm Loki datasource URL: `http://loki:3100`.
2. Vào **Explore**, chọn datasource Loki.
3. Thực hiện các truy vấn LogQL:

**Xem toàn bộ log Nginx**

```logql
{job="nginx"}
```

**Lọc các yêu cầu HTTP 404**

```logql
{job="nginx"} |= " 404 "
```

**Đếm số log theo phút**

```logql
sum(count_over_time({job="nginx"}[1m]))
```

Tạo một URL không tồn tại trên website để sinh log 404, ví dụ `http://localhost/duong-dan-khong-ton-tai-12345`.

## 9. Hardening – bảo mật cơ bản

Cấu hình tại `nginx/nginx.conf`:

| Biện pháp | Cấu hình / mục đích |
|---|---|
| Security headers | `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin` |
| Ẩn phiên bản Nginx | `server_tokens off` |
| Chặn file ẩn | Từ chối truy cập các đường dẫn dạng `/.env`, ngoại trừ `.well-known` |
| Rate limiting | `10r/s` trên mỗi IP, `burst=20 nodelay`, trả HTTP `429` khi vượt giới hạn |
| Giảm bề mặt truy cập | Grafana/Prometheus chỉ bind localhost; MySQL không publish port ra host |
| Tách mạng và bảo vệ bí mật | Các mạng Docker riêng; `.env` và `.my.cnf` không commit |

Kiểm thử đã thực hiện trong môi trường thực hành:

```powershell
docker compose exec nginx nginx -t
docker compose exec nginx nginx -s reload
curl.exe -I http://localhost
curl.exe -s -o NUL -w "%{http_code}`n" http://localhost/.env
```

Kết quả ghi nhận: website HTTP `200`; đủ ba security headers; `/.env` trả `403`. Bài kiểm tra 60 request đồng thời ghi nhận **22 phản hồi HTTP 200 và 38 phản hồi HTTP 429**, chứng minh rate limiting hoạt động tại thời điểm kiểm thử.

**Giới hạn bảo mật:** Môi trường local hiện dùng HTTP, chưa cấu hình TLS/HTTPS; phản hồi PHP vẫn có thể tiết lộ `X-Powered-By`; chưa nên coi đây là hệ thống production hardened.

## 10. Kiểm tra và xử lý sự cố

```powershell
# Kiểm tra cấu hình Compose
docker compose config --quiet

# Xem trạng thái container
docker compose ps

# Kiểm tra cấu hình Nginx
docker compose exec nginx nginx -t

# Xem log gần nhất
docker compose logs --tail 50 nginx
docker compose logs --tail 50 promtail
docker compose logs --tail 50 loki
docker compose logs --tail 50 nginx-exporter
```

Nếu Nginx Exporter không có `wget`, đó chỉ là do image tối giản. Kiểm tra trực tiếp tại Prometheus Targets hoặc truy vấn `nginx_up` thay vì cài công cụ vào container.

## 11. Bảo toàn dữ liệu

Các volume chính: `mysql_data`, `wordpress_data`, `prometheus_data`, `grafana_data`, `nginx_logs`, `loki_data`, `promtail_positions`.

Để dừng và chạy lại mà vẫn giữ volume:

```powershell
docker compose down
docker compose up -d
```

**Không dùng `docker compose down -v` hoặc `docker volume prune`** nếu muốn giữ bài viết WordPress, cơ sở dữ liệu, dashboard và log. Với dữ liệu quan trọng, cần có bản sao lưu riêng trước khi thay đổi cấu hình hoặc nâng cấp image.

## 12. Quy trình Git

Dự án triển khai qua các nhánh tính năng và Pull Request:

- Giai đoạn 1: WordPress, MySQL, phpMyAdmin, Nginx Reverse Proxy.
- Giai đoạn 2: Prometheus, Grafana và exporters – đã merge qua PR #1.
- Giai đoạn 3: Loki, Promtail và LogQL – đã merge qua PR #2.
- Giai đoạn 4: Nginx hardening – commit `212965b` trên nhánh `feature/detai19-hardening` (trạng thái merge cần kiểm tra lại trên GitHub).

Mỗi thay đổi mới nên được thực hiện trên nhánh `feature/*`, sau đó tạo Pull Request để kiểm tra trước khi merge vào `main`.

## 13. Phạm vi và hướng phát triển

Dự án đáp ứng mục tiêu thực hành triển khai ứng dụng web, giám sát metrics, thu thập log tập trung và áp dụng hardening cơ bản. Những việc có thể bổ sung khi triển khai thực tế: HTTPS/TLS, giảm thông tin phiên bản PHP trong response, backup/restore định kỳ, phân quyền chặt hơn cho các dịch vụ, chuyển sang Grafana Alloy thay Promtail theo lộ trình vòng đời sản phẩm và cấu hình lưu trữ log phù hợp.

---

**Repository:** https://github.com/dtc245200382-sudo/DeTai19_WebsiteTinTuc
