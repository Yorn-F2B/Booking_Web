<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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

            if ($googleId === '' || $email === '') {
                return redirect()->route('login')
                    ->with('error', 'Google không cung cấp đủ thông tin tài khoản. Vui lòng dùng tài khoản Google có email.');
            }

            $user = DB::transaction(function () use ($googleId, $email): User {
                $userByGoogleId = User::where('google_id', $googleId)->lockForUpdate()->first();
                $userByEmail = User::whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
                $customerByEmail = Customer::whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();

                if ($userByGoogleId && $userByEmail && $userByGoogleId->id !== $userByEmail->id) {
                    throw new \RuntimeException('GOOGLE_ACCOUNT_CONFLICT');
                }

                $user = $userByGoogleId ?: $userByEmail;

                if ($user) {
                    if (!empty($user->google_id) && $user->google_id !== $googleId) {
                        throw new \RuntimeException('GOOGLE_ACCOUNT_CONFLICT');
                    }

                    if ($customerByEmail && $customerByEmail->user_id && (int) $customerByEmail->user_id !== (int) $user->id) {
                        throw new \RuntimeException('GOOGLE_ACCOUNT_CONFLICT');
                    }

                    $updates = ['google_id' => $googleId];
                    if (empty($user->email_verified_at)) {
                        $updates['email_verified_at'] = now();
                    }
                    $user->forceFill($updates)->save();

                    if ($user->role === 'customer') {
                        $linkedCustomer = $user->customer()->lockForUpdate()->first();

                        if (!$linkedCustomer && $customerByEmail && !$customerByEmail->user_id) {
                            $customerByEmail->user_id = $user->id;
                            $customerByEmail->save();
                            $linkedCustomer = $customerByEmail;
                        }

                        if ($linkedCustomer && $linkedCustomer->status === 'blacklist' && $user->status !== 'banned') {
                            $user->status = 'banned';
                            $user->save();
                        }
                    }

                    return $user->refresh();
                }

                if ($customerByEmail && $customerByEmail->user_id) {
                    throw new \RuntimeException('GOOGLE_ACCOUNT_CONFLICT');
                }

                $user = User::create([
                    'name' => $customerByEmail?->full_name ?: $email,
                    'email' => $email,
                    'email_verified_at' => now(),
                    'google_id' => $googleId,
                    'role' => 'customer',
                    'status' => $customerByEmail?->status === 'blacklist' ? 'banned' : 'active',
                    'password' => null,
                ]);

                // Google đã xác minh quyền sở hữu email. Nếu email này từng đặt
                // phòng dưới dạng khách vãng lai thì liên kết lại hồ sơ cũ thay
                // vì tạo một Customer trùng email rồi lỗi unique khi cập nhật hồ sơ.
                if ($customerByEmail && !$customerByEmail->user_id) {
                    $customerByEmail->user_id = $user->id;
                    $customerByEmail->save();
                }

                return $user;
            });

            if (config('account_restrictions.enabled', false) && $user->booking_locked_until && now()->lt($user->booking_locked_until)) {
                $until = $user->booking_locked_until->timezone('Asia/Ho_Chi_Minh');
                return redirect()->route('login')
                    ->with('error', 'Tài khoản đang bị khóa đến ' . $until->format('H:i d/m/Y') . '. ' . ($user->booking_lock_reason ?: ''))
                    ->with('locked_until', $until->toIso8601String());
            }

            if (($user->status ?? 'active') !== 'active') {
                return redirect()->route('login')->with('error', $user->status === 'banned'
                    ? 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.'
                    : 'Tài khoản đang bị vô hiệu hóa.');
            }

            Auth::login($user, true);
            request()->session()->regenerate();

            if ($user->role !== 'customer') {
                return match ($user->role) {
                    'super_admin' => redirect()->route('admin.dashboard'),
                    'manager', 'receptionist', 'receptionist_lead' => redirect()->route('admin.bookings.index'),
                    'housekeeping', 'housekeeping_supervisor' => redirect()->route('admin.housekeeping.index'),
                    default => redirect()->route('home'),
                };
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
