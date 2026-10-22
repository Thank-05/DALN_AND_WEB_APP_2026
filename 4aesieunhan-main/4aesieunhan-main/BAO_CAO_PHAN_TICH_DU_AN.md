# BÁO CÁO PHÂN TÍCH VÀ MÔ TẢ DỰ ÁN

## Xây dựng website quản lý và đặt vé hệ thống rạp chiếu phim

---

## 1. Tóm tắt đề tài

Đề tài xây dựng một website phục vụ hai nhóm nghiệp vụ chính: cung cấp thông tin và đặt vé xem phim trực tuyến cho khách hàng; hỗ trợ quản trị viên và nhân viên quản lý dữ liệu, vận hành các nghiệp vụ tại rạp. Hệ thống được phát triển trên Laravel theo cấu trúc MVC, sử dụng PHP, các view Blade, cơ sở dữ liệu quan hệ MySQL và một tập hợp chức năng tập trung quanh phim, rạp, phòng chiếu, ghế, lịch chiếu, vé, tài khoản và dịch vụ đi kèm.

Ở phía khách hàng, website có các trang giới thiệu phim, lịch chiếu, thông tin rạp, tin tức và sự kiện; hỗ trợ tìm kiếm/lọc phim, đăng ký và đăng nhập tài khoản, chọn suất chiếu, chọn ghế, đặt combo, thanh toán, xem thông tin vé và quản lý hồ sơ. Mã nguồn cũng có luồng áp dụng mã giảm giá, tích điểm, yêu cầu hoàn vé và gửi email liên quan đến vé/tài khoản.

Ở phía quản trị, các route và controller cho thấy có chức năng quản lý phim, thể loại, diễn viên, đạo diễn, rạp, phòng, ghế, lịch chiếu, vé, món ăn, combo, khuyến mãi, banner, tin tức, sự kiện, tài khoản khách hàng và nhân viên. Nhân viên có các luồng bán vé/bán combo trực tiếp và quét mã vé hoặc mã combo. Khu vực quản trị có các route thống kê và lọc doanh thu.

Qua đối chiếu với báo cáo tham khảo, đề tài có **mức độ tương đồng rất cao về tên bài toán, lĩnh vực, nhóm người dùng, nghiệp vụ đặt vé và bộ chức năng quản lý rạp**. Báo cáo tham khảo cũng mô tả hệ thống rạp chiếu phim dùng Laravel/PHP/MySQL và nhiều nghiệp vụ xuất hiện trong mã nguồn dự án này. Tuy nhiên, không nên mặc định mọi kết quả hay mức độ hoàn thiện trong tài liệu tham khảo là kết quả của phiên bản mã nguồn hiện tại. Các nội dung như triển khai trên Railway, điểm Lighthouse, kiểm thử JMeter với 1.000 người dùng, OTP, hoàn tiền thật và chat Zalo cần được xác minh độc lập trước khi đưa vào báo cáo.

## 2. Cơ sở phân tích và giới hạn

### 2.1. Tài liệu và thành phần đã xem xét

- Tài liệu Word tham khảo: `BC_DALN.docx`, có đề tài “Xây dựng website quản lý hệ thống rạp chiếu phim”.
- Mã nguồn Laravel trong thư mục dự án: các route web/admin/API, controller, middleware, model, cấu hình Composer, view Blade, cấu hình kiểm thử.
- Workspace có tệp dump cơ sở dữ liệu `database/cinema.sql` nằm ngoài thư mục ứng dụng. Tệp dump và SQL cập nhật dữ liệu không được đưa vào commit vì có thể chứa dữ liệu mẫu hoặc thông tin cá nhân; cần cung cấp schema/seed dữ liệu đã rà soát riêng nếu muốn tái tạo cơ sở dữ liệu từ repository.

### 2.2. Điều chưa được xác minh

- Không chạy toàn bộ hệ thống với môi trường dữ liệu và dịch vụ thực tế; do đó báo cáo này không kết luận các luồng giao diện có hoạt động đầu-cuối hay không.
- Không xác minh tài khoản triển khai, tên miền, thông tin máy chủ, cấu hình production hoặc trạng thái dịch vụ thanh toán.
- Không có bằng chứng đo kiểm mới cho Lighthouse/JMeter, tải đồng thời, thời gian phản hồi, độ sẵn sàng, bảo mật hoặc khả năng phục hồi.
- Chưa đối chiếu mọi view, quan hệ dữ liệu và từng nhánh nghiệp vụ đến mức kiểm thử chấp nhận; các đánh giá “có dấu hiệu triển khai” cần được kiểm tra lại khi chạy demo.
- Bản dump SQL nằm ngoài thư mục ứng dụng Laravel và được loại khỏi commit. Báo cáo cần ghi chính xác nguồn schema/dữ liệu đã rà soát và dùng trong lần demo/triển khai của nhóm.

## 3. Đối chiếu với báo cáo tham khảo

### 3.1. Kết luận về mức độ tương đồng

| Khía cạnh | Nhận xét |
|---|---|
| Lĩnh vực và mục tiêu | Rất tương đồng: cùng hướng đến website quản lý rạp chiếu phim và hỗ trợ khách hàng đặt vé trực tuyến. |
| Nhóm người dùng | Rất tương đồng: khách hàng, quản trị viên và nhân viên là các nhóm được phản ánh trong route, middleware và chức năng quản trị. |
| Nghiệp vụ cốt lõi | Rất tương đồng: quản lý phim/lịch chiếu/rạp/phòng/ghế, đặt vé, combo, thanh toán và xử lý vé. |
| Công nghệ | Tương đồng cao: tài liệu tham khảo mô tả Laravel, PHP, MySQL và JavaScript; dự án sử dụng Laravel/PHP, có view Blade, mã JavaScript và tệp dump MySQL. |
| Quản trị và báo cáo | Tương đồng cao: dự án có nhóm route quản trị CRUD và các route lọc/thống kê. Cần chạy thử để xác nhận số liệu, bộ lọc và quyền truy cập chính xác. |
| Hoàn thiện, triển khai và kết quả đo kiểm | Chưa thể suy ra từ mã nguồn. Không được sao chép điểm số, ảnh, số liệu tải hoặc tuyên bố triển khai trong báo cáo tham khảo như thể chúng là kết quả dự án hiện tại. |

**Kết luận:** Có thể sử dụng tài liệu tham khảo làm mẫu về bố cục, phạm vi chủ đề và các nhóm nội dung cần có. Về chức năng, mã nguồn dự án có nhiều phần trùng khớp. Tuy nhiên, cần viết lại mô tả theo đúng phiên bản phần mềm, ghi nhận riêng các chức năng đã chạy thử, chưa hoàn thiện hoặc chưa kiểm chứng; không sao chép nguyên văn nội dung và kết quả của tài liệu tham khảo.

### 3.2. Đối chiếu theo nhóm chức năng

| Nhóm chức năng trong tài liệu tham khảo | Bằng chứng tìm thấy trong dự án | Đánh giá thận trọng |
|---|---|---|
| Xem danh sách phim, chi tiết phim, lọc và tìm kiếm | Có route trang phim, chi tiết phim, lọc phim, tìm kiếm; có controller xử lý trang chủ và thông tin phim. | Có dấu hiệu triển khai; cần chạy thử bộ lọc với dữ liệu thực. |
| Quản lý phim và thể loại | Có nhóm route quản trị phim và thể loại; controller CRUD/trạng thái tương ứng. | Có bằng chứng ở mã nguồn. |
| Quản lý diễn viên, đạo diễn | Có controller và route quản trị riêng; có trang chi tiết diễn viên/đạo diễn ở phía người dùng. | Có bằng chứng ở mã nguồn. |
| Quản lý rạp, phòng chiếu và ghế | Có route và controller quản trị rạp/phòng/ghế; dữ liệu quan hệ xuất hiện trong dump SQL. | Có bằng chứng ở mã nguồn; cần kiểm thử các ràng buộc khi sửa/xóa và thay đổi trạng thái. |
| Quản lý lịch chiếu | Có route quản trị lịch chiếu và route tra lịch theo phim/rạp; lịch chiếu liên kết với phim và phòng. | Có bằng chứng ở mã nguồn; chưa xác nhận kiểm tra trùng suất/phòng khi vận hành. |
| Đặt ghế, giữ chỗ | Có luồng tạo vé và các bản ghi ghế; controller loại ghế đã có vé và xóa các vé giữ quá thời hạn 9 phút khi vào trang chọn vé. | Có dấu hiệu triển khai. Chưa thấy cơ sở để khẳng định cơ chế đồng thời là nguyên tử hoặc ngăn mọi tình huống hai khách cùng giữ ghế. |
| Combo và món ăn | Có model/route quản trị món ăn, combo; luồng đặt combo và bảng chi tiết combo/vé combo. | Có bằng chứng ở mã nguồn; cần xác minh số lượng, giá và ràng buộc dữ liệu trong giao diện. |
| Tài khoản khách hàng và hồ sơ | Có đăng ký, đăng nhập, đăng xuất, xác minh email, quên/đổi mật khẩu, hồ sơ và thay đổi thông tin. | Có bằng chứng ở mã nguồn; quy trình xác minh thực tế phụ thuộc cấu hình mail. |
| Nhân viên, phân quyền | Có nhóm route quản lý nhân viên, cấp quyền; route admin dùng middleware xác thực admin và role `admin|staff`; có Spatie Permission. | Có cấu trúc phân quyền; cần kiểm thử từng role để xác nhận quyền đúng mức tối thiểu. |
| Khuyến mãi và điểm | Có route/controller mã giảm giá; model/vé có trường khuyến mãi; xử lý thanh toán có nhánh giảm giá và tích điểm. | Có dấu hiệu triển khai; cần kiểm tra điều kiện áp dụng, giới hạn số lượng và tính đúng số điểm. |
| Thanh toán trực tuyến | Có controller tích hợp luồng VNPay sandbox và callback kết quả. | Chỉ khẳng định có mã tích hợp sandbox. Không khẳng định thanh toán production, đối soát chữ ký, hoàn tiền thật hoặc vận hành ổn định nếu chưa kiểm chứng. |
| Hoàn vé | Có route/controller yêu cầu hoàn vé. | Có chức năng xử lý trong mã; chưa xác minh hoàn tiền thật. Tài liệu tham khảo mô tả hạn chế hoàn tiền trong môi trường test, nhưng cần kiểm tra trạng thái của dự án hiện tại. |
| Quét vé/mã vạch, bán vé tại rạp | Có route quản trị quét vé, quét combo, mua vé/bán combo trực tiếp; dự án khai báo thư viện QR/barcode. | Có bằng chứng cho luồng và dependency; cần demo thiết bị quét/mã hợp lệ và không hợp lệ. |
| Thống kê doanh thu | Có các route `filter-by-date`, `statistical-filter`, `statistical-sortby`, tìm phim/rạp cho thống kê. | Có bằng chứng về cấu trúc; cần chạy thử và so sánh thủ công với dữ liệu giao dịch. |
| Tin tức, sự kiện, banner | Có route/controller quản trị tin tức, sự kiện, banner; phía người dùng có trang danh sách/chi tiết. | Có bằng chứng ở mã nguồn. |
| Song ngữ Việt/Anh | Có route đổi locale `vi`/`en`, middleware ngôn ngữ và tệp ngôn ngữ. | Có cấu trúc hỗ trợ; chưa xác nhận toàn bộ nội dung dữ liệu động và thông báo được dịch. |
| OTP qua số điện thoại | Không thấy luồng SMS OTP trong các thành phần đã rà soát. | Chưa có bằng chứng; không đưa vào danh sách chức năng hoàn thành nếu nhóm chưa tích hợp và kiểm thử dịch vụ SMS. |
| Chat Zalo | Chưa xác minh được một luồng chat hoàn chỉnh từ các route/controller đã rà soát. | Không kết luận có chat hoạt động. Nếu giao diện chỉ nhúng liên kết/widget thì mô tả đúng mức đó. |
| Railway, Lighthouse, JMeter | Tài liệu tham khảo có nội dung triển khai/đo kiểm; mã nguồn hiện tại không chứng minh các số liệu đó. | Cần tự triển khai và đo lại, lưu ảnh chụp/cấu hình/kết quả. |

### 3.3. Lưu ý khi dùng báo cáo mẫu

Tài liệu tham khảo có thông tin thời gian không nhất quán: trang bìa ghi Hà Nội năm 2026, trong phần phạm vi lại nêu thời gian thực hiện từ 17/04/2023 đến 11/07/2023; một số nguồn và số liệu khảo sát cũng ghi thời điểm truy cập năm 2023. Vì vậy:

1. Không lấy mốc thời gian, tên nhóm, tên giảng viên hoặc số liệu thị trường trong tài liệu tham khảo làm thông tin của nhóm mình.
2. Nếu vẫn dùng thông tin về CGV/Galaxy, cần khảo sát lại, dẫn nguồn cập nhật và ghi ngày truy cập mới.
3. Không sao chép kết quả chấm điểm, số lượng người dùng giả lập hay kết luận hiệu năng; phải đính kèm bằng chứng từ lần kiểm thử của chính dự án.
4. Nên dùng tài liệu tham khảo làm khung chương mục, sau đó diễn đạt và phân tích lại bằng kết quả của mã nguồn và demo hiện tại.

## 4. Bối cảnh, vấn đề và mục tiêu

### 4.1. Bối cảnh

Quy trình đặt vé rạp bao gồm nhiều thông tin liên quan với nhau: phim, rạp, phòng, ghế, ngày giờ chiếu và dịch vụ đi kèm. Nếu quản lý bằng nhiều bảng tính hoặc xử lý thủ công, nhân viên khó đồng bộ trạng thái suất chiếu và ghế; khách hàng phải liên hệ hoặc đến rạp để hỏi thông tin, đồng thời việc tổng hợp doanh thu và lịch sử vé tốn thời gian.

Website tập trung đưa các dữ liệu vận hành này vào một hệ thống chung. Khách hàng có thể tra cứu thông tin và thực hiện một phần hành trình mua vé trên website; nhân viên/quản trị viên quản lý danh mục và thực hiện các thao tác nghiệp vụ tương ứng từ giao diện quản trị.

### 4.2. Mục tiêu tổng quát

Phân tích, thiết kế và xây dựng ứng dụng web hỗ trợ giới thiệu phim, tra cứu lịch chiếu, đặt vé và dịch vụ đi kèm; đồng thời hỗ trợ quản trị dữ liệu và nghiệp vụ tại rạp trong một ứng dụng Laravel có cơ sở dữ liệu quan hệ.

### 4.3. Mục tiêu cụ thể

- Tổ chức dữ liệu về phim, thể loại, diễn viên, đạo diễn, rạp, phòng, ghế và lịch chiếu.
- Cho phép khách hàng xem phim, tìm suất chiếu, chọn ghế và lập đơn vé.
- Ghi nhận combo/món ăn đi kèm và lưu thông tin vé theo khách hàng, suất chiếu.
- Cung cấp tài khoản, hồ sơ và các luồng xác thực tài khoản cơ bản.
- Cung cấp giao diện quản trị để quản lý danh mục, nội dung và tài khoản.
- Hỗ trợ thao tác tại rạp như bán vé/bán combo và kiểm tra mã vé ở mức có trong mã nguồn.
- Cung cấp dữ liệu cần thiết để lọc và thống kê doanh thu.
- Tạo nền tảng để kiểm thử, hoàn thiện quy trình thanh toán, bảo mật và hiệu năng trong các giai đoạn tiếp theo.

### 4.4. Phạm vi

**Trong phạm vi mã nguồn đang có:** website phía khách hàng, giao diện quản trị, dữ liệu rạp/phim/lịch chiếu/vé/dịch vụ ăn uống, các luồng tài khoản, đặt vé, một tích hợp thanh toán sandbox, và một số chức năng bán/soát vé tại rạp.

**Ngoài phạm vi có thể khẳng định từ bằng chứng hiện tại:** ứng dụng mobile riêng, vận hành cho nhiều cụm rạp ngoài dữ liệu demo, thanh toán production, tích hợp SMS OTP, hoàn tiền production, hệ thống chat trực tuyến hoàn chỉnh, khả năng chịu tải ở quy mô xác định, và triển khai production có giám sát/SLA. Chỉ đưa các nội dung này vào “hướng phát triển” nếu chưa có sản phẩm và kiểm thử tương ứng.

## 5. Đối tượng sử dụng và yêu cầu nghiệp vụ

### 5.1. Các vai trò

| Vai trò | Nhu cầu chính | Nhóm chức năng liên quan |
|---|---|---|
| Khách hàng | Tra cứu phim/lịch chiếu, đặt vé, chọn ghế, mua combo, thanh toán, xem thông tin vé và quản lý hồ sơ. | Trang phim, lịch chiếu, đăng ký/đăng nhập, luồng vé, hồ sơ, mã giảm giá. |
| Nhân viên | Hỗ trợ bán vé/bán combo tại rạp và xác nhận mã vé/dịch vụ. | Khu vực `buyTicket`, `buyCombo`, `scanTicket`, `scanCombo`. |
| Quản trị viên | Quản lý dữ liệu hệ thống, nội dung, nhân sự và theo dõi số liệu kinh doanh. | Các route `/admin`, quản lý danh mục, tài khoản, phân quyền, thống kê. |

Mã nguồn nhóm quyền route quản trị qua middleware `admin` và role `admin|staff`. Phạm vi quyền cụ thể từng chức năng cần được xác nhận bằng cách đăng nhập từng vai trò và kiểm thử truy cập trực tiếp URL, không chỉ dựa trên việc menu bị ẩn/hiện.

### 5.2. Yêu cầu chức năng

#### Phía khách hàng

1. Xem trang chủ, banner, phim đang chiếu/sắp chiếu, tin tức và sự kiện.
2. Duyệt danh sách phim, xem chi tiết phim, tìm kiếm/lọc phim.
3. Tra cứu lịch chiếu theo phim hoặc theo rạp.
4. Đăng ký, đăng nhập, đăng xuất; xác minh email, khôi phục/cập nhật mật khẩu theo các route hiện có.
5. Chọn suất chiếu, ghế và combo; tạo vé và theo dõi kết quả thanh toán.
6. Xem hồ sơ, cập nhật thông tin, lịch sử/thông tin vé và yêu cầu hoàn vé theo khả năng thực tế của phiên bản.
7. Gửi phản hồi và xem các trang thông tin liên hệ/nội dung.

#### Phía quản trị và nhân viên

1. Quản lý phim, thể loại, diễn viên, đạo diễn, rạp, phòng, ghế và lịch chiếu.
2. Quản lý banner, tin tức, sự kiện, khuyến mãi, món ăn, combo và thông tin website.
3. Quản lý khách hàng/nhân viên; tạo tài khoản, đổi trạng thái, phân quyền.
4. Xem danh sách vé và các thông tin phục vụ vận hành.
5. Lọc/thống kê dữ liệu doanh thu theo bộ lọc có trong màn hình quản trị.
6. Thực hiện bán vé, bán combo, quét và xác nhận vé/mã combo tại rạp.

### 5.3. Yêu cầu phi chức năng cần đặt ra khi nghiệm thu

Các yêu cầu dưới đây là tiêu chí nên đánh giá; không phải kết quả đã đo:

- **Tính đúng đắn dữ liệu:** không bán trùng ghế trong cùng một suất; tổng tiền, giảm giá, điểm và trạng thái thanh toán phải được tính nhất quán.
- **Bảo mật:** xác thực và phân quyền ở phía máy chủ; bảo vệ thông tin cá nhân; kiểm soát truy cập vào vé; bảo vệ luồng thanh toán và bí mật cấu hình.
- **Hiệu năng:** thời gian tải các trang quan trọng cần đo trên môi trường, bộ dữ liệu và thiết bị xác định.
- **Khả dụng:** thông báo lỗi rõ ràng, xử lý callback thanh toán và tình huống dịch vụ ngoài bị gián đoạn.
- **Khả năng bảo trì:** quy trình dữ liệu, cấu hình, cài đặt và triển khai có tài liệu; migration/seed cần nhất quán.
- **Tương thích:** kiểm tra trên trình duyệt và kích thước màn hình nằm trong phạm vi nghiệm thu của nhóm.
- **Khả năng truy cập và SEO:** đánh giá bằng công cụ và kiểm tra thủ công trước khi nêu kết quả.

## 6. Quy trình nghiệp vụ chính

### 6.1. Tra cứu phim và lịch chiếu

1. Khách hàng truy cập trang chủ hoặc danh sách phim.
2. Hệ thống hiển thị phim, thông tin chi tiết và các dữ liệu liên quan như thể loại, diễn viên, đạo diễn.
3. Khách hàng tìm kiếm/lọc phim hoặc mở một phim cụ thể.
4. Hệ thống cung cấp lịch chiếu theo phim/rạp để khách hàng chọn suất phù hợp.

Các route phía web có trang phim, lọc phim, tìm kiếm, chi tiết phim và tra cứu lịch theo phim/rạp. Tiêu chí lọc và tính đầy đủ của kết quả cần được xác nhận trực tiếp trên UI.

### 6.2. Đặt vé trực tuyến

1. Khách hàng chọn một suất chiếu và mở trang chọn vé.
2. Hệ thống đọc lịch chiếu, phòng, sơ đồ ghế, loại ghế, giá và các combo có trạng thái hoạt động.
3. Khách hàng chọn ghế. Mã nguồn có bước kiểm tra ghế đã xuất hiện trong vé của suất chiếu và từ chối yêu cầu nếu thấy trùng.
4. Hệ thống tạo vé ở trạng thái giữ chỗ, gắn ghế vào vé; bộ xử lý trang chọn vé loại bỏ các vé giữ quá khoảng 9 phút theo điều kiện đang có trong controller.
5. Khách hàng chọn combo/mã giảm giá nếu phù hợp.
6. Hệ thống chuyển sang thanh toán; sau callback, cập nhật trạng thái vé và chuyển khách hàng đến trang kết quả.
7. Khách hàng xem trang vé hoàn tất/thông tin vé.

**Điểm cần kiểm thử kỹ:** kiểm tra giữ chỗ đồng thời bằng ít nhất hai phiên trình duyệt; thử tải lại trang, đóng trình duyệt, hết hạn giữ chỗ, thanh toán thành công/thất bại và callback lặp. Việc truy vấn kiểm tra ghế trước khi tạo vé không tự nó chứng minh tránh được race condition; nên dùng transaction, khóa/unique constraint phù hợp nếu yêu cầu vận hành đòi hỏi chống bán trùng tuyệt đối.

### 6.3. Bán và xác nhận tại rạp

Route quản trị có nhóm thao tác mua vé, thanh toán tại quầy, mua combo và quét vé/combo. Báo cáo nên mô tả đây là một phần mềm hỗ trợ nhân viên, kèm ảnh và kịch bản demo: vé hợp lệ, vé không tồn tại, vé đã sử dụng, mã combo hợp lệ và lỗi thiết bị/đầu vào. Chỉ kết luận nghiệp vụ hoàn chỉnh sau khi các tình huống đó được chạy thử.

### 6.4. Quản lý danh mục và lịch chiếu

Quản trị viên/nhân viên có quyền phù hợp tạo và cập nhật dữ liệu danh mục. Một lịch chiếu gắn với phim, phòng, ngày giờ và thông tin âm thanh/phụ đề; phòng gắn với rạp và loại phòng; ghế gắn với phòng và loại ghế. Khi viết báo cáo, cần bổ sung quy tắc nghiệp vụ mà nhóm thực sự áp dụng, ví dụ: không cho xếp hai suất chồng giờ trong cùng phòng, không xóa phòng đã có lịch hoặc vé, và cách xử lý suất chiếu bị hủy.

### 6.5. Tài khoản và vai trò

Ứng dụng có luồng đăng ký/đăng nhập và một nhóm chức năng hồ sơ, mật khẩu, xác minh email. Khu vực quản trị được giới hạn bằng middleware và role. Khi demo cần phân biệt rõ:

- Tài khoản khách hàng dùng luồng đăng nhập khách hàng.
- Tài khoản admin/nhân viên truy cập khu vực quản trị.
- Quyền của nhân viên được cấp theo cấu hình phân quyền; cần chứng minh bằng thử nghiệm truy cập.
- Email verification không đồng nghĩa SMS OTP; không dùng hai khái niệm này thay thế cho nhau.

## 7. Kiến trúc và công nghệ

### 7.1. Kiến trúc tổng thể

Hệ thống được tổ chức theo mô hình MVC của Laravel:

- **Route:** ánh xạ URL và phương thức HTTP tới controller. Route web nằm trong [routes/web.php](./routes/web.php); các chức năng quản trị nằm trong [routes/admin.php](./routes/admin.php); [routes/api.php](./routes/api.php) hiện có route Sanctum mẫu trả về thông tin người dùng đã xác thực.
- **Controller:** xử lý luồng nghiệp vụ và điều phối truy cập model/view. Có các controller tập trung cho web, xác thực, admin, thanh toán, nhân viên và từng nhóm dữ liệu.
- **Model:** biểu diễn các thực thể như `Movie`, `Schedule`, `Room`, `Seat`, `Ticket`, `User`, `Combo`, `Food` và các dữ liệu hỗ trợ.
- **View:** các trang giao diện Blade cho phía `web` và `admin`.
- **Database:** lưu thông tin danh mục, tài khoản, lịch chiếu, vé và giao dịch liên quan.
- **Middleware:** xử lý session, ngôn ngữ, trạng thái tài khoản, xác thực admin và role/permission.

Đây là ứng dụng web server-rendered; route API hiện thấy trong [routes/api.php](./routes/api.php) chỉ là endpoint `/api/web` có `auth:sanctum`, vì vậy không nên mô tả dự án như một REST API đầy đủ hoặc ứng dụng SPA nếu chưa có phần triển khai khác.

### 7.2. Công nghệ và thư viện nhìn thấy trong dự án

| Thành phần | Bằng chứng trong dự án | Vai trò |
|---|---|---|
| PHP | [composer.json](./composer.json) yêu cầu PHP `^8.2`. | Ngôn ngữ phía máy chủ. |
| Laravel | [composer.json](./composer.json) yêu cầu `laravel/framework` `^9.0`. | Framework web, định tuyến, MVC, session, xác thực và truy cập dữ liệu. |
| Blade | Các view `.blade.php` trong [resources/views/](./resources/views/). | Render giao diện phía máy chủ. |
| MySQL | Tệp dump SQL dùng cú pháp MySQL/InnoDB và cấu hình Laravel có cấu hình kết nối cơ sở dữ liệu. | Lưu dữ liệu quan hệ của hệ thống. |
| Laravel Sanctum | Dependency và route API mẫu có `auth:sanctum`. | Hỗ trợ xác thực API/token; chưa đủ bằng chứng để nói API là sản phẩm chính. |
| Spatie Laravel Permission | Dependency và middleware role/permission được đăng ký. | Quản lý vai trò/quyền truy cập. |
| Cloudinary | Dependency và cấu hình Cloudinary tồn tại. | Tích hợp lưu trữ/quản lý media; cần xác nhận môi trường có cấu hình hợp lệ. |
| Barcode/QR | Có khai báo thư viện tạo barcode/QR và các view/route liên quan đến mã vé. | Hỗ trợ sinh/hiển thị/đọc mã theo luồng đã xây dựng. |
| VNPay sandbox | `PaymentController` tạo URL thanh toán sandbox và xử lý kết quả trả về. | Thử nghiệm luồng thanh toán; chưa phải bằng chứng thanh toán production. |
| JavaScript/CSS và Laravel Mix | Có `resources/js`, tài nguyên giao diện và `webpack.mix.js`. | Xử lý tương tác phía trình duyệt và pipeline tài nguyên. |
| PHPUnit | [phpunit.xml](./phpunit.xml), test Unit và Feature mẫu. | Khung chạy kiểm thử tự động; hiện bộ test trong repository còn rất mỏng. |

### 7.3. Luồng xử lý yêu cầu điển hình

```text
Trình duyệt
    -> Laravel route
    -> middleware (session/ngôn ngữ/xác thực/role)
    -> controller
    -> model và cơ sở dữ liệu
    -> Blade view hoặc response/redirect
    -> Trình duyệt
```

Với thanh toán, luồng bổ sung dịch vụ ngoài:

```text
Khách hàng -> ứng dụng tạo yêu cầu thanh toán
           -> cổng thanh toán sandbox
           -> callback/return URL
           -> ứng dụng cập nhật kết quả vé
           -> trang kết quả
```

Khi mô tả luồng trên báo cáo, cần phân biệt việc trình duyệt quay lại URL kết quả với việc máy chủ đã xác minh callback an toàn và đối soát giao dịch. Chỉ khi kiểm tra toàn bộ xử lý chữ ký, trạng thái giao dịch và các tình huống callback mới được kết luận về tính tin cậy của thanh toán.

## 8. Thiết kế cơ sở dữ liệu

Tệp dump SQL có các bảng chính dưới đây. Tên bảng được ghi theo đúng dạng xuất hiện trong dump; mô hình logic cần được vẽ lại từ quan hệ khóa ngoại và model trước khi chèn ERD vào báo cáo.

### 8.1. Nhóm tài khoản và phân quyền

- `users`: tài khoản khách hàng/nhân viên theo cấu trúc ứng dụng.
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`: cấu trúc phân quyền.
- `personal_access_tokens`: token Sanctum.

### 8.2. Nhóm phim và nội dung

- `movies`, `movie_genres`, `moviegenres_movies`
- `casts`, `casts_movies`
- `directors`, `directors_movies`
- `audios`, `subtitles`
- `banners`, `news`, `posts`, `infos`, `feedback`, `rating`

Các bảng liên kết thể hiện quan hệ nhiều-nhiều giữa phim với thể loại, diễn viên và đạo diễn.

### 8.3. Nhóm rạp và lịch chiếu

- `theaters`: thông tin rạp.
- `rooms`: phòng chiếu thuộc rạp.
- `roomtypes`: loại phòng/định dạng và phụ thu.
- `seats`: ghế thuộc phòng.
- `seattypes`: loại ghế và phụ thu.
- `schedules`: lịch chiếu gắn với phim, phòng và thông tin âm thanh/phụ đề.
- `prices`: dữ liệu bảng giá.

### 8.4. Nhóm đặt vé và dịch vụ

- `tickets`: vé gắn với khách hàng và lịch chiếu.
- `ticketseats`: các ghế thuộc vé.
- `ticketcombos`, `ticketfoods`: dịch vụ gắn với vé.
- `combos`, `combo_details`, `foods`: combo, thành phần combo và món ăn.
- `discounts`: dữ liệu mã/điều kiện giảm giá.

### 8.5. Quan hệ nghiệp vụ chính

```text
Theater 1 --- N Room 1 --- N Seat
Room 1 --- N Schedule N --- 1 Movie
Schedule 1 --- N Ticket N --- 1 User
Ticket 1 --- N TicketSeat
Ticket 1 --- N TicketCombo / TicketFood
Movie N --- N MovieGenre
Movie N --- N Cast
Movie N --- N Director
```

Sơ đồ trên là mô tả logic khái quát. Trước khi đưa vào báo cáo, cần xác nhận tên khóa ngoại thực tế trong SQL/model, cardinality, ràng buộc xóa, khóa duy nhất và các trường snapshot của vé. Đặc biệt, nên kiểm tra việc giá vé/giá combo có được lưu cố định tại thời điểm mua hay chỉ lấy từ dữ liệu danh mục hiện tại.

### 8.6. Nhận xét về quản lý cấu trúc dữ liệu

Trong thư mục ứng dụng có seeders, nhưng qua rà soát chưa xác định được bộ migration đầy đủ để tái tạo toàn bộ schema. Dump `database/cinema.sql` ở workspace được loại khỏi commit để tránh phát tán dữ liệu mẫu/cá nhân; vì vậy repository hiện chưa cung cấp một schema đầy đủ để dựng cơ sở dữ liệu từ đầu. Nhóm nên chuẩn hóa migration và seed dữ liệu đã làm sạch, tránh tình trạng máy demo và môi trường triển khai có cấu trúc khác nhau. Không đưa dữ liệu cá nhân hoặc thông tin cấu hình nhạy cảm vào ảnh/chụp màn hình báo cáo.

## 9. Mô tả các phân hệ

### 9.1. Phân hệ khách hàng

Phần giao diện khách hàng tổ chức các trang trang chủ, phim, chi tiết phim, lịch chiếu, tìm kiếm, tin tức/sự kiện, thông tin diễn viên/đạo diễn, đặt vé, kết quả vé và hồ sơ. Mục tiêu là giúp khách hàng tìm được phim và suất phù hợp, hoàn tất thao tác chọn chỗ và dịch vụ, sau đó xem lại thông tin vé.

Khi viết phần hiện thực, nên chèn ảnh theo thứ tự hành trình người dùng: trang chủ → danh sách/chi tiết phim → lịch chiếu → chọn ghế → chọn combo → thanh toán → vé hoàn tất → hồ sơ/lịch sử. Mỗi ảnh nên có chú thích nêu dữ liệu đầu vào, thao tác và kết quả mong đợi.

### 9.2. Phân hệ quản trị

Khu vực quản trị có các nhóm quản lý nội dung và vận hành: phim, thể loại, rạp, phòng, ghế, lịch chiếu, vé, khách hàng, nhân viên, banner, diễn viên, đạo diễn, combo, món ăn, giảm giá, tin tức, sự kiện, giá và thông tin website. Các controller tương ứng nhận yêu cầu từ route quản trị và thao tác với model.

Nên lập bảng test CRUD cho từng nhóm dữ liệu:

| Thao tác | Ca kiểm thử tối thiểu |
|---|---|
| Tạo | Dữ liệu hợp lệ; thiếu trường bắt buộc; dữ liệu trùng; ảnh/URL không hợp lệ. |
| Xem/tìm kiếm | Danh sách rỗng; nhiều trang; từ khóa có dấu/không dấu; lọc trạng thái. |
| Sửa | Sửa một trường; dữ liệu sai định dạng; sửa khi bản ghi đã được tham chiếu. |
| Xóa/vô hiệu hóa | Bản ghi chưa tham chiếu; bản ghi đã có lịch/vé; xác nhận hành vi cascade hoặc từ chối. |
| Phân quyền | Admin; nhân viên có quyền; nhân viên không có quyền; khách hàng truy cập trực tiếp URL admin. |

### 9.3. Phân hệ thống kê

Các route quản trị cho lọc theo ngày và tiêu chí, sắp xếp thống kê, tìm kiếm phim/rạp. Báo cáo nên giải thích rõ:

- Chỉ số được thống kê (ví dụ doanh thu, số vé, số suất) và công thức.
- Trạng thái giao dịch được tính vào doanh thu.
- Cách áp dụng ngày bắt đầu/kết thúc, múi giờ và khoảng ngày.
- Cách lọc theo phim/rạp/thể loại/dịch vụ nếu giao diện có hỗ trợ.
- Kết quả đối chiếu với truy vấn dữ liệu độc lập.

Không chỉ chụp biểu đồ làm bằng chứng; hãy chọn một tập dữ liệu nhỏ, tự cộng các giao dịch và so sánh với con số trên trang quản trị.

## 10. Kiểm thử và đánh giá

### 10.1. Trạng thái kiểm thử quan sát được

Repository có cấu hình PHPUnit và hai bài kiểm thử mẫu: kiểm thử Feature gọi trang chủ và kỳ vọng HTTP 200; kiểm thử Unit kiểm tra một biểu thức đúng. Đây chưa phải bộ kiểm thử nghiệp vụ. Chưa có cơ sở để tuyên bố các chức năng đặt vé, thanh toán, phân quyền hay thống kê đã được kiểm thử tự động đầy đủ.

### 10.2. Kịch bản kiểm thử đề xuất

| Mã | Nhóm | Kịch bản | Kết quả cần ghi nhận |
|---|---|---|---|
| T01 | Tài khoản | Đăng ký với dữ liệu hợp lệ và email đã tồn tại. | Kết quả tạo tài khoản, thông báo, email xác minh. |
| T02 | Phân quyền | Khách hàng truy cập URL admin; staff thử chức năng ngoài quyền; admin truy cập chức năng được phép. | Mã phản hồi/redirect và quyền thực tế. |
| T03 | Lịch chiếu | Tạo lịch hợp lệ, lịch chồng giờ và lịch gắn phòng không hoạt động. | Quy tắc cho phép/từ chối và thông báo. |
| T04 | Ghế | Hai phiên đồng thời chọn cùng ghế trong cùng suất. | Chỉ một lượt giữ/mua ghế thành công; trạng thái còn lại rõ ràng. |
| T05 | Giữ chỗ | Thanh toán trước hạn, hết hạn, đóng tab và quay lại trang. | Thời gian hết hạn và trạng thái ghế/vé sau từng nhánh. |
| T06 | Giá | Ghế thường/VIP/couple, phụ thu phòng và giá theo suất. | Tổng tiền khớp quy tắc giá đã công bố. |
| T07 | Khuyến mãi | Mã hợp lệ, hết hạn, hết lượt, không đủ điều kiện, dùng lặp. | Giá cuối, số lượng mã còn lại và trạng thái vé. |
| T08 | Thanh toán | Callback thành công/thất bại, sai chữ ký, lặp callback, giao dịch không khớp vé. | Không ghi nhận sai hoặc cộng điểm trùng; có log/ thông báo phù hợp. |
| T09 | Hoàn vé | Vé không đủ điều kiện, vé đủ điều kiện, gửi yêu cầu lặp. | Quy tắc hoàn/đổi điểm và trạng thái vé. |
| T10 | Quét vé | Mã hợp lệ, không tồn tại, đã dùng, sai suất chiếu. | Thông báo và trạng thái sử dụng. |
| T11 | Thống kê | So sánh kết quả bộ lọc với dữ liệu giao dịch đã biết. | Kết quả chính xác theo công thức và mốc thời gian. |
| T12 | Email | Xác minh tài khoản/đặt vé trong mailer test và mailer cấu hình thực. | Nội dung, URL, người nhận, lỗi dịch vụ. |
| T13 | Giao diện | Kiểm tra desktop/mobile, biểu mẫu lỗi và ngôn ngữ Việt/Anh. | Ảnh chụp, trình duyệt, kích thước màn hình và lỗi phát hiện. |
| T14 | Hiệu năng | Lighthouse và kiểm thử tải với số người dùng/tốc độ tăng tải đã định. | File kết quả, cấu hình, thời điểm, URL, máy chủ và metric. |

### 10.3. Cách báo cáo kết quả

Với từng ca, ghi: mã ca, điều kiện ban đầu, dữ liệu kiểm thử, các bước, kết quả mong đợi, kết quả thực tế, trạng thái đạt/không đạt, ảnh/log và người thực hiện. Với Lighthouse/JMeter, lưu bản báo cáo gốc cùng cấu hình chạy. Không lấy lại điểm trong báo cáo tham khảo để điền vào kết quả của dự án.

## 11. Hạn chế, rủi ro và hướng phát triển

### 11.1. Các điểm cần làm rõ trước khi bảo vệ

1. **Tính nhất quán của đặt ghế:** kiểm tra race condition và thêm cơ chế khóa/transaction hoặc ràng buộc phù hợp nếu cần bảo đảm ghế không thể bán trùng.
2. **Thanh toán:** xác minh chữ ký, số tiền, mã đơn, callback lặp, timeout và đối soát. Mọi thông tin bí mật cần nằm trong cấu hình môi trường an toàn, không đặt trực tiếp trong source code hoặc báo cáo.
3. **Trạng thái vé và điểm:** bảo đảm cập nhật giao dịch là idempotent, không cộng điểm/giảm lượt khuyến mãi nhiều lần khi callback lặp.
4. **Validation và phân quyền:** rà soát validation phía server và quyền trên từng endpoint, đặc biệt các thao tác sửa/xóa vé, combo và tài khoản.
5. **Dữ liệu khởi tạo:** thống nhất schema/dump, migrations và seeders; ghi rõ quy trình dựng môi trường demo.
6. **Kiểm thử:** bổ sung test cho đặt ghế, giá, giảm giá, callback thanh toán, hoàn vé và phân quyền.
7. **Bản địa hóa:** kiểm tra dữ liệu do admin nhập và thông báo lỗi/hệ thống ở cả hai ngôn ngữ.
8. **Hiệu năng:** tối ưu ảnh và truy vấn; đo trước/sau bằng cùng môi trường, cùng dữ liệu và cùng công cụ.
9. **Thông tin cấu hình:** quản lý biến môi trường; không đưa khóa, mật khẩu hoặc dữ liệu nhạy cảm vào tài liệu nộp.

Các điểm trên là nội dung cần kiểm tra hoặc cải tiến, không phải kết luận rằng tất cả đều đang lỗi. Báo cáo cuối cần phân biệt “đã phát hiện lỗi khi kiểm thử” với “rủi ro cần xác minh”.

### 11.2. Hướng phát triển

- Hoàn thiện kiểm soát giữ ghế và quy trình thanh toán an toàn, có đối soát.
- Xây dựng bộ test tự động cho các luồng quan trọng và test tích hợp với dịch vụ giả lập.
- Chuẩn hóa REST API nếu có nhu cầu tích hợp ứng dụng mobile hoặc hệ thống đối tác.
- Bổ sung thông báo trạng thái suất chiếu, hoàn/hủy vé và xử lý hoàn tiền theo chính sách.
- Hoàn thiện báo cáo doanh thu, xuất dữ liệu và kiểm toán thao tác quản trị.
- Cải thiện khả năng tiếp cận, tối ưu ảnh, caching và truy vấn cơ sở dữ liệu.
- Mở rộng đa ngôn ngữ đầy đủ; bổ sung thông báo email/SMS chỉ khi có nhà cung cấp và quy trình tuân thủ phù hợp.
- Triển khai môi trường production có HTTPS, sao lưu/khôi phục, logging, giám sát và quản lý secret đúng chuẩn.
- Cân nhắc ứng dụng mobile/chatbot chỉ sau khi xác định nhu cầu, chi phí vận hành và rủi ro dữ liệu.

## 12. Kết luận

Dự án giải quyết một bài toán có ý nghĩa thực tiễn trong quản lý hoạt động rạp chiếu và đặt vé trực tuyến. Qua cấu trúc mã nguồn, có thể xác nhận hệ thống đã có các thành phần cho cả phía khách hàng lẫn quản trị/nhân viên: quản lý danh mục phim và rạp, lịch chiếu, đặt vé/ghế, combo, tài khoản, phân quyền, nội dung và một số luồng thanh toán/thao tác tại rạp. Cấu trúc Laravel MVC cùng dữ liệu quan hệ giúp phân tách tương đối rõ các nhóm chức năng.

Đề tài tương đồng rất cao với báo cáo tham khảo ở bài toán, đối tượng sử dụng và nhiều nghiệp vụ cốt lõi. Điểm cần thận trọng là mức tương đồng về chủ đề không chứng minh hai báo cáo có cùng kết quả triển khai. Phiên bản báo cáo chính thức cần dựa vào demo, test và số liệu của chính nhóm. Các nội dung trọng yếu cần xác nhận thêm là chống đặt trùng ghế, độ chính xác thống kê, xác thực callback thanh toán, quyền từng vai trò, email thực tế và trạng thái triển khai production.

### Đoạn kết luận mẫu có thể dùng sau khi kiểm thử

> “Trong phạm vi đồ án, nhóm đã xây dựng được website hỗ trợ [liệt kê chức năng đã demo thành công]. Qua các kịch bản kiểm thử, hệ thống đạt [số ca đạt]/[tổng số ca], trong đó các chức năng [nêu chức năng] hoạt động theo yêu cầu; các nội dung [nêu hạn chế] cần tiếp tục hoàn thiện. Nhóm đã đo [công cụ, thời gian, môi trường] và ghi nhận [số liệu thực tế]. Kết quả cho thấy sản phẩm đáp ứng [phạm vi đã kiểm chứng], đồng thời cần tiếp tục cải thiện về [bảo mật/hiệu năng/nghiệp vụ cụ thể].”

Không giữ nguyên phần trong ngoặc vuông trong bản nộp; thay bằng dữ liệu thực tế.

## 13. Danh sách hình, bảng và phụ lục nên bổ sung

1. Sơ đồ ngữ cảnh hệ thống và các actor.
2. Use case tổng quát; use case khách hàng đặt vé; use case admin quản lý lịch chiếu; use case nhân viên quét vé.
3. Sơ đồ tuần tự cho đặt vé, thanh toán, callback và quét vé.
4. Sơ đồ hoạt động cho chọn suất/ghế, xử lý hết thời hạn giữ chỗ và xử lý thanh toán lỗi.
5. ERD được dựng từ schema thực tế, có cardinality và khóa chính/ngoại.
6. Ảnh giao diện desktop/mobile theo quy trình đặt vé và màn hình quản trị chính.
7. Bảng phân quyền vai trò và kết quả thử truy cập từng chức năng.
8. Bảng test case, log lỗi, bằng chứng chạy test.
9. Báo cáo Lighthouse/JMeter do nhóm tự chạy và cấu hình của phép đo.
10. Hướng dẫn cài đặt: phiên bản PHP/Composer, cấu hình môi trường không chứa secret, khởi tạo DB, seed dữ liệu và chạy ứng dụng.
11. Phụ lục mô tả giới hạn của môi trường thanh toán sandbox và cách mô phỏng các trạng thái giao dịch.

## 14. Tài liệu mã nguồn tham chiếu trong dự án

- Route khách hàng: [routes/web.php](./routes/web.php)
- Route quản trị: [routes/admin.php](./routes/admin.php)
- Route API: [routes/api.php](./routes/api.php)
- Xử lý nghiệp vụ khách hàng/đặt vé: [WebController.php](./app/Http/Controllers/WebController.php)
- Xác thực tài khoản: [AuthController.php](./app/Http/Controllers/AuthController.php)
- Thống kê và quản lý người dùng/nhân viên: [AdminController.php](./app/Http/Controllers/AdminController.php)
- Thanh toán: [PaymentController.php](./app/Http/Controllers/PaymentController.php)
- Nhân viên, bán vé và quét mã: [StaffController.php](./app/Http/Controllers/StaffController.php)
- Cấu hình role/middleware: [Kernel.php](./app/Http/Kernel.php), [AdminMiddleware.php](./app/Http/Middleware/AdminMiddleware.php)
- Model: [app/Models/](./app/Models/)
- Giao diện: [resources/views/](./resources/views/)
- Dependency PHP: [composer.json](./composer.json)
- Cấu hình PHPUnit và test mẫu: [phpunit.xml](./phpunit.xml), [ExampleTest.php](./tests/Feature/ExampleTest.php), [ExampleTest.php](./tests/Unit/ExampleTest.php)
- Dump SQL mẫu tại workspace (`database/cinema.sql`) không được đưa vào repository; cần thay bằng migration/seed đã rà soát nếu muốn chia sẻ schema và dữ liệu demo.
