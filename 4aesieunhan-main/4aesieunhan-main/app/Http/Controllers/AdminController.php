<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Theater;
use App\Models\Ticket;
use App\Models\TicketSeat;
use App\Models\User;
use App\Models\Movie;
use App\Models\Info;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{

    public function __construct()
    {
        $info = Info::find(1);
        view()->share('info', $info);
    }

    // Chỉ tính vé đã thanh toán
    private function paidTickets($from, $to)
    {
        return Ticket::with('ticketSeats', 'schedule.room')
            ->whereBetween('created_at', [$from, $to])
            ->where('hasPaid', 1)
            ->orderBy('created_at', 'ASC')
            ->get();
    }

    // Gắn tổng tiền + số ghế đã thanh toán vào từng phim / rạp
    private function attachMovieTotals($movies)
    {
        foreach ($movies as $movie) {
            $tickets = $movie->schedules->flatMap->Ticket->where('hasPaid', 1);
            $movie->setAttribute('totalPrice', $tickets->sum('totalPrice'));
            $movie->setAttribute('ticketseats', $tickets->sum(fn ($t) => $t->ticketseats->count()));
        }
        return $movies->sortByDesc('totalPrice');
    }

    private function attachTheaterTotals($theaters)
    {
        foreach ($theaters as $theater) {
            $tickets = $theater->rooms->flatMap->schedules->flatMap->Ticket->where('hasPaid', 1);
            $theater->setAttribute('totalPrice', $tickets->sum('totalPrice'));
            $theater->setAttribute('ticketseats', $tickets->sum(fn ($t) => $t->ticketseats->count()));
        }
        return $theaters->sortByDesc('totalPrice');
    }

    // Gom vé theo ngày (d-m-Y) hoặc tháng (m-Y) cho biểu đồ
    private function chartData($tickets, $format)
    {
        return $tickets
            ->groupBy(fn ($t) => date($format, strtotime($t->created_at)))
            ->map(fn ($group, $date) => [
                'date' => $date,
                'total' => $group->sum('totalPrice'),
                'seat_count' => $group->sum(fn ($t) => $t->ticketSeats->count()),
            ])
            ->values();
    }

    public function home(Request $request)
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh')->endOfDay();
        $year = Carbon::now('Asia/Ho_Chi_Minh')->subDays(365)->startOfYear()->toDateString();
        $start_of_month = Carbon::now('Asia/Ho_Chi_Minh')->startOfMonth();
        $total_year = $this->paidTickets($year, $now);
        $ticket = Ticket::whereDate('created_at', Carbon::today('Asia/Ho_Chi_Minh'))->where('hasPaid', 1)->get();
        $ticket_seat = $total_year->sum(fn ($t) => $t->ticketSeats->count());
        $user = User::role('user')->get();

        $theaters = $this->attachTheaterTotals(Theater::orderBy('id', 'ASC')->get());
        $movies = $this->attachMovieTotals(Movie::all());

        return view('admin.home.list', [
            'user' => $user,
            'ticket' => $ticket,
            'sum' => $total_year->sum('totalPrice'),
            'sum_today' => $ticket->sum('totalPrice'),
            'now' => $now,
            'start_of_month' => $start_of_month,
            'ticket_seat' => $ticket_seat,
            'year' => $year,
            'theaters' => $theaters,
            'movies' => $movies
        ]);
    }

    public function filter_by_date(Request $request)
    {
        if (!$request->from_date || !$request->to_date) {
            return response()->json(['error' => 'Vui lòng chọn khoảng ngày']);
        }
        $start_time = Carbon::createFromFormat('Y-m-d', trim($request->from_date))->startOfDay();
        $end_time = Carbon::createFromFormat('Y-m-d', trim($request->to_date))->endOfDay();

        return response()->json([
            'success' => 'Thành công',
            'chart_data' => $this->chartData($this->paidTickets($start_time, $end_time), 'd-m-Y'),
        ]);
    }

    public function statistical_filter(Request $request)
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh')->endOfDay();
        $format = 'd-m-Y';

        switch ($request['statistical_value']) {
            case 'week':
                $from = Carbon::now('Asia/Ho_Chi_Minh')->subDays(7)->startOfDay();
                break;
            case 'this_month':
                $from = Carbon::now('Asia/Ho_Chi_Minh')->startOfMonth();
                break;
            case 'last_month':
                $from = Carbon::now('Asia/Ho_Chi_Minh')->subMonthNoOverflow()->startOfMonth();
                $now = Carbon::now('Asia/Ho_Chi_Minh')->subMonthNoOverflow()->endOfMonth();
                break;
            case 'year':
                $from = Carbon::now('Asia/Ho_Chi_Minh')->subDays(365)->startOfYear();
                $format = 'm-Y';
                break;
            default:
                return response()->json(['error' => 'Lựa chọn không hợp lệ']);
        }

        return response()->json([
            'success' => 'Thành công',
            'chart_data' => $this->chartData($this->paidTickets($from, $now), $format),
        ]);
    }

    public function statistical_sortby(Request $request)
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh')->endOfDay();
        $year = Carbon::now('Asia/Ho_Chi_Minh')->subDays(365)->startOfYear();
        $tickets = $this->paidTickets($year, $now);

        if ($request['statistical_value'] == 'ticket') {
            return response()->json([
                'success' => 'Thành công',
                'chart_data' => $this->chartData($tickets, 'm-Y'),
            ]);
        }

        if ($request['statistical_value'] == 'theater') {
            $theaters = Theater::orderBy('id', 'ASC')->get();
            $chart_data = $tickets
                ->groupBy(fn ($t) => date('m-Y', strtotime($t->created_at)))
                ->map(function ($group, $date) use ($theaters) {
                    $data = ['date' => $date];
                    foreach ($theaters as $theater) {
                        $data[(string) $theater->id] = $group
                            ->filter(fn ($t) => $t->schedule && $t->schedule->room && $t->schedule->room->theater_id == $theater->id)
                            ->sum('totalPrice');
                    }
                    return $data;
                })
                ->values();

            return response()->json([
                'success' => 'Thành công',
                'chart_data' => $chart_data,
                'theaters' => $theaters->map(fn ($t) => ['id' => (string) $t->id, 'name' => $t->name])->values(),
            ]);
        }

        return response()->json(['error' => 'Lựa chọn không hợp lệ']);
    }
    //User
    public function user()
    {
        $users = User::orderBy('id', 'DESC')->with('roles', 'permissions')->Paginate(50);
        return view('admin.user_account.list', ['users' => $users]);
    }
    public function searchUser(Request $request)
    {
        $output = '';
        if ($request->search == null) {
            $users = User::orderBy('id', 'DESC')->Paginate(50);
        } else {
            $users = User::where('code', 'LIKE', '%' . $request->search . '%')->orWhere('email', 'LIKE', '%' . $request->search . '%')->get();
        }
        if ($users) {
            foreach ($users as $value) {
                foreach ($value['roles'] as $role) {
                    if ($role['name'] == 'user') {
                        $output .= '<tr>
                        <td class="align-middle text-center">
                            <h6 class="mb-0 text-sm ">' . $value['code'] . '</h6>
                        </td>

                        <td class="align-middle text-center">
                            <h6 class="mb-0 text-sm ">' . $value['fullName'] . '</h6>
                        </td>

                        <td class="align-middle text-center">
                            <span class="text-secondary font-weight-bold">' . $value['email'] . '</span>
                        </td>

                        <td class="align-middle text-center">
                            <span class="text-secondary font-weight-bold">' . $value['phone'] . '</span>
                        </td>

                         <td class="align-middle text-center">
                            <button href="#barcode" class="btn btn-link text-danger "
                                    data-bs-toggle="modal"
                                    data-bs-target="#barcode' . $value['id'] . '"><i style="color:grey" class="fa-sharp fa-regular fa-eye"></i>
                            </button>
                        </td>
                        <td id="status' . $value['id'] . '" class="align-middle text-center text-sm ">';
                        if ($value['status'] == 1) {
                            $output .= '
                             <a href="javascript:void(0)" class="btn_active"  onclick="changestatus(' . $value['id'] . ',0)">
                                    <span class="badge badge-sm bg-gradient-success">Online</span>
                                </a>';
                        } else {
                            $output .= '<a href="javascript:void(0)" class="btn_active"  onclick="changestatus(' . $value['id'] . ',1)">
                                    <span class="badge badge-sm bg-gradient-secondary">Offline</span>
                                </a>';
                        }
                        $output .= '
                        </td>
                         <td class="align-middle text-center">
                            <span class="text-secondary font-weight-bold">' . number_format($value['point'], 0, ",", ".") . ' Point</span>
                        </td>
                        <td class="align-middle text-center">
                            <span
                                class="text-secondary font-weight-bold">' . date("d-m-Y H:m:s", strtotime($value['created_at'])) . '</span>
                        </td>
                        <td class="align-middle text-center">
                            <span
                                class="text-secondary font-weight-bold">' . date("d-m-Y H:m:s", strtotime($value['updated_at'])) . '</span>
                        </td>
                        </tr>';
                    }
                }
            }
        }

        return Response($output);
    }
    //Staff
    public function staff()
    {
        $staff = User::orderBy('id', 'DESC')->with('roles', 'permissions')->Paginate(20);
        $permission = Permission::orderBy('id', 'asc')->get();
        $theaters = Theater::all();

        return view('admin.staff_account.list', [
            'staff' => $staff,
            'permission' => $permission,
            'theaters' => $theaters
        ]);
    }

    public function postCreate(Request $request)
    {
        $request->validate([
            'fullName' => 'required|min:1',
            'email' => 'required|unique:users',
            'phone' => 'required|unique:users',
            'password' => 'required',
        ], [
            'fullName.required' => 'fullName is required',
            'email.required' => 'Email is required',
            'email.unique' => 'Email already exists',
            'password.required' => 'Password is required',
            'phone.required' => 'Phone is required',
            'phone.unique' => 'Phone already exists'
        ]);
        $request['password'] = bcrypt($request['password']);
        $staff = new User([
            'fullName' => $request['fullName'],
            'password' => $request['password'],
            'email' => $request['email'],
            'phone' => $request['phone'],
            'code' => rand(10000000000, 9999999999999999),
            'point' => 0,
            'email_verified' => true,
            'remember_token' => Str::random(20),
        ]);
        //        dd($staff);

        $staff->theater_id = $request->theater_id;

        $staff->save();
        $staff->syncRoles('staff');
        return redirect('/admin/staff')->with('success', 'Tạo tài khoản thành công!');
    }

    public function postPermission(Request $request, $id)
    {
        $data = $request->all();
        $user = User::find($id);
        if ($user->hasRole('admin')) {
            return redirect('admin/staff')->with('warning', 'Không thể thay đổi quyền của admin!');
        } else {
            if (array_key_exists('permission', $data)) {
                $user->syncPermissions($data['permission']);
            } else {
                return redirect('admin/staff')->with('warning', 'Vui lòng chọn ít nhất 1 quyền!');
            }
        }


        return redirect('admin/staff')->with('success', 'Cập nhật quyền thành công!');
    }

    public function delete($id)
    {
        $user = User::find($id);
        if ($user['status'] == 0) {
            if ($user->hasRole('admin')) {
                return response()->json(['error' => "Không thể xóa tài khoản admin!"]);
            } else {
                User::destroy($id);
                return response()->json(['success' => 'Xóa thành công!']);
            }
        } else {
            return response()->json(['error' => "Vui lòng chuyển trạng thái sang offline!"]);
        }
    }

    //Profile
    public function profile()
    {
        if (Auth::check()) {
            $user = Auth()->user();
        } else {
            return redirect('admin/sign_in');
        }
        return view('admin.profile', ['user' => $user])->with('roles', 'permissions');
    }
    public function Postprofile(Request $request)
    {
        $user = User::find(Auth::user()->id);
        if ($request['checkPassword'] == 'on') {
            $request->validate([
                'password' => 'required',
                'repassword' => 'required|same:password'
            ], [
                'password.required' => 'Vui lòng nhập mật khẩu mới',
                'repassword.required' => 'Vui lòng nhập lại mật khẩu',
                'repassword.same' => "Mật khẩu nhập lại không đúng"
            ]);
            $request['password'] = bcrypt($request['password']);
        }
        $user->update($request->all());
        return redirect('admin/sign_out')->with('success', 'Update Successfully');
    }
    //Sign_in
    public function sign_in()
    {
        return view('admin.sign_in');
    }

    public function Post_sign_in(Request $request)
    {
        $request->validate(
            [
                'email' => 'required',
                'password' => 'required'
            ],
            [
                'email.required' => 'Please enter your email!',
                'password.required' => 'Please enter your password!'
            ]
        );
        if (Auth::attempt(['email' => $request['email'], 'password' => $request['password']])) {
            return redirect('admin');
        } else {
            return redirect('admin/sign_in')->with('warning', "Đăng nhập thành công!");
        }
    }

    public function sign_out()
    {
        Auth::logout();
        return redirect('admin/sign_in');
    }

    public function status(Request $request)
    {
        $user = User::find($request->user_id);
        if ($user->hasRole('admin')) {
            return response()->json(['error' => "Không thể thay đổi trạng thái của admin!"]);
        } else {
            $user['status'] = $request->active;
            $user->save();
        }
    }
    public function feedback()
    {
        $feed = Feedback::orderBy('id', 'DESC')->Paginate(15);
        return view('admin.feedback.list', ['feed' => $feed]);
    }
    public function search_movie(Request $request)
    {
        $output = '';
        if ($request->search_movie == null) {
            $movies = Movie::all();
            $movies = $this->attachMovieTotals($movies);
        } else {
            $movies = Movie::where('name', 'LIKE', '%' . $request->search_movie . '%')->get();
            $movies = $this->attachMovieTotals($movies);
        }
        if ($movies) {
            foreach ($movies as $movie) {
                $output .= '<tr>
                <td class="w-30">
                    <div class="d-flex px-2 py-1 align-items-center">
                        <div class="ms-4">
                            <p class="text-xs font-weight-bold mb-0">Phim</p>
                            <h6 class="text-sm mb-0">' .
                    $movie['name'] . '
                            </h6>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="text-center">
                        <p class="text-xs font-weight-bold mb-0">Vé</p>
                        <h6 class="text-sm mb-0">' .
                    $movie['ticketseats'] . '
                        </h6>
                    </div>
                </td>
                <td>
                    <div class="text-center">
                        <p class="text-xs font-weight-bold mb-0">Tổng tiền</p>
                        <h6 class="text-sm mb-0">' .
                    number_format($movie['totalPrice'], 0, ",", ".") . ' đ
                        </h6>
                    </div>
                </td>
            </tr>';
            }
        }
        return response()->json(["output" => $output]);
    }
    public function search_theater(Request $request)
    {
        $output = '';
        if ($request->search_theater == null) {
            $theaters = Theater::all();
            $theaters = $this->attachTheaterTotals($theaters);
        } else {
            $theaters = Theater::where('name', 'LIKE', '%' . $request->search_theater . '%')->get();
            $theaters = $this->attachTheaterTotals($theaters);
        }
        if ($theaters) {
            foreach ($theaters as $theater) {
                $output .= '<tr>
                <td class="w-30">
                    <div class="d-flex px-2 py-1 align-items-center">
                        <div class="ms-4">
                            <p class="text-xs font-weight-bold mb-0">Rạp</p>
                            <h6 class="text-sm mb-0">' .
                    $theater['name'] . '
                            </h6>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="text-center">
                        <p class="text-xs font-weight-bold mb-0">Vé bán ra</p>
                        <h6 class="text-sm mb-0">' .
                    $theater['ticketseats'] . '
                        </h6>
                    </div>
                </td>
                <td>
                    <div class="text-center">
                        <p class="text-xs font-weight-bold mb-0">Tổng tiền</p>
                        <h6 class="text-sm mb-0">' .
                    number_format($theater['totalPrice'], 0, ",", ".") . ' đ
                        </h6>
                    </div>
                </td>
            </tr>';
            }
        }
        return response()->json(["output" => $output]);
    }
}
