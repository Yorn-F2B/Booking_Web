<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Redirect người dùng đến Google OAuth.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * Xử lý callback từ Google OAuth.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            try {
                // Fallback cho môi trường local khi session OAuth bị mất state.
                $googleUser = Socialite::driver('google')->stateless()->user();
            } catch (Throwable $fallbackException) {
                Log::warning('Google OAuth invalid state', [
                    'message' => $fallbackException->getMessage(),
                ]);

                return redirect()->route('login')
                    ->with('error', 'Phiên đăng nhập Google đã hết hạn hoặc không hợp lệ. Vui lòng thử lại.');
            }
        } catch (Throwable $e) {
            Log::error('Google OAuth callback failed', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Đăng nhập bằng Google thất bại. Vui lòng thử lại.');
        }

        $googleEmail = $googleUser->getEmail();
        $googleName = trim((string) $googleUser->getName());
        $googleId = (string) $googleUser->getId();

        if ($googleId === '') {
            return redirect()->route('login')
                ->with('error', 'Không thể xác thực tài khoản Google. Vui lòng thử lại.');
        }

        if (!$googleEmail) {
            return redirect()->route('login')
                ->with('error', 'Tài khoản Google của bạn chưa cung cấp email. Vui lòng chọn tài khoản khác hoặc đăng ký bằng email.');
        }

        $user = DB::transaction(function () use ($googleEmail, $googleId, $googleName, $googleUser) {
            $user = User::where('google_id', $googleId)->first();

            if (!$user) {
                $user = User::where('email', $googleEmail)->first();
            }

            if ($user) {
                $user->forceFill([
                    'google_id' => $googleId,
                    'avatar' => $googleUser->getAvatar(),
                ])->save();
            } else {
                $nameParts = preg_split('/\s+/', $googleName, 2) ?: [];
                $firstName = $nameParts[0] ?? ($googleName !== '' ? $googleName : 'Google');
                $lastName = $nameParts[1] ?? '';

                $user = User::create([
                    'name' => $googleName !== '' ? $googleName : $googleEmail,
                    'email' => $googleEmail,
                    'google_id' => $googleId,
                    'avatar' => $googleUser->getAvatar(),
                    'role' => 'customer',
                    'status' => 'active',
                    'password' => Hash::make(Str::password(32)),
                ]);

                $user->forceFill([
                    'email_verified_at' => now(),
                ])->save();

                Customer::create([
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $googleEmail,
                    'status' => 'active',
                ]);
            }

            if ($user->role === 'customer' && !$user->customer) {
                $nameParts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];

                Customer::create([
                    'user_id' => $user->id,
                    'first_name' => $nameParts[0] ?? $user->name,
                    'last_name' => $nameParts[1] ?? '',
                    'email' => $user->email,
                    'status' => 'active',
                ]);
            }

            return $user->fresh(['customer']);
        });

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
