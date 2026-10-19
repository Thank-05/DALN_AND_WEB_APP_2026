<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{

    public function ticketPayment(Request $request)
    {
        $ticket = Ticket::find($request->ticket_id);
        $ticket->holdState = false;
        $ticket->totalPrice = $request->totalPrice;
        $ticket->save();

        return response('', 200);
    }

    public function create(Request $request)
    {
        $ticket = Ticket::find($request->ticket_id);
        $vnp_TmnCode = config('services.vnpay.tmn_code');
        $vnp_HashSecret = config('services.vnpay.hash_secret');
        $vnp_Url = config('services.vnpay.url');
        if (!$vnp_TmnCode || !$vnp_HashSecret) {
            throw new \RuntimeException('VNPay credentials are not configured.');
        }
        $vnp_Returnurl = $request->getSchemeAndHttpHost() . "/payment/result?point=" . $request->point . '&hasDiscount=' . $request->hasDiscount;
        $vnp_apiUrl = "http://sandbox.vnpayment.vn/merchant_webapi/merchant.html";
        $apiUrl = "https://sandbox.vnpayment.vn/merchant_webapi/api/transaction";
        //Config input format
        //Expire
        $startTime = date("YmdHis");
        $expire = date('YmdHis', strtotime('+' . $request->time . 'minutes', strtotime($startTime)));

        $vnp_TxnRef = $ticket->code; //Mã giao dịch thanh toán tham chiếu của merchant
        $vnp_Amount = $ticket->totalPrice; // Số tiền thanh toán
        $vnp_Locale = $request->language; //Ngôn ngữ chuyển hướng thanh toán
        $vnp_BankCode = $request->bankCode; //Mã phương thức thanh toán
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR']; //IP Khách hàng thanh toán


        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount * 100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => "Thanh toan GD:" . $vnp_TxnRef,
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_ExpireDate" => $expire,
        );

        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        return redirect($vnp_Url);
    }

    public function handleResult(Request $request)
    {
        $ticket = Ticket::where('code', $request->vnp_TxnRef)->get()->first();

        switch ($request->vnp_ResponseCode) {
            case '00':
                $ticket->hasPaid = true;
                if ($request->hasDiscount != 'false') {
                    $ticket->hasDiscount = true;
                }
                $ticket->save();
                $user = Auth::user();
                $money_payment = 0 ;
                foreach($user['ticket'] as $ticket)
                {
                    $money_payment += $ticket['totalPrice'];
            }
                if($money_payment < 4000000){
                    $point = ($ticket['totalPrice']) * 5 / 100;
                }else{
                    $point = ($ticket['totalPrice']) * 10 / 100;
                }
                if ($request->hasDiscount == 'false') {
                    $user->point += $point;
                } else {
                    $discount  = Discount::find($request->hasDiscount);
                    $discount->quantity--;
                    $discount->save();
                }

                $user->save();
                return redirect()->action([WebController::class, 'ticketCompleted'], $ticket->id);
            default:
                Ticket::where('code', $request->vnp_TxnRef)->delete();
                return redirect('/');
        }
    }
}
