<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\Ticket;
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
            'ticket_id'  => 'required|integer|exists:tickets,id',
            'totalPrice' => 'required|numeric|min:0',
        ]);

        $ticket = Ticket::findOrFail($validated['ticket_id']);
        $ticket->holdState = false;
        $ticket->totalPrice = $validated['totalPrice'];
        $ticket->save();

        return response('', 200);
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

        // 5. URL trả về
        $vnp_ReturnUrl = url('/payment/result') . '?' . http_build_query([
            'point'       => $request->input('point', 0),
            'hasDiscount' => $request->input('hasDiscount', 'false'),
        ]);

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
        $user        = Auth::user();
        $hasDiscount = (string) $request->query('hasDiscount', 'false');

        DB::transaction(function () use ($ticket, $user, $hasDiscount) {
            $ticket->hasPaid = true;
            if ($hasDiscount !== 'false') {
                $ticket->hasDiscount = true;
            }
            $ticket->save();

            if (!$user) {
                return;
            }

            if ($hasDiscount === 'false') {
                // Tổng tiền đã thanh toán (dùng biến riêng, không ghi đè $ticket)
                $totalSpent = $user->ticket()->where('hasPaid', true)->sum('totalPrice');
                $rate       = $totalSpent < 4000000 ? 5 : 10;

                $user->point += $ticket->totalPrice * $rate / 100;
                $user->save();
            } else {
                $discount = Discount::find($hasDiscount);
                if ($discount && $discount->quantity > 0) {
                    $discount->decrement('quantity');
                }
            }
        });

        return redirect()->action([WebController::class, 'ticketCompleted'], $ticket->id);
    }
}   