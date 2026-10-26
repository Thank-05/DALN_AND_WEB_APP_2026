@extends('web.layout.index')
@section('css')
    .vnpay-red {
    color: #e50019;
    font-weight: 700;
    }
    .vnpay-blue {
    color: #004a9c;
    font-weight: 700;
    }
    #qrcode img, #qrcode canvas {
    margin: 0 auto;
    }
@endsection
@section('content')
    <section class="container my-5">
        <div class="card mx-auto shadow" style="max-width: 420px;">
            <div class="card-header text-center fs-4">
                <span class="vnpay-red">VN</span><span class="vnpay-blue">PAY</span><sup class="vnpay-red">QR</sup>
            </div>
            <div class="card-body text-center">
                <p class="mb-1">Quét mã để thanh toán</p>
                <div id="qrcode" class="my-3"></div>
                <p class="mb-1">Mã vé: <strong>{{ $ticket->code }}</strong></p>
                <p class="mb-1">Số tiền: <strong class="text-danger">{{ number_format($amount, 0, ',', '.') }} đ</strong></p>
                <p class="mb-3">Hết hạn sau: <strong id="qrTimer"></strong></p>
                <div class="alert alert-warning small mb-3">
                    Chế độ demo: chưa cấu hình tài khoản VNPay nên mã QR này không thanh toán được.
                </div>
                <a href="/" class="btn btn-warning">Về trang chủ</a>
            </div>
        </div>
    </section>
@endsection
@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        new QRCode(document.getElementById('qrcode'), {
            text: @json('VNPAY|' . $ticket->code . '|' . $amount),
            width: 220,
            height: 220,
        });

        let timer = {{ $expireMinutes }} * 60;
        const display = document.getElementById('qrTimer');
        const tick = () => {
            const m = String(Math.floor(timer / 60)).padStart(2, '0');
            const s = String(timer % 60).padStart(2, '0');
            display.textContent = m + ':' + s;
            if (timer-- <= 0) {
                clearInterval(interval);
                document.getElementById('qrcode').style.opacity = 0.2;
                display.textContent = 'Đã hết hạn';
            }
        };
        const interval = setInterval(tick, 1000);
        tick();
    </script>
@endsection
