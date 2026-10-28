<?php

/*
|--------------------------------------------------------------------------
| MIGRATION CẦN CHẠY TRƯỚC (tạo file bằng lệnh bên dưới rồi dán nội dung vào)
|--------------------------------------------------------------------------
| php artisan make:migration add_verify_and_reset_token_to_users_table --table=users
|
| public function up()
| {
|     Schema::table('users', function (Blueprint $table) {
|         $table->string('verify_token', 64)->nullable();
|         $table->timestamp('verify_token_expires_at')->nullable();
|         $table->string('reset_token', 64)->nullable();
|         $table->timestamp('reset_token_expires_at')->nullable();
|     });
| }
|
| public function down()
| {
|     Schema::table('users', function (Blueprint $table) {
|         $table->dropColumn(['verify_token', 'verify_token_expires_at', 'reset_token', 'reset_token_expires_at']);
|     });
| }
|
| Sau đó chạy: php artisan migrate
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\Info;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const PASSWORD_REGEX = '/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9]).{6,}$/';
    private const PASSWORD_REGEX_MSG = 'Mật khẩu phải có ít nhất 1 chữ hoa, 1 chữ thường, 1 số và độ dài tối thiểu 6 kí tự';

    public function __construct()
    {
        $info = Info::find(1);
        view()->share('info', $info);
    }

    /* ==================== HÀM HỖ TRỢ ==================== */

    private function normalizeEmail(?string $email): ?string
    {
        $email = $email !== null ? strtolower(trim($email)) : null;
        return $email === '' ? null : $email;
    }

    private function sendVerifyMail(User $user): void
    {
        $token = Str::random(64);
        $user->verify_token = $token;
        $user->verify_token_expires_at = now()->addHours(24);
        $user->save();

        $to_email = $user->email;
        $link_verify = url('/verify-email') . '?' . http_build_query([
            'email' => $to_email,
            'token' => $token,
        ]);

        Mail::send('web.pages.verify_account_mail', [
            'to_email'    => $to_email,
            'link_verify' => $link_verify,
        ], function ($message) use ($to_email) {
            $message->subject('Kích hoạt tài khoản: ' . $to_email);
            $message->to($to_email);
        });
    }

    /* ==================== ĐĂNG NHẬP ==================== */

    public function signIn(Request $request)
    {
        $request->validate(
            [
                'username' => 'required',
                'password' => 'required',
            ],
            [
                'username.required' => 'Vui lòng nhập email hoặc số điện thoại!',
                'password.required' => 'Vui lòng nhập mật khẩu!',
            ]
        );

        $username = trim($request->input('username'));
        $remember = $request->has('rememberme');

        // Xác định đăng nhập bằng email hay số điện thoại
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $credentials = ['email' => $this->normalizeEmail($username)];
        } else {
            $credentials = ['phone' => format_phone_number($username)];
        }
        $credentials['password'] = $request->input('password');

        if (!Auth::attempt($credentials, $remember)) {
            return redirect('/')->with('warning', 'Sai tài khoản hoặc mật khẩu');
        }

        if (Auth::user()->hasRole('admin')) {
            Auth::logout();
            return redirect('/')->with('warning', 'Tài khoản không hợp lệ !');
        }

        $request->session()->regenerate();

        // Chỉ ghi nhớ tên đăng nhập để điền sẵn form, KHÔNG lưu mật khẩu
        if ($remember) {
            session(['username_web' => $username]);
        } else {
            session()->forget('username_web');
        }
        session()->forget('password_web');

        $redirectTo = $request->input('url', '/');
        return redirect($redirectTo)->with('success', 'Chào mừng bạn ' . Auth::user()->fullName . ' !');
    }

    /* ==================== ĐĂNG KÝ ==================== */

    public function signUp(Request $request)
    {
        $request->merge([
            'email' => $this->normalizeEmail($request->input('email')),
            'phone' => $request->filled('phone') ? trim($request->input('phone')) : null,
        ]);

        $request->validate([
            'fullName'   => 'required|min:1',
            'email'      => 'nullable|required_without:phone|email|max:255|unique:users,email',
            'phone'      => 'nullable|required_without:email|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|max:12|unique:users,phone',
            'password'   => ['required', 'regex:' . self::PASSWORD_REGEX],
            'repassword' => 'required|same:password',
        ], [
            'fullName.required'      => 'Vui lòng nhập họ tên',
            'email.required_without' => 'Vui lòng nhập mail hoặc số điện thoại',
            'email.email'            => 'Email không đúng định dạng',
            'email.unique'           => 'Email đã tồn tại',
            'phone.min'              => 'Vui lòng nhập tối thiểu 10 số',
            'phone.max'              => 'Chỉ được nhập tối đa 12 số',
            'phone.regex'            => 'Số điện thoại không hợp lệ',
            'phone.required_without' => 'Vui lòng nhập mail hoặc số điện thoại',
            'phone.unique'           => 'Số điện thoại đã tồn tại',
            'password.required'      => 'Vui lòng nhập mật khẩu',
            'password.regex'         => self::PASSWORD_REGEX_MSG,
            'repassword.required'    => 'Vui lòng nhập lại mật khẩu',
            'repassword.same'        => 'Mật khẩu nhập lại không trùng khớp',
        ]);

        $user = new User([
            'fullName' => $request->input('fullName'),
            'password' => bcrypt($request->input('password')),
            'email'    => $request->input('email'),
            'phone'    => $request->input('phone') ? format_phone_number($request->input('phone')) : null,
            'code'     => rand(1000000000000000, 9999999999999999),
            'point'    => 0,
        ]);
        $user->save();
        $user->syncRoles('user');

        if ($user->email) {
            try {
                $this->sendVerifyMail($user);
            } catch (\Throwable $e) {
                report($e);
                return redirect('/')->with('warning', 'Đăng ký thành công nhưng chưa gửi được email kích hoạt, vui lòng thử gửi lại sau!');
            }
            return redirect('/')->with('success', 'Đăng ký thành công, vui lòng kiểm tra email để kích hoạt tài khoản !');
        }

        return redirect('/')->with('success', 'Đăng ký thành công tài khoản !');
    }

    /* ==================== GỬI LẠI MAIL KÍCH HOẠT ==================== */
    // Route: POST /resend-verify (throttle 3 lần / 10 phút)

    public function resend_verify(Request $request)
    {
        $email = $this->normalizeEmail($request->input('email'));
        $user = $email ? User::where('email', $email)->first() : null;

        if (!$user) {
            return redirect()->back()->with('warning', 'Email chưa được đăng ký trong hệ thống !');
        }
        if ($user->email_verified == 1) {
            return redirect()->back()->with('success', 'Email này đã được kích hoạt rồi !');
        }

        try {
            $this->sendVerifyMail($user);
        } catch (\Throwable $e) {
            report($e);
            return redirect()->back()->with('warning', 'Không gửi được email, vui lòng thử lại sau!');
        }

        return redirect()->back()->with('success', 'Đã gửi lại email kích hoạt, vui lòng kiểm tra hộp thư !');
    }

    /* ==================== KÍCH HOẠT EMAIL ==================== */

    public function verify_email(Request $request)
    {
        $email = $this->normalizeEmail($request->query('email'));
        $token = (string) $request->query('token', '');

        if (!$email || $token === '') {
            return redirect('/')->with('warning', 'Đường dẫn không hợp lệ');
        }

        $user = User::where('email', $email)
            ->whereNotNull('verify_token')
            ->where('verify_token', $token)
            ->first();

        if (!$user || !$user->verify_token_expires_at || now()->greaterThan($user->verify_token_expires_at)) {
            return redirect('/')->with('warning', 'Đường dẫn không hợp lệ hoặc đã hết hạn, vui lòng yêu cầu gửi lại email kích hoạt');
        }

        $user->email_verified = 1;
        $user->verify_token = null;
        $user->verify_token_expires_at = null;
        $user->save();

        if (Auth::check() && Auth::id() == $user->id) {
            return redirect('/profile')->with('success', 'Kích hoạt email cho tài khoản ' . $user->fullName . ' thành công !');
        }

        return redirect('/')->with('success', 'Kích hoạt email thành công');
    }

    /* ==================== ĐĂNG XUẤT ==================== */

    public function signOut(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('success', 'Đăng xuất thành công');
    }

    /* ==================== ĐỔI MẬT KHẨU ==================== */

    public function changePassword(Request $request)
    {
        $request->validate([
            'oldpassword' => 'required',
            'password'    => ['required', 'regex:' . self::PASSWORD_REGEX],
            'repassword'  => 'required|same:password',
        ], [
            'oldpassword.required' => 'Vui lòng nhập mật khẩu cũ',
            'password.required'    => 'Vui lòng nhập mật khẩu mới',
            'password.regex'       => self::PASSWORD_REGEX_MSG,
            'repassword.required'  => 'Vui lòng nhập lại mật khẩu',
            'repassword.same'      => 'Mật khẩu nhập lại không trùng khớp !',
        ]);

        $user = User::find(Auth::id());

        if (!Hash::check($request->input('oldpassword'), $user->password)) {
            return redirect('/profile')->with('warning', 'Mật khẩu cũ không đúng !');
        }

        if ($request->input('password') === $request->input('oldpassword')) {
            return redirect('/profile')->with('danger', 'Mật khẩu mới trùng với mật khẩu cũ !');
        }

        $user->password = bcrypt($request->input('password'));
        $user->save();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Cập nhật mật khẩu thành công, vui lòng đăng nhập lại');
    }

    /* ==================== QUÊN MẬT KHẨU ==================== */

    public function forgot_password(Request $request)
    {
        $email = $this->normalizeEmail($request->input('email'));
        $user = $email ? User::where('email', $email)->first() : null;

        if (!$user) {
            return redirect()->back()->with('warning', 'Email chưa được đăng ký trong hệ thống !');
        }

        if ($user->email_verified != 1) {
            return redirect()->back()->with('warning', 'Email chưa được kích hoạt trong hệ thống nên không thể reset mật khẩu !');
        }

        $token = Str::random(64);
        $user->reset_token = $token;
        $user->reset_token_expires_at = now()->addMinutes(60);
        $user->save();

        $to_email = $user->email;
        $name = $user->fullName;
        $now = Carbon::now('Asia/Ho_Chi_Minh')->format('d-m-Y');

        $link_reset_password = url('/update-password') . '?' . http_build_query([
            'email' => $to_email,
            'token' => $token,
        ]);

        try {
            Mail::send('web.pages.reset_password_mail', [
                'name'                => $name,
                'to_email'            => $to_email,
                'now'                 => $now,
                'link_reset_password' => $link_reset_password,
            ], function ($message) use ($name, $to_email, $now) {
                $message->subject('Xác nhận cập nhật lại mật khẩu ngày: ' . $now);
                $message->to($to_email, $name);
            });
        } catch (\Throwable $e) {
            report($e);
            return redirect()->back()->with('warning', 'Không gửi được email, vui lòng thử lại sau!');
        }

        return redirect()->back()->with('success', 'Vui lòng kiểm tra email để reset mật khẩu !');
    }

    public function update_password()
    {
        return view('web.pages.update_password');
    }

    public function Post_update_password(Request $request)
    {
        $email = $this->normalizeEmail($request->input('email'));
        $token = (string) $request->input('token', '');

        $user = ($email && $token !== '')
            ? User::where('email', $email)
                ->whereNotNull('reset_token')
                ->where('reset_token', $token)
                ->first()
            : null;

        if (!$user || !$user->reset_token_expires_at || now()->greaterThan($user->reset_token_expires_at)) {
            return redirect('/')->with('warning', 'Vui lòng thử lại vì đường dẫn không hợp lệ hoặc đã hết thời gian sử dụng');
        }

        $request->validate([
            'password'   => ['required', 'regex:' . self::PASSWORD_REGEX],
            'repassword' => 'required|same:password',
        ], [
            'password.required'   => 'Vui lòng nhập mật khẩu mới !',
            'password.regex'      => self::PASSWORD_REGEX_MSG,
            'repassword.required' => 'Vui lòng nhập lại mật khẩu !',
            'repassword.same'     => 'Mật khẩu nhập lại không khớp !',
        ]);

        if (Hash::check($request->input('password'), $user->password)) {
            return redirect()->back()->with('danger', 'Mật khẩu mới trùng với mật khẩu cũ');
        }

        $user->password = bcrypt($request->input('password'));
        $user->reset_token = null;
        $user->reset_token_expires_at = null;
        $user->save();

        return redirect('/')->with('success', 'Thay đổi mật khẩu thành công');
    }
}