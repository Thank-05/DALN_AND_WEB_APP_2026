<?php

namespace Tests\Feature;

use App\Models\Discount;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\TicketCombo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Kiểm thử luồng đặt vé: giá do máy chủ tính, chống đặt trùng ghế, quyền sở hữu vé.
 * Cần cơ sở dữ liệu cinema_test đã chạy: php artisan migrate --seed --env=testing
 */
class TicketFlowTest extends TestCase
{
    use DatabaseTransactions;

    private Schedule $schedule;
    private Seat $seat;

    protected function setUp(): void
    {
        parent::setUp();

        // Phòng 2D (phụ phí 0 đ), suất 10:00 thứ Hai => giá gốc 75.000 đ
        $room = Room::where('roomType_id', 1)->firstOrFail();
        $this->schedule = Schedule::create([
            'room_id' => $room->id,
            'movie_id' => 1,
            'audio_id' => 1,
            'subtitle_id' => 1,
            'date' => Carbon::parse('next monday')->format('Y-m-d'),
            'startTime' => '10:00:00',
            'endTime' => '12:00:00',
            'early' => 0,
            'status' => 1,
        ]);
        $this->seat = Seat::where('room_id', $room->id)->where('status', 1)->firstOrFail();
    }

    private function expectedSeatPrice(): int
    {
        return 75000 + $this->seat->seatType->surcharge;
    }

    private function holdSeat(User $user)
    {
        return $this->actingAs($user)->postJson('/tickets/create', [
            'schedule' => $this->schedule->id,
            // Giá 1 đ do trình duyệt gửi lên phải bị bỏ qua
            'ticketSeats' => [[$this->seat->row, $this->seat->col, 1]],
        ]);
    }

    public function test_guest_cannot_create_ticket()
    {
        $response = $this->postJson('/tickets/create', [
            'schedule' => $this->schedule->id,
            'ticketSeats' => [[$this->seat->row, $this->seat->col, 1]],
        ]);

        // Chưa đăng nhập bị chặn (ứng dụng chuyển hướng về trang chủ)
        $this->assertFalse($response->isSuccessful());

        $this->assertSame(0, Ticket::where('schedule_id', $this->schedule->id)->count());
    }

    public function test_seat_price_is_calculated_on_server()
    {
        $ticketId = $this->holdSeat(User::find(2))->assertOk()->json('ticket_id');

        $this->assertSame($this->expectedSeatPrice(), (int) Ticket::find($ticketId)->ticketSeats()->first()->price);
    }

    public function test_same_seat_cannot_be_held_twice()
    {
        $this->holdSeat(User::find(2))->assertOk();
        $this->holdSeat(User::find(3))->assertStatus(401);
    }

    public function test_total_price_is_calculated_on_server()
    {
        $ticketId = $this->holdSeat(User::find(2))->json('ticket_id');
        TicketCombo::create(['comboName' => 'Combo test', 'comboPrice' => 50000, 'comboDetails' => '1 bắp', 'quantity' => 2, 'ticket_id' => $ticketId]);

        $this->postJson('/payment', ['ticket_id' => $ticketId, 'totalPrice' => 1])
            ->assertOk()
            ->assertJson(['totalPrice' => $this->expectedSeatPrice() + 100000]);

        $this->assertSame($this->expectedSeatPrice() + 100000, (int) Ticket::find($ticketId)->totalPrice);
    }

    public function test_discount_is_applied_on_server()
    {
        $discount = Discount::create(['name' => 'Test', 'code' => 'TEST20', 'percent' => 20, 'quantity' => 5, 'status' => 1]);
        $ticketId = $this->holdSeat(User::find(2))->json('ticket_id');

        $this->postJson('/payment', ['ticket_id' => $ticketId, 'hasDiscount' => $discount->id])
            ->assertOk()
            ->assertJson(['totalPrice' => (int) round($this->expectedSeatPrice() * 0.8)]);
    }

    public function test_points_above_balance_are_rejected()
    {
        $user = User::find(2);
        $user->update(['point' => 10000]);
        $ticketId = $this->holdSeat($user)->json('ticket_id');

        $this->postJson('/payment', ['ticket_id' => $ticketId, 'point' => 20000])->assertStatus(422);
    }

    public function test_other_user_cannot_delete_ticket()
    {
        $ticketId = $this->holdSeat(User::find(2))->json('ticket_id');

        $this->actingAs(User::find(3))->deleteJson('/tickets/delete', ['ticket_id' => $ticketId])->assertStatus(403);
        $this->assertNotNull(Ticket::find($ticketId));
    }

    public function test_other_user_cannot_refund_ticket()
    {
        $ticketId = $this->holdSeat(User::find(2))->json('ticket_id');
        Ticket::where('id', $ticketId)->update(['hasPaid' => 1, 'holdState' => 0, 'totalPrice' => 75000]);

        $this->actingAs(User::find(3))->postJson('/refund-ticket', ['ticket_id' => $ticketId])
            ->assertJsonStructure(['error']);
        $this->assertNotNull(Ticket::find($ticketId));
    }

    public function test_counter_cash_payment_uses_server_total_and_adds_points()
    {
        $staff = User::find(1);
        $member = User::find(2);
        $member->update(['point' => 0]);
        $ticketId = $this->holdSeat($staff)->json('ticket_id');

        $this->actingAs($staff)->postJson('/admin/buyTicket/ticketPayment', [
            'ticket_id' => $ticketId, 'totalPrice' => 1, 'userCode' => $member->code,
        ])->assertOk()->assertJson(['totalPrice' => $this->expectedSeatPrice()]);

        $this->post('/admin/buyTicket/handleResult', [
            'vnp_BankCode' => 'MONEY', 'ticket_id' => $ticketId, 'type' => 'ticket',
        ])->assertRedirect('admin/buyTicket');

        $this->assertTrue((bool) Ticket::find($ticketId)->hasPaid);
        // Thành viên dưới mốc 4.000.000 đ được cộng 5% giá trị vé
        $this->assertEquals($this->expectedSeatPrice() * 5 / 100, $member->fresh()->point);
    }

    public function test_counter_vnpay_result_rejects_bad_signature()
    {
        config(['services.vnpay.hash_secret' => 'secret']);
        $ticketId = $this->holdSeat(User::find(1))->json('ticket_id');
        $code = Ticket::find($ticketId)->code;

        $this->get('/admin/buyTicket/vnpayResult?type=ticket&vnp_TxnRef=' . $code . '&vnp_ResponseCode=00&vnp_SecureHash=abc')
            ->assertRedirect('admin/buyTicket');
        $this->assertFalse((bool) Ticket::find($ticketId)->hasPaid);
    }

    public function test_scanning_unknown_or_unpaid_ticket_returns_message()
    {
        $staff = User::find(1);
        $this->actingAs($staff)->postJson('/admin/scanTicket/handle', ['code' => '0'])
            ->assertOk()->assertJson(['check' => false, 'message' => 'không tìm thấy vé']);

        $ticketId = $this->holdSeat($staff)->json('ticket_id');
        $this->postJson('/admin/scanTicket/handle', ['code' => Ticket::find($ticketId)->code])
            ->assertOk()->assertJson(['check' => false, 'message' => 'vé chưa được thanh toán']);
    }

    public function test_admin_wrong_password_shows_error()
    {
        $this->post('/admin/sign_in', ['email' => 'admin@gmail.com', 'password' => 'sai-mat-khau'])
            ->assertRedirect('admin/sign_in')
            ->assertSessionHas('warning', 'Email hoặc mật khẩu không đúng!');
    }
}
