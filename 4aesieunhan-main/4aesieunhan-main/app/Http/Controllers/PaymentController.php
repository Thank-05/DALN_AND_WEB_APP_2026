<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\Ticket;
use App\Services\TicketPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /** VNPay yêu cầu thời gian theo múi giờ GMT+7 */
    private const VNP_TIMEZONE = 'Asia/Ho_Chi_Minh';

    /** Thời gian hết hạn mặc định của giao dịch (phút) */
    private const DEFAULT_EXPIRE_MINUTES = 15;

    public function ticketPayment(Request $request)
    {
        $validated = $request->validate([
            'ticket_id'   => 'required|integer|exists:tickets,id',
            'hasDiscount' => 'nullable',
            'point'       => 'nullable|integer|min:0',
        ]);

        $ticket = Ticket::findOrFail($validated['ticket_id']);
        $user   = Auth::user();
        if ($ticket->user_id !== $user->id || $ticket->hasPaid) {
            return response()->json(['error' => 'Vé không hợp lệ.'], 403);
        }

        // Máy chủ tự tính tổng tiền từ ghế và combo đã lưu, không dùng số tiền trình duyệt gửi lên
        $total = TicketPricing::grossTotal($ticket);

        $discountId = null;
        $hasDiscount = (string) ($validated['hasDiscount'] ?? 'false');
        if ($hasDiscount !== 'false' && $hasDiscount !== '') {
            $discount = Discount::where('id', $hasDiscount)->where('status', 1)->where('quantity', '>', 0)->first();
            if (!$discount) {
                return response()->json(['error' => 'Mã giảm giá không còn hiệu lực.'], 422);
            }
            $discountId = $discount->id;
            $total = (int) round($total * (100 - $discount->percent) / 100);
        }

        // Dùng điểm: không áp dụng chung với mã giảm giá
        $point = (int) ($validated['point'] ?? 0);
        if ($point > 0) {
            if ($discountId) {
                return response()->json(['error' => 'Không dùng điểm cùng mã giảm giá.'], 422);
            }
            if (TicketPricing::validPoint($point, (int) $user->point, $total) === null) {
                return response()->json(['error' => 'Số điểm sử dụng không hợp lệ.'], 422);
            }
            $total -= $point;
        }

        $ticket->holdState = false;
        $ticket->totalPrice = $total;
        $ticket->save();

        // Lưu mã giảm giá và điểm phía máy chủ, không truyền qua URL trả về của VNPay
        session()->put('payment.' . $ticket->id, ['discount_id' => $discountId, 'point' => $point]);

        return response()->json(['totalPrice' => $total]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
            'time'      => 'nullable|integer|min:1|max:60',
            'language'  => 'nullable|in:vn,en',
            'bankCode'  => 'nullable|string|max:20',
        ]);

        // 1. Lấy vé, báo lỗi rõ ràng nếu không tồn tại
        $ticket = Ticket::findOrFail($validated['ticket_id']);

        if ($ticket->hasPaid) {
            return redirect('/')->with('error', 'Vé này đã được thanh toán.');
        }

        // 2. Đọc cấu hình VNPay
        $vnp_TmnCode    = config('services.vnpay.tmn_code');
        $vnp_HashSecret = config('services.vnpay.hash_secret');
        $vnp_Url        = config('services.vnpay.url', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');

        // 3. Kiểm tra số tiền
        $amount = (int) round($ticket->totalPrice);
        if ($amount <= 0) {
            return back()->with('error', 'Số tiền thanh toán không hợp lệ.');
        }

        // Chưa có tài khoản VNPay: chỉ hiện bảng QR demo, không thanh toán thật
        if (empty($vnp_TmnCode) || empty($vnp_HashSecret) || empty($vnp_Url)) {
            Log::warning('VNPay credentials are not configured (services.vnpay.*), showing demo QR.');

            return view('web.pages.payment_qr', [
                'ticket'        => $ticket,
                'amount'        => $amount,
                'expireMinutes' => (int) ($validated['time'] ?? self::DEFAULT_EXPIRE_MINUTES),
            ]);
        }

        // 4. Thời gian tạo / hết hạn theo GMT+7
        $now           = now(self::VNP_TIMEZONE);
        $expireMinutes = (int) ($validated['time'] ?? self::DEFAULT_EXPIRE_MINUTES);

        // 5. URL trả về (mã giảm giá và điểm đã lưu trong session ở bước ticketPayment)
        $vnp_ReturnUrl = url('/payment/result');

        $inputData = [
            'vnp_Version'    => '2.1.0',
            'vnp_TmnCode'    => $vnp_TmnCode,
            'vnp_Amount'     => $amount * 100,
            'vnp_Command'    => 'pay',
            'vnp_CreateDate' => $now->format('YmdHis'),
            'vnp_CurrCode'   => 'VND',
            'vnp_IpAddr'     => $request->ip(),
            'vnp_Locale'     => $validated['language'] ?? 'vn',
            'vnp_OrderInfo'  => 'Thanh toan GD:' . $ticket->code,
            'vnp_OrderType'  => 'other',
            'vnp_ReturnUrl'  => $vnp_ReturnUrl,
            'vnp_TxnRef'     => $ticket->code,
            'vnp_ExpireDate' => $now->copy()->addMinutes($expireMinutes)->format('YmdHis'),
        ];

        if (!empty($validated['bankCode'])) {
            $inputData['vnp_BankCode'] = $validated['bankCode'];
        }

        // 6. Tạo chữ ký (giống cách urlencode trong code mẫu của VNPay)
        ksort($inputData);
        $hashData   = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        $paymentUrl = $vnp_Url . '?' . $hashData . '&vnp_SecureHash=' . $secureHash;

        return redirect()->away($paymentUrl);
    }

    public function handleResult(Request $request)
    {
        // 1. Xác thực chữ ký VNPay trả về (bắt buộc, tránh giả mạo kết quả)
        $vnp_HashSecret = config('services.vnpay.hash_secret');

        $vnpData = collect($request->query())
            ->filter(fn ($value, $key) => str_starts_with($key, 'vnp_'))
            ->except(['vnp_SecureHash', 'vnp_SecureHashType'])
            ->all();
        ksort($vnpData);

        $calculatedHash = hash_hmac('sha512', http_build_query($vnpData, '', '&', PHP_QUERY_RFC1738), (string) $vnp_HashSecret);
        $receivedHash   = strtolower((string) $request->query('vnp_SecureHash', ''));

        if (empty($vnp_HashSecret) || !hash_equals($calculatedHash, $receivedHash)) {
            Log::warning('VNPay: invalid signature', ['txnRef' => $request->query('vnp_TxnRef')]);

            return redirect('/')->with('error', 'Chữ ký thanh toán không hợp lệ.');
        }

        // 2. Tìm vé
        $ticket = Ticket::where('code', $request->query('vnp_TxnRef'))->first();
        if (!$ticket) {
            return redirect('/')->with('error', 'Không tìm thấy vé.');
        }

        // Người dùng F5 lại trang kết quả: không cộng điểm lần nữa
        if ($ticket->hasPaid) {
            return redirect()->action([WebController::class, 'ticketCompleted'], $ticket->id);
        }

        // 3. Giao dịch thất bại / bị hủy
        if ($request->query('vnp_ResponseCode') !== '00'
            || $request->query('vnp_TransactionStatus', '00') !== '00') {
            $ticket->delete();

            return redirect('/')->with('error', 'Thanh toán không thành công hoặc đã bị hủy.');
        }

        // 4. Đối chiếu số tiền
        if ((int) $request->query('vnp_Amount') !== (int) round($ticket->totalPrice) * 100) {
            Log::warning('VNPay: amount mismatch', ['ticket' => $ticket->id]);

            return redirect('/')->with('error', 'Số tiền thanh toán không khớp.');
        }

        // 5. Cập nhật vé, điểm, mã giảm giá trong 1 transaction
        $user    = Auth::user();
        $pending = session()->pull('payment.' . $ticket->id, ['discount_id' => null, 'point' => 0]);

        DB::transaction(function () use ($ticket, $user, $pending) {
            $ticket->hasPaid = true;
            if ($pending['discount_id']) {
                $ticket->hasDiscount = true;
            }
            $ticket->save();

            if ($pending['discount_id']) {
                Discount::where('id', $pending['discount_id'])->where('quantity', '>', 0)->decrement('quantity');
                return;
            }

            if (!$user) {
                return;
            }

            if ($pending['point'] > 0) {
                // Có dùng điểm thì trừ điểm, không cộng điểm thưởng cho vé này
                $user->point -= $pending['point'];
            } else {
                // Tổng tiền đã thanh toán (dùng biến riêng, không ghi đè $ticket)
                $totalSpent = $user->ticket()->where('hasPaid', true)->sum('totalPrice');
                $rate       = $totalSpent < 4000000 ? 5 : 10;
                $user->point += $ticket->totalPrice * $rate / 100;
            }
            $user->save();
        });

        return redirect()->action([WebController::class, 'ticketCompleted'], $ticket->id);
    }
}   