<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $googleId = (string) $googleUser->getId();
            $email = mb_strtolower(trim((string) $googleUser->getEmail()));
            $name = trim((string) $googleUser->getName());
            $avatar = $googleUser->getAvatar();

            if ($googleId === '' || $email === '') {
                return redirect()->route('login')
                    ->with('error', 'Google không cung cấp đủ thông tin tài khoản. Vui lòng dùng tài khoản Google có email.');
            }

            $user = DB::transaction(function () use ($googleId, $email, $name, $avatar): User {
                $userByGoogleId = User::where('google_id', $googleId)->lockForUpdate()->first();
                $userByEmail = User::where('email', $email)->lockForUpdate()->first();

                if ($userByGoogleId && $userByEmail && $userByGoogleId->id !== $userByEmail->id) {
                    throw new \RuntimeException('GOOGLE_ACCOUNT_CONFLICT');
                }

                $user = $userByGoogleId ?: $userByEmail;

                if ($user) {
                    if (!empty($user->google_id) && $user->google_id !== $googleId) {
                        throw new \RuntimeException('GOOGLE_ACCOUNT_CONFLICT');
                    }

                    $updates = [
                        'google_id' => $googleId,
                    ];

                    if (empty($user->name) && $name !== '') {
                        $updates['name'] = $name;
                    }

                    // Không ghi đè ảnh người dùng tự tải lên.
                    if (empty($user->avatar) && !empty($avatar)) {
                        $updates['avatar'] = $avatar;
                    }

                    if (empty($user->email_verified_at)) {
                        $updates['email_verified_at'] = now();
                    }

                    $user->forceFill($updates)->save();

                    return $user->refresh();
                }

                return User::create([
                    'name' => $name !== '' ? $name : $email,
                    'email' => $email,
                    'email_verified_at' => now(),
                    'google_id' => $googleId,
                    'avatar' => $avatar,
                    'role' => 'customer',
                    'status' => 'active',
                    'password' => null,
                ]);
            });

            if (in_array($user->status, ['banned', 'inactive'], true)) {
                return redirect()->route('login')
                    ->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
            }

            Auth::login($user, true);
            request()->session()->regenerate();

            if ($user->role !== 'customer') {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('home')
                ->with('success', 'Đăng nhập bằng Google thành công.');
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'GOOGLE_ACCOUNT_CONFLICT') {
                return redirect()->route('login')
                    ->with('error', 'Tài khoản Google này đang liên kết với một tài khoản khác.');
            }

            report($e);

            return redirect()->route('login')
                ->with('error', 'Không thể xử lý tài khoản Google. Vui lòng thử lại.');
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')
                ->with('error', 'Đăng nhập bằng Google thất bại. Vui lòng thử lại.');
        }
    }
}
