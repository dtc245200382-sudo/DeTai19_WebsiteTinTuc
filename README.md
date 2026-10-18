# Đề tài 19 – Website Tin tức / Cổng thông tin

Triển khai **WordPress + MySQL + Nginx Reverse Proxy** bằng Docker Compose, tích hợp **Prometheus/Grafana** để giám sát, **Loki/Promtail** để thu thập log và các biện pháp **hardening** cơ bản.

> **Phạm vi:** bài thực hành học phần Triển khai và Quản trị Hệ thống Phần mềm, chạy trên máy cá nhân (ưu tiên Windows + Docker Desktop). Đây **không phải** cấu hình production hoàn chỉnh. Các bước dưới đây mô tả cấu hình đang có trong repository và những thao tác cần thực hiện thủ công khi dựng môi trường mới.

## 1. Kiến trúc

```text
Trình duyệt -- HTTP :80 --> Nginx --> WordPress --> MySQL
                              |
                              +--> access/error log --> Promtail --> Loki --> Grafana Explore

Nginx /stub_status --> Nginx Exporter --+
MySQL --------------> MySQL Exporter --+--> Prometheus --> Grafana Dashboard
Docker --------------> cAdvisor -------+
```

Docker Compose quản lý **11 dịch vụ** trên ba mạng `edge`, `data`, `monitoring`. WordPress và phpMyAdmin kết nối MySQL qua mạng `data`; Nginx phục vụ website trên cổng 80. phpMyAdmin **chưa có đường truy cập từ host** trong Compose hiện tại.

## 2. Thành phần và phiên bản

| Dịch vụ | Docker image | Vai trò |
|---|---|---|
| WordPress | `wordpress:php8.3-apache` | Website tin tức |
| MySQL | `mysql:8.4` | Lưu trữ dữ liệu |
| phpMyAdmin | `phpmyadmin:5.2-apache` | Quản trị DB nội bộ |
| Nginx | `nginx:1.27-alpine` | Reverse proxy, security headers, rate limiting |
| Prometheus | `prom/prometheus:v3.5.0` | Thu thập metrics |
| Grafana | `grafana/grafana:12.1.1` | Dashboard và Explore |
| cAdvisor | `ghcr.io/google/cadvisor:v0.53.0` | Metrics môi trường container |
| Nginx Exporter | `nginx/nginx-prometheus-exporter:1.4.2` | Metrics Nginx |
| MySQL Exporter | `prom/mysqld-exporter:v0.17.2` | Metrics MySQL |
| Loki | `grafana/loki:3.5.0` | Lưu trữ và truy vấn log |
| Promtail | `grafana/promtail:3.5.0` | Đọc log Nginx, đẩy sang Loki |

> Promtail đã hết vòng đời hỗ trợ; dự án giữ Promtail để đáp ứng yêu cầu bài thực hành. Khi triển khai mới trong thực tế, nên cân nhắc Grafana Alloy.

## 3. Cấu trúc repository

```text
DeTai19_WebsiteTinTuc/
├── .env.example
├── .gitignore
├── docker-compose.yml
├── README.md
├── nginx/nginx.conf
├── prometheus/prometheus.yml
├── grafana/detai19-dashboard.json
├── loki/loki-config.yml
├── promtail/promtail-config.yml
└── mysql-exporter/
    └── .my.cnf               # Tự tạo trên máy, KHÔNG commit
```

Các file `.env` và `mysql-exporter/.my.cnf` chứa thông tin nhạy cảm, phải được tạo cục bộ và không đưa lên Git.

## 4. Yêu cầu môi trường

- Docker Desktop đang chạy **Linux containers**, Docker Compose V2 (`docker compose version`).
- Git, trình duyệt web; PowerShell nếu dùng Windows.
- Các cổng host `80`, `3000`, `9090` chưa bị ứng dụng khác chiếm dụng.
- Đủ tài nguyên cho 11 container; lần đầu cần Internet để tải Docker images.

Kiểm tra nhanh:

```powershell
docker version
docker compose version
```

## 5. Cài đặt lần đầu

### 5.1. Clone và tạo `.env`

```powershell
git clone https://github.com/dtc245200382-sudo/DeTai19_WebsiteTinTuc.git
cd DeTai19_WebsiteTinTuc
Copy-Item .env.example .env
```

Mở `.env`, thay các mật khẩu mẫu bằng giá trị riêng và **thêm hai biến Grafana** (hiện chưa có trong `.env.example` của repository):

```dotenv
MYSQL_DATABASE=news_db
MYSQL_USER=news_user
MYSQL_PASSWORD=THAY_MAT_KHAU_UNG_DUNG
MYSQL_ROOT_PASSWORD=THAY_MAT_KHAU_ROOT
GRAFANA_ADMIN_USER=admin
GRAFANA_ADMIN_PASSWORD=THAY_MAT_KHAU_GRAFANA
```

**Không commit** `.env`. Tránh dùng ký tự gây khó khăn khi nội suy biến trong Docker Compose nếu chưa kiểm tra bằng `docker compose config`.

### 5.2. Khởi động database trước

```powershell
docker compose config --quiet
docker compose up -d mysql
docker compose ps mysql
```

Đợi MySQL chuyển sang trạng thái `healthy` trước khi tạo tài khoản exporter.

### 5.3. Tạo tài khoản chỉ đọc metrics cho MySQL Exporter

Dùng mật khẩu riêng cho exporter. Ví dụ, mở MySQL CLI trong container:

```powershell
docker compose exec mysql mysql -u root -p
```

Nhập mật khẩu `MYSQL_ROOT_PASSWORD` khi được hỏi, sau đó chạy SQL (thay `MAT_KHAU_EXPORTER_RIENG`):

```sql
CREATE USER IF NOT EXISTS 'exporter'@'%' IDENTIFIED BY 'MAT_KHAU_EXPORTER_RIENG';
GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'exporter'@'%';
```

Các quyền này phục vụ mục đích thực hành metrics; khi đưa lên môi trường thực tế, rà soát lại theo nguyên tắc tối thiểu quyền. Nếu user đã tồn tại, `CREATE USER IF NOT EXISTS` không cập nhật mật khẩu cũ; cần đồng bộ mật khẩu bằng `ALTER USER` khi cần.

Thoát MySQL bằng `exit;`. Tạo file **`mysql-exporter/.my.cnf`** với nội dung (không commit):

```ini
[client]
user=exporter
password=MAT_KHAU_EXPORTER_RIENG
host=mysql
port=3306
```

Tạo thư mục nếu chưa có:

```powershell
New-Item -ItemType Directory -Force mysql-exporter | Out-Null
notepad .\mysql-exporter\.my.cnf
```

Xác nhận file được Git bỏ qua:

```powershell
git check-ignore mysql-exporter/.my.cnf
```

### 5.4. Khởi động các dịch vụ

```powershell
docker compose config --quiet
docker compose up -d
docker compose ps
```

Truy cập `http://localhost` để hoàn thành màn hình cài đặt WordPress trên **database mới** (chọn ngôn ngữ, tên website, tài khoản quản trị và mật khẩu). Không đưa mật khẩu quản trị WordPress vào repository.

**Lưu ý khi triển khai lại:** dữ liệu WordPress nằm trong Docker volumes; clone repository không tự có các bài viết, tài khoản và dữ liệu đã tạo trên máy tác giả.

## 6. Địa chỉ và phạm vi truy cập

| Thành phần | URL từ máy host | Ghi chú |
|---|---|---|
| Website | http://localhost | Qua Nginx cổng 80 |
| WordPress Admin | http://localhost/wp-admin | Tài khoản tạo lúc cài WordPress |
| Grafana | http://localhost:3000 | Bind `127.0.0.1` |
| Prometheus | http://localhost:9090 | Bind `127.0.0.1` |
| Prometheus Targets | http://localhost:9090/targets | Xem target UP/DOWN |
| phpMyAdmin | Chưa có URL host | Chỉ ở mạng Docker |
| Loki | Không publish ra host | Grafana truy cập `http://loki:3100` |
| MySQL | Không publish ra host | Container nội bộ |

Cổng Nginx `80:80` hiện được publish trên các giao diện host, không giới hạn `127.0.0.1`. Khi chỉ chạy lab cá nhân, cân nhắc giới hạn thành `127.0.0.1:80:80` nếu không cần truy cập từ máy khác.

## 7. Monitoring: Prometheus + Grafana

Prometheus scrape bốn nhóm: `prometheus`, `cadvisor`, `nginx`, `mysql`.

1. Mở `http://localhost:9090/targets`, kiểm tra các target mong đợi ở trạng thái **UP**.
2. Mở Grafana `http://localhost:3000`, đăng nhập bằng `GRAFANA_ADMIN_USER` / `GRAFANA_ADMIN_PASSWORD`.
3. **Connections → Data sources → Add new data source → Prometheus**; nhập URL `http://prometheus:9090`, **Save & test**.
4. **Dashboards → New → Import**, tải `grafana/detai19-dashboard.json`, chọn datasource Prometheus và lưu.

PromQL để kiểm thử:

```promql
up
nginx_up
mysql_up
rate(mysql_global_status_questions[5m])
mysql_global_status_threads_connected
```

`nginx_up = 1` và `mysql_up = 1` cho biết exporter tương ứng đang lấy metrics thành công. Dashboard đã xuất JSON, nhưng **chưa có Grafana provisioning tự động**; máy mới phải thêm datasource/import dashboard thủ công.

**Giới hạn đã gặp:** trên Docker Desktop/Windows, cAdvisor có thể chỉ báo metrics tổng hợp môi trường Docker, không liệt kê đầy đủ metrics của từng container. Không nên tuyên bố giám sát từng container thành công nếu thực tế chỉ có metrics tổng hợp.

## 8. Centralized Logging: Loki + Promtail

Nginx cấu hình ghi `access.log` và `error.log` vào `/var/log/nginx`; Compose gắn volume `nginx_logs` vào Nginx và mount **read-only** vào Promtail. Promtail đọc `/var/log/nginx/*.log`, gắn nhãn `job="nginx"`, `service="nginx"` và gửi tới `http://loki:3100/loki/api/v1/push`.

### 8.1. Điểm cần kiểm tra khi dựng môi trường mới

Image Nginx chính thức có thể tạo **symlink** `access.log -> /dev/stdout` và `error.log -> /dev/stderr`. Nếu symlink còn tồn tại, Promtail đọc file trong shared volume có thể không hoạt động như mong đợi. **Repository hiện chưa có cơ chế tự động chuyển symlink thành file thực**; kiểm tra sau khi khởi động:

```powershell
docker compose exec nginx ls -l /var/log/nginx/
```

Nếu cả hai đường dẫn là symlink, với môi trường lab này có thể sửa trong volume rồi yêu cầu Nginx mở lại log:

```powershell
docker compose exec nginx sh -c "rm /var/log/nginx/access.log /var/log/nginx/error.log && touch /var/log/nginx/access.log /var/log/nginx/error.log"
docker compose exec nginx nginx -s reopen
```

**Chỉ thực hiện thao tác này khi đã xác nhận chúng là symlink**, không áp dụng tùy tiện cho file log đang có dữ liệu cần giữ. Đây là cách xử lý thủ công cho lab, chưa phải giải pháp tự động/production. Khi xóa và tạo lại volume log, cần kiểm tra lại.

Tạo lưu lượng và xem log:

```powershell
1..5 | ForEach-Object { curl.exe -s -o NUL http://localhost/ }
docker compose exec nginx tail -n 10 /var/log/nginx/access.log
docker compose logs --tail=30 promtail
docker compose logs --tail=30 loki
```

### 8.2. Kết nối Loki với Grafana

Trong Grafana: **Connections → Data sources → Add new data source → Loki**. Nhập URL `http://loki:3100` rồi **Save & test**. Mở **Explore**, chọn datasource Loki, thời gian **Last 15 minutes**.

Ba ví dụ LogQL:

**Xem log Nginx**

```logql
{job="nginx"}
```

**Lọc các request GET**

```logql
{job="nginx"} |= "GET"
```

**Đếm số dòng log trong mỗi phút**

```logql
sum(count_over_time({job="nginx"}[1m]))
```

**Lọc dòng access log có mã 404**

```logql
{job="nginx"} |= " 404 "
```

Để tạo log 404 thử nghiệm:

```powershell
curl.exe -s -o NUL -w "%{http_code}`n" http://localhost/duong-dan-khong-ton-tai-12345
```

Nếu chưa thấy log, kiểm tra **time range**, file log thật hay symlink, log Promtail và trạng thái Loki. Chỉ coi logging hoàn tất sau khi **truy vấn Explore trả về log**.

## 9. Hardening và kiểm thử bảo mật

Cấu hình bảo mật Nginx hiện có trong `nginx/nginx.conf`:

| Biện pháp | Cấu hình |
|---|---|
| Security headers | `X-Content-Type-Options: nosniff`; `X-Frame-Options: SAMEORIGIN`; `Referrer-Policy: strict-origin-when-cross-origin` |
| Ẩn phiên bản Nginx | `server_tokens off` |
| Chặn file/thư mục ẩn | `location` chặn đường dẫn như `/.env`, ngoại lệ `.well-known` |
| Rate limiting | `10r/s` mỗi IP, `burst=20 nodelay`, trả HTTP `429` khi vượt ngưỡng |
| Giảm bề mặt truy cập | MySQL/Loki không publish host; Grafana/Prometheus chỉ bind localhost |
| Bí mật | `.env`, `mysql-exporter/.my.cnf` nằm ngoài Git |

Lệnh kiểm thử cơ bản:

```powershell
docker compose exec nginx nginx -t
curl.exe -I http://localhost/
curl.exe -s -o NUL -w "%{http_code}`n" http://localhost/.env
```

Kỳ vọng: website HTTP `200` (trừ khi bị giới hạn tạm thời), có ba security headers, `/.env` bị chặn (thường `403`). Với rate limiting, kiểm tra nhiều request trong thời gian ngắn và xác nhận xuất hiện `429`; không dùng bài test tải nặng kéo dài trên máy yếu.

**Giới hạn bảo mật cần công bố:** hiện sử dụng HTTP, chưa có TLS/HTTPS; cAdvisor chạy `privileged: true`; WordPress/PHP có thể lộ header nhận diện công nghệ; chưa cấu hình backup tự động, firewall/WAF hoặc quản lý bí mật chuyên dụng. Đây là **lab hardening**, không nên coi là triển khai production an toàn.

## 10. Kiểm tra vận hành và xử lý lỗi

```powershell
# Xác nhận Compose hợp lệ
docker compose config --quiet

# Trạng thái dịch vụ
docker compose ps

# Nginx config và website
docker compose exec nginx nginx -t
curl.exe -I http://localhost/

# Log chẩn đoán
docker compose logs --tail=50 nginx
docker compose logs --tail=50 wordpress
docker compose logs --tail=50 mysql
docker compose logs --tail=50 nginx-exporter
docker compose logs --tail=50 mysql-exporter
docker compose logs --tail=50 loki
docker compose logs --tail=50 promtail
```

| Triệu chứng | Kiểm tra/khắc phục |
|---|---|
| `docker compose config` báo biến thiếu | Kiểm tra `.env` và tên biến Grafana/MySQL |
| MySQL không `healthy` | `docker compose logs mysql`; kiểm tra volume và mật khẩu lần khởi tạo |
| MySQL Exporter `mysql_up=0` | Kiểm tra tài khoản/grants, mật khẩu `.my.cnf`, kết nối `host=mysql` |
| Nginx Exporter `nginx_up=0` | Kiểm tra `nginx -t`, endpoint nội bộ `/stub_status` và mạng `edge` |
| Grafana không thấy metrics | Kiểm tra datasource `http://prometheus:9090`, Prometheus Targets, dashboard import |
| Grafana không thấy log | Kiểm tra `access.log` là file thực, log Promtail/Loki, datasource `http://loki:3100` và time range |
| cAdvisor thiếu metrics từng container | Có thể là giới hạn Docker Desktop/Windows; dùng metrics tổng hợp và ghi rõ hạn chế |
| HTTP 429 khi truy cập liên tục | Rate limiting đang hoạt động; đợi rồi thử lại |
| Không mở được phpMyAdmin từ host | Cấu hình hiện tại chưa publish port/route cho phpMyAdmin |

## 11. Lưu trữ và bảo toàn dữ liệu

Named volumes: `mysql_data`, `wordpress_data`, `prometheus_data`, `grafana_data`, `nginx_logs`, `loki_data`, `promtail_positions`.

```powershell
# Dừng và xóa container/network, vẫn giữ named volumes
docker compose down

# Khởi động lại với dữ liệu hiện có
docker compose up -d
```

**Không chạy `docker compose down -v` hoặc `docker volume prune`** nếu muốn giữ dữ liệu WordPress, MySQL, Grafana, Prometheus và Loki. Volume **không thay thế bản sao lưu**; cần backup MySQL và dữ liệu WordPress trước khi xóa volume/nâng cấp.

Lưu ý: sửa `MYSQL_PASSWORD` trong `.env` sau khi MySQL đã khởi tạo **không tự đổi mật khẩu tài khoản đang lưu trong database**. Cần thay đổi tương ứng trong MySQL nếu muốn cập nhật credential.

## 12. Quy trình Git

Repository: https://github.com/dtc245200382-sudo/DeTai19_WebsiteTinTuc

- Giai đoạn 1: WordPress + MySQL + phpMyAdmin + Nginx.
- Giai đoạn 2: Prometheus + Grafana + exporters (PR #1).
- Giai đoạn 3: Loki + Promtail + LogQL (PR #2).
- Giai đoạn 4: Hardening Nginx; kiểm tra trạng thái merge của nhánh tương ứng trước khi ghi nhận là đã hợp nhất.

Các thay đổi mới nên làm trên nhánh `feature/*`, commit, push nhánh feature rồi tạo Pull Request vào `main`. Không push trực tiếp vào `main`.

Ví dụ:

```powershell
git switch main
git pull --ff-only origin main
git switch -c docs/complete-readme
# Chỉnh sửa README.md
git add README.md
git diff --cached --check
git commit -m "docs: complete deployment and operations README"
# Chỉ push khi đã kiểm tra và sẵn sàng tạo PR
git push -u origin docs/complete-readme
```

## 13. Checklist nghiệm thu và ảnh minh chứng

- [ ] `docker compose config --quiet` thành công; các container chính đang chạy.
- [ ] Website truy cập được, đăng nhập WordPress Admin, có bài viết/chuyên mục minh họa.
- [ ] Prometheus Targets `UP` cho Prometheus, Nginx Exporter, MySQL Exporter và cAdvisor.
- [ ] Grafana có datasource Prometheus và dashboard thể hiện metrics.
- [ ] Grafana có datasource Loki, Explore trả về log Nginx bằng LogQL.
- [ ] Security headers, chặn `/.env` và rate limiting đã được kiểm thử.
- [ ] README không chứa mật khẩu thật; `.env` và `.my.cnf` không được commit.
- [ ] Báo cáo tối thiểu 10 trang có kiến trúc, bước triển khai, ảnh chụp, kiểm thử và hạn chế.

Ảnh nên chuẩn bị: website WordPress; `docker compose ps`; Prometheus Targets; Grafana Dashboard; Grafana Explore với LogQL; kết quả HTTP headers/403/429; lịch sử commit/Pull Request.

## 14. Giới hạn và hướng phát triển

- Chưa có HTTPS/TLS và tên miền thực.
- Chưa có backup/restore tự động và giám sát cảnh báo production.
- Grafana datasource/dashboard chưa được provision tự động trên máy mới.
- Cơ chế chuyển symlink log Nginx thành file thực còn thủ công.
- cAdvisor trên Docker Desktop có thể thiếu metrics riêng của từng container.
- Promtail đã hết hỗ trợ; có thể chuyển sang Grafana Alloy trong tương lai.
- Cần giảm quyền container, rà soát cấu hình bí mật và bổ sung kiểm thử bảo mật trước khi triển khai Internet.

---

**Repository:** https://github.com/dtc245200382-sudo/DeTai19_WebsiteTinTuc
