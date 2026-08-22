<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class ProfileController extends Controller
{
    public function edit(Request $request): RedirectResponse
    {
        // Route Breeze cũ được giữ để tương thích với các liên kết hiện có,
        // nhưng giao diện hồ sơ chính của dự án là /user-settings.
        return Redirect::route('user.settings');
    }

    /**
     * Giữ route Breeze /profile hoạt động nhưng đồng bộ hồ sơ khách nếu tài khoản
     * đã có customer, tránh tình trạng users và customers mang hai email/tên khác nhau.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data): void {
            $emailChanged = strcasecmp((string) $user->email, (string) $data['email']) !== 0;
            $user->fill($data);
            if ($emailChanged) {
                $user->email_verified_at = null;
            }
            $user->save();

            $customer = $user->customer()->lockForUpdate()->first();
            if ($customer) {
                $parts = preg_split('/\s+/u', trim((string) $data['name'])) ?: [];
                $firstName = array_pop($parts) ?: trim((string) $data['name']);
                $lastName = implode(' ', $parts);

                $customer->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $data['email'],
                ]);
            }
        });

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $customer = $user->customer;

        if ($customer && $customer->bookings()->withTrashed()->exists()) {
            return Redirect::route('user.settings')->withErrors([
                'userDeletion' => 'Tài khoản đã có lịch sử đặt phòng nên không thể xóa. Vui lòng liên hệ quản trị viên nếu cần khóa tài khoản.',
            ]);
        }

        Auth::logout();

        DB::transaction(function () use ($customer, $user): void {
            // Không có lịch sử booking thì xóa hẳn để email/CCCD/số điện thoại
            // được giải phóng và khách có thể đăng ký lại sau này.
            if ($customer) {
                $customer->forceDelete();
            }
            $user->forceDelete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
