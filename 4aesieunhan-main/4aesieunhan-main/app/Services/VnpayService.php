<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Http\Request;

/**
 * Tạo URL thanh toán và kiểm tra chữ ký kết quả trả về của VNPay (HMAC-SHA512).
 * Khóa bí mật đọc từ config('services.vnpay'), không ghi trong mã nguồn.
 */
class VnpayService
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public static function isConfigured(): bool
    {
        return config('services.vnpay.tmn_code') && config('services.vnpay.hash_secret') && config('services.vnpay.url');
    }

    public static function paymentUrl(Ticket $ticket, string $returnUrl, Request $request, int $expireMinutes = 15): string
    {
        $now = now(self::TIMEZONE);

        $inputData = [
            'vnp_Version'    => '2.1.0',
            'vnp_TmnCode'    => config('services.vnpay.tmn_code'),
            'vnp_Amount'     => (int) round($ticket->totalPrice) * 100,
            'vnp_Command'    => 'pay',
            'vnp_CreateDate' => $now->format('YmdHis'),
            'vnp_CurrCode'   => 'VND',
            'vnp_IpAddr'     => $request->ip(),
            'vnp_Locale'     => in_array($request->input('language'), ['vn', 'en']) ? $request->input('language') : 'vn',
            'vnp_OrderInfo'  => 'Thanh toan GD:' . $ticket->code,
            'vnp_OrderType'  => 'other',
            'vnp_ReturnUrl'  => $returnUrl,
            'vnp_TxnRef'     => $ticket->code,
            'vnp_ExpireDate' => $now->copy()->addMinutes($expireMinutes)->format('YmdHis'),
        ];
        if ($request->filled('bankCode') && $request->input('bankCode') !== 'MONEY') {
            $inputData['vnp_BankCode'] = $request->input('bankCode');
        }

        ksort($inputData);
        $hashData = http_build_query($inputData, '', '&', PHP_QUERY_RFC1738);

        return config('services.vnpay.url') . '?' . $hashData
            . '&vnp_SecureHash=' . hash_hmac('sha512', $hashData, config('services.vnpay.hash_secret'));
    }

    public static function validSignature(Request $request): bool
    {
        $secret = (string) config('services.vnpay.hash_secret');
        if ($secret === '') {
            return false;
        }

        $vnpData = collect($request->query())
            ->filter(fn ($value, $key) => str_starts_with($key, 'vnp_'))
            ->except(['vnp_SecureHash', 'vnp_SecureHashType'])
            ->all();
        ksort($vnpData);

        $calculated = hash_hmac('sha512', http_build_query($vnpData, '', '&', PHP_QUERY_RFC1738), $secret);

        return hash_equals($calculated, strtolower((string) $request->query('vnp_SecureHash', '')));
    }

    public static function succeeded(Request $request): bool
    {
        return $request->query('vnp_ResponseCode') === '00'
            && $request->query('vnp_TransactionStatus', '00') === '00';
    }
}
