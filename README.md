# 🎬 TTCinema – Hệ thống đặt vé xem phim trực tuyến

Website đặt vé xem phim và trang quản trị rạp chiếu, xây dựng bằng **Laravel 9** (PHP 8.2). Đây là Đồ án liên nghành (DALN) 2026.

Hệ thống gồm hai phần:

- **Website khách hàng**: xem phim, lịch chiếu, chọn ghế, mua combo đồ ăn, thanh toán qua VNPay, nhận vé qua email.
- **Trang quản trị (Admin / Staff)**: quản lý phim, rạp, phòng, ghế, lịch chiếu, giá vé, khuyến mãi, bán vé tại quầy, quét mã vé và thống kê doanh thu.

---

## 📑 Mục lục

- [Tính năng](#-tính-năng)
- [Công nghệ sử dụng](#-công-nghệ-sử-dụng)
- [Cấu trúc thư mục](#-cấu-trúc-thư-mục)
- [Yêu cầu hệ thống](#-yêu-cầu-hệ-thống)
- [Cài đặt](#-cài-đặt)
- [Cấu hình biến môi trường](#-cấu-hình-biến-môi-trường)
- [Chạy bằng Docker](#-chạy-bằng-docker)
- [Tài khoản mặc định](#-tài-khoản-mặc-định)
- [Phân quyền](#-phân-quyền)
- [Các đường dẫn chính](#-các-đường-dẫn-chính)
- [Thanh toán VNPay](#-thanh-toán-vnpay)
- [Đa ngôn ngữ](#-đa-ngôn-ngữ)
- [Kiểm thử](#-kiểm-thử)
- [Giấy phép](#-giấy-phép)

---

## ✨ Tính năng

### Khách hàng (Website)

- Đăng ký, đăng nhập, xác thực email, quên mật khẩu / đặt lại mật khẩu qua email
- Trang chủ với banner, phim đang chiếu và phim sắp chiếu
- Danh sách phim, lọc phim, tìm kiếm phim
- Chi tiết phim, thông tin đạo diễn và diễn viên
- Xem lịch chiếu theo **rạp** hoặc theo **phim**
- Chọn ghế theo sơ đồ phòng chiếu (nhiều loại ghế, giá theo loại ghế)
- Mua thêm combo / đồ ăn kèm vé
- Áp dụng mã giảm giá và tích điểm thành viên
- Thanh toán trực tuyến qua **VNPay** (hoặc QR demo khi chưa cấu hình VNPay)
- Nhận vé điện tử (mã vạch / QR code) qua email
- Quản lý hồ sơ cá nhân, lịch sử vé, yêu cầu hoàn vé, đổi mật khẩu
- Xem tin tức, sự kiện; gửi góp ý / liên hệ
- Giao diện song ngữ **Tiếng Việt / English**

### Quản trị viên & Nhân viên (Admin)

- Dashboard thống kê doanh thu, lọc theo ngày, theo phim, theo rạp
- Quản lý **thể loại phim, phim, đạo diễn, diễn viên**
- Quản lý **rạp, phòng chiếu, ghế** (bật/tắt ghế, sửa theo hàng)
- Quản lý **lịch chiếu** (tạo, sửa, bật/tắt, xóa hàng loạt)
- Quản lý **giá vé**, **mã giảm giá**, **đồ ăn & combo**
- Quản lý **banner, tin tức, sự kiện**
- Quản lý **vé** đã bán, **tài khoản khách hàng**, **tài khoản nhân viên**
- **Bán vé và bán combo tại quầy** (có thanh toán)
- **Quét mã vé / mã combo** để soát vé tại rạp
- Xem góp ý của khách hàng
- Cập nhật thông tin rạp và hồ sơ cá nhân
- Phân quyền chi tiết theo chức năng cho từng nhân viên

---

## 🛠 Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Ngôn ngữ | PHP 8.2 |
| Framework | Laravel 9 |
| Cơ sở dữ liệu | MySQL |
| Giao diện | Blade template, Bootstrap, jQuery |
| Phân quyền | [spatie/laravel-permission](https://github.com/spatie/laravel-permission) |
| Lưu trữ ảnh | [Cloudinary](https://cloudinary.com/) (`cloudinary-labs/cloudinary-laravel`) |
| Thanh toán | [VNPay](https://vnpay.vn/) (sandbox) |
| Mã vạch / QR | `milon/barcode`, `picqer/php-barcode-generator`, `simplesoftwareio/simple-qrcode` |
| Realtime (tùy chọn) | Firebase Realtime Database |
| Email | Laravel Mail (SMTP) |
| Xác thực API | Laravel Sanctum |
| Triển khai | Docker |

---

## 📂 Cấu trúc thư mục

Mã nguồn nằm trong `4aesieunhan-main/4aesieunhan-main/`:

```
4aesieunhan-main/4aesieunhan-main/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Controller cho web và admin (Movie, Ticket, Payment, Staff, ...)
│   │   ├── Middleware/      # Middleware xác thực, phân quyền, ngôn ngữ
│   │   └── Helper/          # Hàm dùng chung (GlobalFunction.php)
│   ├── Models/              # Eloquent models (Movie, Schedule, Seat, Ticket, Combo, ...)
│   └── function/            # Hàm hỗ trợ Cloudinary
├── config/                  # Cấu hình Laravel, services (VNPay, Firebase, Cloudinary)
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/             # Dữ liệu mẫu: rạp, phòng, ghế, phim, người dùng, quyền, ...
├── public/                  # Tài nguyên tĩnh (CSS, JS, hình ảnh)
├── resources/
│   ├── lang/                # Ngôn ngữ: vi, en
│   └── views/
│       ├── admin/           # Giao diện trang quản trị
│       └── web/             # Giao diện website khách hàng
├── routes/
│   ├── web.php              # Route website khách hàng
│   ├── admin.php            # Route trang quản trị
│   └── api.php
├── tests/
├── Dockerfile
└── composer.json
```

---

## 💻 Yêu cầu hệ thống

- PHP **>= 8.2** với các extension: `gd`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`
- Composer 2.x
- MySQL 5.7+ / MariaDB 10.3+
- (Tùy chọn) Docker
- Tài khoản Cloudinary để upload ảnh
- (Tùy chọn) Tài khoản VNPay sandbox, SMTP mail, Firebase

---

## 🚀 Cài đặt

**1. Clone dự án**

```bash
git clone https://github.com/Thank-05/DALN_AND_WEB_APP_2026.git
cd DALN_AND_WEB_APP_2026/4aesieunhan-main/4aesieunhan-main
```

> Mã nguồn Laravel nằm trong thư mục `4aesieunhan-main/4aesieunhan-main/`. Mọi lệnh bên dưới đều chạy trong thư mục này.

**2. Cài đặt thư viện PHP**

```bash
composer install
```

**3. Tạo file môi trường và khóa ứng dụng**

```bash
cp .env.example .env
php artisan key:generate
```

**4. Tạo cơ sở dữ liệu**

Tạo database MySQL (mặc định tên `cinema`) và cập nhật thông tin kết nối trong `.env`:

```env
DB_DATABASE=cinema
DB_USERNAME=root
DB_PASSWORD=
```

**5. Tạo bảng và dữ liệu mẫu**

Thư mục `database/migrations` đã có đủ migration cho 37 bảng. Với database trống:

```bash
php artisan migrate --seed
```

Lệnh trên tạo toàn bộ bảng và nạp dữ liệu mẫu (3 rạp, phòng, ghế, phim, bảng giá, 3 vai trò, 18 quyền, tài khoản mặc định).

Nếu đã có database nhập từ bản dump cũ `cinema.sql`, chỉ cần chạy `php artisan migrate`: các migration tự bỏ qua bảng đã có và chỉ bổ sung các cột còn thiếu (`verify_token`, `reset_token`, `banners.user_id`).

**6. Chạy ứng dụng**

```bash
php artisan serve
```

Truy cập:

- Website: <http://localhost:8000>
- Trang quản trị: <http://localhost:8000/admin/sign_in>

---

## ⚙️ Cấu hình biến môi trường

Các biến quan trọng trong `.env`:

| Biến | Mô tả |
|---|---|
| `APP_URL` | URL của ứng dụng (dùng để tạo link trả về từ VNPay, link trong email) |
| `DB_*` | Thông tin kết nối MySQL |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS` | Cấu hình SMTP để gửi email xác thực, đặt lại mật khẩu, vé điện tử |
| `CLOUD_NAME`, `CLOUDINARY_URL`, `CLOUDINARY_UPLOAD_PRESET`, `CLOUDINARY_NOTIFICATION_URL` | Cấu hình Cloudinary để upload ảnh phim, banner, diễn viên, ... |
| `VNPAY_TMN_CODE`, `VNPAY_HASH_SECRET`, `VNPAY_URL` | Thông tin merchant VNPay |
| `FIREBASE_*` | Cấu hình Firebase Realtime Database (tùy chọn) |

Ví dụ cấu hình Gmail SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
```

> 🔒 Không commit file `.env` lên GitHub. File này đã được thêm vào `.gitignore`.

---

## 🐳 Chạy bằng Docker

Dự án có `Dockerfile` (PHP 8.2 + `gd`, `pdo_mysql`, `zip`) và `docker-compose.yml` (ứng dụng + MySQL 8.0):

```bash
cd 4aesieunhan-main/4aesieunhan-main
cp .env.example .env            # điền APP_KEY, DB_PASSWORD, DB_ROOT_PASSWORD, VNPay, Cloudinary, mail
docker compose up -d --build    # container app tự chạy php artisan migrate --force
docker compose exec app php artisan db:seed --force   # chỉ chạy lần đầu
```

Ứng dụng chạy tại <http://localhost:10000>. File `.env` không được đóng gói vào image (đã khai báo trong `.dockerignore`).

### Triển khai lên VPS

1. Cài Docker và Docker Compose trên VPS (Ubuntu 22.04 trở lên).
2. `git clone` dự án, tạo `.env` từ `.env.example`, đặt `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://<tên-miền>`.
3. `docker compose up -d --build`, sau đó `docker compose exec app php artisan db:seed --force` ở lần đầu.
4. Cấu hình Nginx làm reverse proxy từ cổng 80/443 về `127.0.0.1:10000` và cấp chứng chỉ HTTPS bằng Certbot.
5. URL trả về của VNPay được sinh theo tên miền đang truy cập: `/payment/result` (khách hàng) và `/admin/buyTicket/vnpayResult` (tại quầy), nên website phải được truy cập qua đúng tên miền HTTPS. Nếu đứng sau Nginx, cấu hình `TrustProxies` để Laravel nhận đúng `https`.

---

## 👤 Tài khoản mặc định

Khi dữ liệu mẫu được nạp bằng `php artisan db:seed`:

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | `admin@gmail.com` | `1` |
| Khách hàng | `phuclee0120@gmail.com` | `1` |
| Khách hàng | `minh@gmail.com` | `1` |

> ⚠️ Hãy đổi mật khẩu các tài khoản này khi triển khai thực tế.

---

## 🔐 Phân quyền

Hệ thống dùng **spatie/laravel-permission** với 3 vai trò:

- **admin** – toàn quyền quản trị
- **staff** – nhân viên rạp, được cấp quyền theo từng chức năng
- **user** – khách hàng

Các quyền chức năng: `movie_genre`, `movies`, `theater`, `schedule_movie`, `events`, `ticket`, `food`, `user`, `staff`, `banners`, `director`, `cast`, `statistical`, `discount`, `price`, `buyTicket`, `buyCombo`, `feedback`.

---

## 🧭 Các đường dẫn chính

### Website

| Đường dẫn | Chức năng |
|---|---|
| `/` | Trang chủ |
| `/movies`, `/movie/{id}` | Danh sách / chi tiết phim |
| `/schedulesByTheater`, `/schedulesByMovie` | Lịch chiếu theo rạp / theo phim |
| `/tickets/{schedule_id}` | Chọn ghế, combo và đặt vé |
| `/payment/create`, `/payment/result` | Tạo thanh toán / nhận kết quả VNPay |
| `/profile` | Hồ sơ và lịch sử vé |
| `/news`, `/events` | Tin tức, sự kiện |
| `/search` | Tìm kiếm |
| `/contact` | Liên hệ, góp ý |
| `/lang/{vi\|en}` | Đổi ngôn ngữ |

### Quản trị (`/admin`)

| Đường dẫn | Chức năng |
|---|---|
| `/admin/sign_in` | Đăng nhập quản trị |
| `/admin` | Dashboard thống kê |
| `/admin/movie`, `/admin/movie_genres` | Phim, thể loại |
| `/admin/theater`, `/admin/room`, `/admin/seat/{id}` | Rạp, phòng, ghế |
| `/admin/schedule` | Lịch chiếu |
| `/admin/discount`, `/admin/prices` | Mã giảm giá, giá vé |
| `/admin/buyTicket`, `/admin/buyCombo` | Bán vé / combo tại quầy |
| `/admin/scanTicket`, `/admin/scanCombo` | Quét mã vé / combo |
| `/admin/feedback` | Góp ý khách hàng |

---

## 💳 Thanh toán VNPay

1. Đăng ký tài khoản sandbox tại <https://sandbox.vnpayment.vn/devreg/>.
2. Điền `VNPAY_TMN_CODE` và `VNPAY_HASH_SECRET` vào `.env`.
3. URL trả về được tạo tự động: `{APP_URL}/payment/result`.

Khi kết quả trả về, hệ thống kiểm tra chữ ký `vnp_SecureHash` (HMAC-SHA512), mã phản hồi và số tiền trước khi xác nhận vé.

Nếu chưa cấu hình VNPay, hệ thống hiển thị **trang QR demo** thay vì chuyển sang cổng thanh toán thật.

---

## 🌐 Đa ngôn ngữ

- Ngôn ngữ mặc định: **Tiếng Việt** (`vi`)
- Hỗ trợ: **English** (`en`)
- File ngôn ngữ: `resources/lang/vi`, `resources/lang/en`
- Múi giờ: `Asia/Bangkok` (UTC+7)

---

## 🧪 Kiểm thử

Bộ kiểm thử dùng database riêng `cinema_test` (khai báo trong `phpunit.xml`):

```bash
mysql -u root -e "CREATE DATABASE cinema_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
DB_DATABASE=cinema_test php artisan migrate --seed   # PowerShell: $env:DB_DATABASE="cinema_test"; php artisan migrate --seed
php artisan test
```

`tests/Feature/TicketFlowTest.php` kiểm tra: chặn khách chưa đăng nhập tạo vé, giá ghế và tổng tiền do máy chủ tính, chống giữ trùng ghế, áp mã giảm giá, chặn dùng điểm vượt số dư, chặn xóa và hoàn vé của người khác, thanh toán tiền mặt tại quầy, từ chối kết quả VNPay sai chữ ký, thông báo đăng nhập admin sai.

---

## 📄 Giấy phép

Dự án được phát triển phục vụ mục đích học tập (Đồ án tốt nghiệp). Mã nguồn dựa trên Laravel Framework – phát hành theo giấy phép [MIT](https://opensource.org/licenses/MIT).
