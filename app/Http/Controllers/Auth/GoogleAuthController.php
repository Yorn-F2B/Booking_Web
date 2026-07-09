<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Redirect người dùng đến Google OAuth.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Xử lý callback từ Google OAuth.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return redirect()->route('login')
                ->with('error', 'Đăng nhập bằng Google thất bại. Vui lòng thử lại.');
        }

        // Kiểm tra email có trong hệ thống chưa
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Tài khoản đã tồn tại → cập nhật google_id nếu chưa có, rồi đăng nhập
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            // Tạo User mới với role customer
            $nameParts = explode(' ', trim($googleUser->getName()), 2);
            $firstName = $nameParts[0] ?? $googleUser->getName();
            $lastName  = $nameParts[1] ?? '';

            $user = User::create([
                'name'       => $googleUser->getName(),
                'email'      => $googleUser->getEmail(),
                'google_id'  => $googleUser->getId(),
                'avatar'     => $googleUser->getAvatar(),
                'role'       => 'customer',
                'status'     => 'active',
                'password'   => null,
            ]);

            // Tạo Customer record tương ứng
            Customer::create([
                'user_id'    => $user->id,
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $googleUser->getEmail(),
                'status'     => 'active',
            ]);
        }

        // Kiểm tra tài khoản có bị khoá không
        if ($user->status === 'banned' || $user->status === 'inactive') {
            return redirect()->route('login')
                ->with('error', 'Tài khoản của bạn đã bị khoá. Vui lòng liên hệ quản trị viên.');
        }

        Auth::login($user, true);

        request()->session()->regenerate();

        // Chỉ cho phép nhân viên vào admin, customer về trang chủ
        return match ($user->role) {
            'super_admin'  => redirect()->route('admin.dashboard'),
            'manager'      => redirect()->route('admin.dashboard'),
            'receptionist' => redirect()->route('admin.dashboard'),
            'housekeeping' => redirect()->route('admin.dashboard'),
            default        => redirect()->route('home'),
        };
    }
}
