<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserSettingController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

// Không tạo Customer rỗng tại đây vì phone và CCCD đều có ràng buộc unique.
        // Tài khoản đăng nhập Google chưa có hồ sơ sẽ được tạo Customer khi người dùng lưu thông tin.
        $customer = $user->customer ?? new Customer([
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => 'active',
        ]);

        $bookings = $customer->exists
            ? Booking::with(['roomCategory', 'bookingRooms.room'])
                ->where('customer_id', $customer->id)
                ->latest()
                ->get()
            : collect();

        $bookingCount = $bookings->count();

        return view('user.pages.user-settings', compact(
            'user',
            'customer',
            'bookings',
            'bookingCount'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $customer = $user->customer;

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => [
                'required',
                'regex:/^0[0-9]{9}$/',
                Rule::unique('customers', 'phone')->ignore($customer?->id),
            ],
            'cccd' => [
                'required',
                'regex:/^[0-9]{12}$/',
                Rule::unique('customers', 'cccd')->ignore($customer?->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('customers', 'email')->ignore($customer?->id),
            ],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'address' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'first_name.required' => 'Vui lòng nhập họ.',
            'last_name.required' => 'Vui lòng nhập tên.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'cccd.required' => 'Vui lòng nhập số CCCD.',
            'cccd.regex' => 'Số CCCD phải gồm đúng 12 chữ số.',
            'cccd.unique' => 'Số CCCD này đã được sử dụng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'birthday.before_or_equal' => 'Ngày sinh không được lớn hơn ngày hiện tại.',
            'avatar.image' => 'Ảnh đại diện không hợp lệ.',
            'avatar.max' => 'Ảnh đại diện không được vượt quá 2 MB.',
        ]);

        $newAvatarPath = null;
        $oldLocalAvatar = null;

        if ($request->hasFile('avatar')) {
            $newAvatarPath = $request->file('avatar')->store('avatars', 'public');

            if (!empty($user->avatar) && !str_starts_with($user->avatar, 'http')) {
                $oldLocalAvatar = $user->avatar;
            }
        }

        try {
            DB::transaction(function () use ($validated, $user, $newAvatarPath): void {
                Customer::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'first_name' => trim($validated['first_name']),
                        'last_name' => trim($validated['last_name']),
                        'phone' => $validated['phone'],
                        'cccd' => $validated['cccd'],
                        'email' => mb_strtolower(trim($validated['email'])),
                        'birthday' => $validated['birthday'] ?? null,
                        'gender' => $validated['gender'],
                        'address' => $validated['address'] ?? null,
                        'status' => 'active',
                    ]
                );

                $userData = [
                    'name' => trim($validated['first_name'].' '.$validated['last_name']),
                    'email' => mb_strtolower(trim($validated['email'])),
                ];

                if ($newAvatarPath !== null) {
                    $userData['avatar'] = $newAvatarPath;
                }

                $user->update($userData);
            });
        } catch (\Throwable $e) {
            if ($newAvatarPath !== null) {
                Storage::disk('public')->delete($newAvatarPath);
            }

            report($e);

            return back()
                ->withInput()
                ->withErrors(['profile' => 'Không thể cập nhật hồ sơ. Vui lòng thử lại.']);
        }

        if ($oldLocalAvatar !== null) {
            Storage::disk('public')->delete($oldLocalAvatar);
        }

        return redirect()->route('user.settings')
            ->with('success', 'Cập nhật thông tin cá nhân thành công.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $hasPassword = !empty($user->password);

        $request->validate([
            'pass_old' => [$hasPassword ? 'required' : 'nullable', 'string'],
            'pass_new' => ['required', Password::min(8)],
            'pass_re' => ['required', 'same:pass_new'],
        ], [
            'pass_old.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'pass_new.required' => 'Vui lòng nhập mật khẩu mới.',
            'pass_new.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'pass_re.same' => 'Mật khẩu xác nhận không khớp.',
        ]);

        if ($hasPassword && !Hash::check((string) $request->pass_old, $user->password)) {
            return back()->withErrors([
                'pass_old' => 'Mật khẩu hiện tại không đúng.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->pass_new),
        ]);

        return back()->with('success', $hasPassword
            ? 'Đổi mật khẩu thành công.'
            : 'Tạo mật khẩu cho tài khoản thành công.');
    }
}
