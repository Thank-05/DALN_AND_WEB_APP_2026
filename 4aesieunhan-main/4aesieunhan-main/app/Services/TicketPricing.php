<?php

namespace App\Services;

use App\Models\Price;
use App\Models\Schedule;
use App\Models\Seat;
use App\Models\Ticket;

/**
 * Tính giá vé phía máy chủ, không tin vào số tiền trình duyệt gửi lên.
 * Giá ghế = giá gốc (thành viên/vé online, theo thứ và khung giờ) + phụ phí loại phòng + phụ phí loại ghế.
 */
class TicketPricing
{
    /** Điểm tối thiểu cho một lần sử dụng */
    public const MIN_POINT = 20000;

    /** Điểm dùng được tối đa 90% giá trị hóa đơn */
    public const MAX_POINT_RATE = 0.9;

    public static function basePrice(Schedule $schedule): int
    {
        $after = strtotime($schedule->startTime) < strtotime('17:00') ? '08:00' : '17:00';

        $price = Price::where('generation', 'vtt')
            ->where('day', 'like', '%' . date('l', strtotime($schedule->date)) . '%')
            ->where('after', $after)
            ->value('price');

        return (int) $price;
    }

    public static function seatPrice(Schedule $schedule, Seat $seat): int
    {
        return self::basePrice($schedule)
            + (int) $schedule->room->roomType->surcharge
            + (int) $seat->seatType->surcharge;
    }

    /** Tổng tiền ghế + combo + món lẻ đã lưu trong vé (giá tại thời điểm mua) */
    public static function grossTotal(Ticket $ticket): int
    {
        $seats  = $ticket->ticketSeats()->sum('price');
        $combos = $ticket->ticketCombos()->get()->sum(fn ($c) => $c->comboPrice * $c->quantity);
        $foods  = $ticket->ticketFoods()->get()->sum(fn ($f) => $f->foodPrice * $f->quantity);

        return (int) round($seats + $combos + $foods);
    }

    /**
     * Kiểm tra số điểm muốn dùng. Trả về số điểm hợp lệ hoặc null nếu không hợp lệ.
     */
    public static function validPoint(int $point, int $userPoint, int $amount): ?int
    {
        if ($point <= 0) {
            return 0;
        }
        if ($point < self::MIN_POINT || $point > $userPoint || $point > $amount * self::MAX_POINT_RATE) {
            return null;
        }

        return $point;
    }
}
