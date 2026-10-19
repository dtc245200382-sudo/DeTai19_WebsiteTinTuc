# DeTai19 News

Theme WordPress PHP tối giản cho Website Tin tức / Cổng thông tin.

- `home.php`: bài đầu tiên nổi bật, các bài mới còn lại và tối đa bốn khối chuyên mục có nhiều bài nhất, mỗi khối ba bài. Bài có thể xuất hiện lại trong khối chuyên mục tương ứng.
- `single.php`: bài viết, tác giả, ngày đăng, ảnh đại diện, tối đa ba bài cùng danh mục và ô sao chép liên kết.
- `category.php`: truy vấn danh mục gốc và phân trang WordPress.
- `index.php`: danh sách dự phòng.
- `search.php`, `searchform.php`: tìm kiếm bài viết bằng WordPress, phân trang, số kết quả và thông báo rỗng; ô tìm kiếm ở header và trang kết quả.
- `sidebar.php`: năm bài mới nhất và cây danh mục, dùng trên trang chủ, danh mục và tìm kiếm.
- `assets/share.js`: sao chép liên kết bằng Clipboard API; chọn sẵn liên kết để sao chép thủ công khi không có quyền. Không có JavaScript vẫn dùng được ô liên kết.
- `template-parts/card.php`: thẻ bài viết dùng chung; khung CSS khi chưa có ảnh đại diện.
- `functions.php`: tính năng theme, CSS frontend, JavaScript chỉ trên bài viết; giới hạn truy vấn tìm kiếm chính ở frontend vào bài viết, không áp dụng trong quản trị.
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
Không sửa Core hoặc dữ liệu nội dung. Không có plugin hoặc thư viện giao diện ngoài.
Thay đổi mã theme hiện tại có hiệu lực qua bind mount, không cần kích hoạt lại hoặc khởi động lại container.

## Kiểm tra trên môi trường hiện tại

WordPress 7.1.3 / PHP 8.3.35. Tất cả file PHP qua `php -l`;
Compose hợp lệ, bind mount có `RW=false`. Lần triển khai ban đầu kích hoạt bằng `switch_theme()` sau khi
kiểm tra `wp_get_theme()->errors()` và xác nhận theme cũ là Twenty Twenty-Five.
Đối chiếu SHA-256 dữ liệu posts, postmeta, terms, term_taxonomy,
term_relationships, termmeta, users, usermeta, comments, commentmeta trước/sau kích hoạt:
không thay đổi. WordPress cập nhật các tùy chọn và cache liên quan đến chuyển theme.

## Kiểm tra bản nâng cấp 1.1

- Chromium ở 1440×900, 768×900 và 390×900: trang chủ, danh mục, bài viết,
  tìm kiếm có kết quả và không có kết quả đều HTTP 200, không tràn ngang,
  không lỗi JavaScript. Đã xem ảnh chụp bố cục.
- Tìm kiếm từ header và sửa từ khóa ở trang kết quả hoạt động; tìm “Chào”
  hiển thị hai bài hiện có; từ khóa không tồn tại hiển thị thông báo rỗng.
- Sidebar có bài mới; danh mục hiện có hiển thị bảy bài, bài liên quan có ba bài
  và không chứa bài đang xem. Liên kết giữa trang chủ, bài và danh mục hoạt động.
- Clipboard chứa đúng permalink; khi Clipboard API không khả dụng,
  ô liên kết được chọn và có hướng dẫn sao chép thủ công.
- Truy vấn tìm kiếm chỉ đọc với một kết quả/trang trong tiến trình kiểm thử:
  hai trang trả các bài khác nhau, liên kết phân trang được tạo đúng.
  Không đổi `posts_per_page` trong database. Chưa nhấn phân trang trên website
  thực vì bảy bài hiện có ít hơn giới hạn mười bài/trang.
- Kiểm tra nội dung bài hiện có, PHP syntax, JavaScript syntax và Compose đạt.
  WordPress chạy, MySQL healthy, phpMyAdmin 8081 trả HTTP 200.
- Trang quản trị chuyển về đăng nhập, không có asset theme trong trang đăng nhập.
  Chưa kiểm tra trực quan trong phiên quản trị đã đăng nhập.
- Chưa kiểm chứng ảnh đại diện thật và nhiều chuyên mục vì dữ liệu hiện tại
  chưa có ảnh đại diện, các bài cùng thuộc một chuyên mục. Không tạo dữ liệu mẫu.
