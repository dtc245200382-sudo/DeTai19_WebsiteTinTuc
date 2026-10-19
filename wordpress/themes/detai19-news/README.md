# DeTai19 News

Theme WordPress PHP tối giản cho Website Tin tức / Cổng thông tin.

- `home.php`: bài đầu tiên nổi bật và các bài mới còn lại từ truy vấn WordPress; không lặp bài nổi bật.
- `single.php`: bài viết, tác giả, ngày đăng, ảnh đại diện và tối đa ba bài cùng danh mục.
- `category.php`: truy vấn danh mục gốc và phân trang WordPress.
- `index.php`: danh sách dự phòng và kết quả tìm kiếm.
- `template-parts/card.php`: thẻ bài viết dùng chung; khung CSS khi chưa có ảnh đại diện.
- `functions.php`: đăng ký tính năng theme và CSS cho frontend, không thêm hook quản trị.
- `header.php`, `footer.php`, `style.css`: bố cục navy–trắng, menu danh mục có bài, responsive.

## Triển khai

Compose mount riêng thư mục theme vào `/var/www/html/wp-content/themes/detai19-news`
ở chế độ chỉ đọc, giữ volume WordPress hiện có. Sau `docker compose config --quiet`,
áp dụng bằng `docker compose up -d --no-deps wordpress`.

Máy mới cần kích hoạt **DeTai19 News** trong Giao diện → Giao diện của WordPress.
Việc kích hoạt được WordPress lưu trong database, không được lưu trong Git.
Có thể quay về Twenty Twenty-Five bằng cùng giao diện quản trị.

Không cần tạo bài mẫu, danh mục mẫu, menu hoặc thay đổi thiết lập trang chủ.
Theme dùng cấu hình hiện tại: trang chủ hiển thị bài viết mới nhất.
Không sửa Core hoặc dữ liệu nội dung. Không có JavaScript hoặc thư viện giao diện ngoài.

## Kiểm tra trên môi trường hiện tại

WordPress 7.1.3 / PHP 8.3.35. Tất cả file PHP qua `php -l`;
Compose hợp lệ, bind mount có `RW=false`. Kích hoạt bằng `switch_theme()` sau khi
kiểm tra `wp_get_theme()->errors()` và xác nhận theme cũ là Twenty Twenty-Five.
Đối chiếu SHA-256 dữ liệu posts, postmeta, terms, term_taxonomy,
term_relationships, termmeta, users, usermeta, comments, commentmeta trước/sau kích hoạt:
không thay đổi. WordPress cập nhật các tùy chọn và cache liên quan đến chuyển theme.

Dữ liệu hiện có gồm ba bài công khai, ít hơn giới hạn mười bài/trang;
chưa kiểm thử chuyển sang trang phân trang tiếp theo bằng dữ liệu thật.
Không tạo thêm nội dung để kiểm thử.

Kiểm tra Chromium ở 1440×1000 và 390×844: trang chủ, bài viết và danh mục
trả HTTP 200, không tràn ngang, không lỗi JavaScript; liên kết trang chủ → bài viết
→ danh mục → trang chủ hoạt động. Đã xem ảnh chụp để kiểm tra bố cục.
Dữ liệu hiện tại chưa có ảnh đại diện nên giao diện hiển thị khung thay thế;
nhánh hiển thị ảnh đại diện thật chưa được kiểm chứng bằng dữ liệu hiện có.
