# DALN_AND_WEB_APP_2026

Website quản lý rạp chiếu phim và đặt vé trực tuyến, phát triển bằng Laravel.

## Mã nguồn

- [Ứng dụng Laravel](./4aesieunhan-main/4aesieunhan-main/)
- [Báo cáo phân tích dự án](./4aesieunhan-main/4aesieunhan-main/BAO_CAO_PHAN_TICH_DU_AN.md)
- [Cấu hình môi trường mẫu](./4aesieunhan-main/4aesieunhan-main/.env.example)

## Chạy ứng dụng
1. Cài PHP theo yêu cầu trong `composer.json` và cài các dependency bằng `composer install`.
2. Sao chép `.env.example` thành `.env`, sau đó điền cấu hình database và các dịch vụ cần dùng. Không đưa tệp `.env` hoặc khóa dịch vụ lên Git.
3. Tạo khóa ứng dụng bằng `php artisan key:generate`.
4. Chuẩn bị schema và dữ liệu MySQL đã được rà soát; bản dump SQL chứa dữ liệu mẫu không được đưa vào repository.
5. Khởi chạy ứng dụng bằng `php artisan serve`.....

Các chức năng thanh toán VNPay yêu cầu cấu hình `VNPAY_TMN_CODE` và `VNPAY_HASH_SECRET`; mặc định URL thanh toán trong cấu hình là môi trường sandbox.