<?php

namespace App\Http\Controllers;

use App\Models\Combo;
use App\Models\Food;
use App\Models\Movie;
use App\Models\Price;
use App\Models\RoomType;
use App\Models\Schedule;
use App\Models\SeatType;
use App\Models\Theater;
use App\Models\Ticket;
use App\Models\TicketCombo;
use App\Models\TicketFood;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\TicketPricing;
use App\Services\VnpayService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StaffController extends Controller
{
    public function __construct()
    {
        $cloud_name = cloud_name();
        view()->share('cloud_name', $cloud_name);
    }

    public function buyTicket(Request $request) {
        // Admin không gắn cố định với một rạp nên được chọn rạp; nhân viên chỉ bán vé tại rạp được gán
        $theaters = collect();
        if (Auth::user()->hasRole('admin')) {
            $theaters = Theater::where('status', 1)->get();
            $theater = $theaters->firstWhere('id', $request->theater)
                ?? Auth::user()->theater
                ?? $theaters->first();
        } else {
            $theater = Auth::user()->theater;
        }
        if (!$theater) {
            return redirect('admin')->with('warning', 'Tài khoản chưa được gán rạp, vui lòng liên hệ quản trị viên!');
        }
        if (isset($request->date)) {
            $date_cur = $request->date;
        } else {
            $date_cur = date('Y-m-d');
        }
        $roomTypes = RoomType::all();
        $movies = Movie::whereDate('releaseDate', '<=', Carbon::today()->format('Y-m-d'))
            ->where('endDate', '>', Carbon::today()->format('Y-m-d'))
            ->get();
        $moviesEarly = Movie::all()->filter(function ($movie) {
            foreach ($movie->schedules as $schedule) {
                if ($schedule->early && $movie->releaseDate > date('Y-m-d')) {
                    return $movie;
                }
            }
            return null;
        });
        return view('admin.buyTicket.buyTicket', [
            'movies' => $movies,
            'moviesEarly' => $moviesEarly,
            'theater' => $theater,
            'theaters' => $theaters,
            'date_cur' => $date_cur,
            'roomTypes' => $roomTypes,
        ]);
    }

    public function ticket($schedule_id) {
        Ticket::where('holdState', false)->where('hasPaid', false)->where('schedule_id', $schedule_id)->delete();
        $ticketsHolds = Ticket::where('holdState', true)->where('schedule_id', $schedule_id)->get();

        foreach ($ticketsHolds as $ticketsHold) {
            $time = strtotime(date('Y-m-d H:i:s')) - strtotime($ticketsHold->created_at);

            if ($time > (9*60)) {
                $ticketsHold->delete();
            }

        }

        $seatTypes = SeatType::all();
        $combos = Combo::where('status', 1)->get();
        $tickets = Ticket::where('schedule_id', $schedule_id)->get();
        $schedule = Schedule::find($schedule_id);
        if (strtotime($schedule->startTime) < strtotime('17:00')) {
            $price = Price::where('generation', 'vtt')
                ->where('day', 'like', '%' . date('l', strtotime($schedule->date)) . '%')
                ->where('after', '08:00')->get()->first()->price;
        } else {
            $price = Price::where('generation', 'vtt')
                ->where('day', 'like', '%' . date('l', strtotime($schedule->date)) . '%')
                ->where('after', '17:00')->get()->first()->price;
        }
        $roomSurcharge = $schedule->room->roomType->surcharge;
        $room = $schedule->room;
        $movie = $schedule->movie;

        return view('admin.buyTicket.ticket', [
            'schedule' => $schedule,
            'room' => $room,
            'seatTypes' => $seatTypes,
            'roomSurcharge' => $roomSurcharge,
            'price' => $price,
            'movie' => $movie,
            'tickets' => $tickets,
            'combos' => $combos,
        ]);
    }

    public function scanBarcode(Request $request) {
        $user = User::where('code', $request->code)->get()->first();
        if (!$user) {
            return response('user not found', 500);
        }
        return response()->json([
            'username' => $user->fullName,
            'userPoint' => $user->point,
            'userId' => $user->code,
        ]);
    }

    public function createPayment(Request $request)
    {
        $ticket = Ticket::findOrFail($request->ticket_id);
        $type = $request->type === 'combo' ? 'combo' : 'ticket';
        if ($ticket->hasPaid) {
            return redirect('admin/buy' . ucfirst($type))->with('fail', 'Vé này đã được thanh toán!');
        }
        if (!VnpayService::isConfigured()) {
            return redirect('admin/buy' . ucfirst($type))->with('fail', 'Chưa cấu hình VNPay, vui lòng thanh toán tiền mặt!');
        }

        // URL trả về trỏ tới handler của quầy, không dùng /payment/result của khách
        $returnUrl = url('/admin/buyTicket/vnpayResult') . '?' . http_build_query(['type' => $type]);

        return redirect()->away(VnpayService::paymentUrl($ticket, $returnUrl, $request, (int) ($request->time ?: 15)));
    }

    /** Thanh toán tiền mặt tại quầy */
    public function handleResult(Request $request)
    {
        $type = $request->type === 'combo' ? 'combo' : 'ticket';
        $ticket = Ticket::find($request->ticket_id);
        if (!$ticket || $ticket->hasPaid || $request->vnp_BankCode !== 'MONEY') {
            return redirect('admin/buy' . ucfirst($type))->with('fail', 'thanh toán thất bại!');
        }

        return $this->completeCounterPayment($ticket, $type);
    }

    /** Kết quả VNPay trả về khi bán tại quầy */
    public function vnpayResult(Request $request)
    {
        $type = $request->query('type') === 'combo' ? 'combo' : 'ticket';
        $redirect = 'admin/buy' . ucfirst($type);

        if (!VnpayService::validSignature($request)) {
            return redirect($redirect)->with('fail', 'Chữ ký thanh toán không hợp lệ!');
        }
        $ticket = Ticket::where('code', $request->query('vnp_TxnRef'))->first();
        if (!$ticket) {
            return redirect($redirect)->with('fail', 'Không tìm thấy vé!');
        }
        if ($ticket->hasPaid) {
            return redirect($redirect)->with('success', 'thanh toán thành công!');
        }
        if (!VnpayService::succeeded($request)) {
            $ticket->delete();
            return redirect($redirect)->with('fail', 'thanh toán thất bại!');
        }
        if ((int) $request->query('vnp_Amount') !== (int) round($ticket->totalPrice) * 100) {
            return redirect($redirect)->with('fail', 'Số tiền thanh toán không khớp!');
        }

        return $this->completeCounterPayment($ticket, $type);
    }

    private function completeCounterPayment(Ticket $ticket, string $type)
    {
        $pending = session()->pull('staff_payment.' . $ticket->id, ['member_id' => null, 'point' => 0]);

        DB::transaction(function () use ($ticket, $pending) {
            $ticket->hasPaid = true;
            $ticket->save();

            $member = $pending['member_id'] ? User::find($pending['member_id']) : null;
            if (!$member) {
                return;
            }
            if ($pending['point'] > 0) {
                $member->point -= $pending['point'];
            } else {
                // Tổng chi tiêu dùng biến riêng, không ghi đè $ticket
                $totalSpent = $member->ticket()->where('hasPaid', true)->sum('totalPrice');
                $rate = $totalSpent < 4000000 ? 5 : 10;
                $member->point += $ticket->totalPrice * $rate / 100;
            }
            $member->save();
        });

        return redirect('admin/buy' . ucfirst($type))->with('success', 'thanh toán thành công!');
    }

    public function ticketPayment(Request $request) {
        $ticket = Ticket::find($request->ticket_id);
        if (!$ticket || $ticket->hasPaid) {
            return response()->json(['error' => 'Vé không hợp lệ'], 422);
        }

        // Tổng tiền do máy chủ tính từ ghế, combo, món lẻ đã lưu
        $total = TicketPricing::grossTotal($ticket);
        $member = $request->userCode ? User::where('code', $request->userCode)->first() : null;
        $point = (int) $request->point;
        if ($point > 0) {
            if (!$member || TicketPricing::validPoint($point, (int) $member->point, $total) === null) {
                return response()->json(['error' => 'Số điểm sử dụng không hợp lệ'], 422);
            }
            $total -= $point;
        }

        $ticket->holdState = false;
        $ticket->totalPrice = $total;
        $ticket->user_id = $member ? $member->id : Auth::user()->id;
        $ticket->save();

        session()->put('staff_payment.' . $ticket->id, [
            'member_id' => $member ? $member->id : null,
            'point' => $point,
        ]);

        return response()->json(['totalPrice' => $total]);
    }

    public function scanTicket() {
        return view('admin.scanTicket.scanTicket');
    }

    public function handleScanTicket(Request $request) {
        $message = 'vé hợp lệ';
        $check = true;
        $seatsList = '';
        $ticket = Ticket::where('code',  $request->code)->first();

        // Mã không tồn tại, đơn chỉ có đồ ăn hoặc vé chưa thanh toán: báo lỗi thay vì lỗi máy chủ
        if (!$ticket || !$ticket->schedule || !$ticket->hasPaid) {
            return response()->json([
                'theater' => '', 'room' => '', 'movie' => '', 'seats' => '', 'date' => '', 'startTime' => '',
                'message' => !$ticket || !$ticket->schedule ? 'không tìm thấy vé' : 'vé chưa được thanh toán',
                'check' => false,
            ]);
        }

        if ($ticket) {
            if ($ticket->schedule->date == date('Y-m-d')) {
                if (strtotime('- 10 minutes', strtotime($ticket->schedule->startTime)) > strtotime(date('H:i:s'))) {
                    $message = 'Chưa đến giờ chiếu phim';
                    $check = false;
                    $ticket->status = true;
                } else {
                    if (strtotime($ticket->schedule->endTime) > strtotime(date('H:i:s'))) {
                        if ($ticket->status) {
                            $ticket->status = false;
                            $check = true;
                            $message = 'vé hợp lệ';
                        } else {
                            $message = 'vé không hợp lệ';
                            $check = false;
                        }
                    } else {
                        $message = 'suất chiếu đã kết thúc';
                        $check = false;
                        $ticket->status = false;
                    }
                }

            } else if ($ticket->schedule->date > date('Y-m-d')) {
                    $message = 'Chưa đến ngày chiếu phim';
                    $check = false;
                    $ticket->status = true;
            } else  {
                $message = 'suất chiếu đã kết thúc';
                $check = false;
                $ticket->status = false;
            }

            $ticket->save();

            foreach ($ticket->ticketSeats as $seats) {
                $seatsList .= $seats->row.$seats->col.',';
            }
        } else {
            $message = 'không tìm thấy vé';
            $check = false;
        }

        return response()->json([
            'theater' => $ticket->schedule->room->theater->name,
            'room' => $ticket->schedule->room->name,
            'movie' => $ticket->schedule->movie->name,
            'seats' => $seatsList,
            'date' => $ticket->schedule->date,
            'startTime' => $ticket->schedule->startTime,
            'message' => $message,
            'check' => $check,
        ]);
    }

    public function buyCombo(Request $request) {
        $combos = Combo::where('status', 1)->get();
        $foods = Food::where('status', 1)->get();
        return view('admin.buyCombo.buyCombo', [
            'combos' => $combos,
            'foods' => $foods,
        ]);
    }

    public function createTicketCombo(Request $request) {
        $ticket = new Ticket([
            'schedule_id' => null,
            'user_id' => null,
            'holdState' => false,
            'status' => false,
            'hasPaid' => false,
            'code' => rand(10000000, 9999999999)
        ]);
        $ticket->save();
        if ($request->ticketCombos) {
            foreach ($request->ticketCombos as $ticketCombo) {
                $combo = Combo::find($ticketCombo[0]);
                $details = '';
                foreach ($combo->foods as $food) {
                    $details .= $food->pivot->quantity . ' ' . $food->name . ' + ';
                }
                $details = substr($details, 0, -3);
                $newTkCb = new TicketCombo([
                    'comboName' => $combo->name,
                    'comboPrice' => $combo->price,
                    'comboDetails' => $details,
                    'quantity' => $ticketCombo[1],
                    'ticket_id' => $ticket->id
                ]);

                $newTkCb->save();
                unset($newTkCb);
            }
        }
        if ($request->ticketFoods) {
            foreach ($request->ticketFoods as $ticketFood) {
                $food = Food::find($ticketFood[0]);
                $newTkF = new TicketFood([
                    'foodName' => $food->name,
                    'foodPrice' => $food->price,
                    'quantity' => $ticketFood[1],
                    'ticket_id' => $ticket->id,
                ]);

                $newTkF->save();
                unset($newTkF);
            }
        }

        return response()->json(['ticket_id' => $ticket->id]);
    }

    public function scanCombo() {
        return view('admin.scanCombo.scanCombo');
    }

    public function handleScanCombo(Request $request) {
        $ticket = Ticket::where('code', $request->code)->first();
        $message = 'vé hợp lệ';
        $check = true;

        if (!$ticket) {
            $message = 'không tìm thấy vé';
            $check = false;
        } else {
            if ($ticket->receivedCombo) {
                $message = 'Đã lấy đồ ăn';
                $check = false;
            } else {
                $ticket->receivedCombo = true;
            }
        }
        $comboHtml = '<ul>';
        foreach ($ticket->ticketCombos as $combo) {
            $comboHtml .= '<li>'.$combo->quantity.' X '.$combo->comboName.'<br>('.$combo->comboDetails.')</li>';
        }

        foreach ($ticket->ticketFoods as $food) {
            $comboHtml .= '<li>'.$food->quantity.' X '.$food->foodName.'</li>';
        }
        $comboHtml .= '</ul>';

        $ticket->save();

        return response()->json([
            'comboHtml' => $comboHtml,
            'message' => $message,
            'check' => $check,
        ]);
    }

    public function ticketComboPayment(Request $request) {
        $ticket = Ticket::find($request->ticket_id);
        $ticket->holdState = false;
        $ticket->totalPrice = $request->totalPrice;
        $ticket->save();

        return response('', 200);
    }
}
