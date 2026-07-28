<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class UserSettingController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Tài khoản Google chỉ có user (tên, email, avatar) vẫn mở được Settings.
        // Customer sẽ chỉ được tạo khi người dùng thực sự lưu hồ sơ.
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

        return view('user.pages.user-settings', [
            'user' => $user,
            'customer' => $customer,
            'bookings' => $bookings,
            'bookingCount' => $bookings->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $customer = $user->customer;

        $request->merge([
            'first_name' => trim((string) $request->input('first_name')),
            'last_name' => trim((string) $request->input('last_name')),
            'phone' => preg_replace('/\s+/', '', (string) $request->input('phone')),
            'cccd' => preg_replace('/\s+/', '', (string) $request->input('cccd')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'address' => trim((string) $request->input('address')),
        ]);

        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
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
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('customers', 'email')->ignore($customer?->id),
            ],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'address' => ['required', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'last_name.required' => 'Vui lòng nhập họ.',
            'first_name.required' => 'Vui lòng nhập tên.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.',
            'phone.unique' => 'Số điện thoại này đã được một tài khoản khác sử dụng.',
            'cccd.required' => 'Vui lòng nhập số CCCD.',
            'cccd.regex' => 'Số CCCD phải gồm đúng 12 chữ số.',
            'cccd.unique' => 'Số CCCD này đã được một tài khoản khác sử dụng.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được một tài khoản khác sử dụng.',
            'gender.required' => 'Vui lòng chọn giới tính.',
            'address.required' => 'Vui lòng nhập địa chỉ liên hệ.',
            'birthday.date' => 'Ngày sinh không hợp lệ.',
            'birthday.before_or_equal' => 'Ngày sinh không được lớn hơn ngày hiện tại.',
            'avatar.image' => 'Tệp ảnh đại diện không hợp lệ.',
            'avatar.mimes' => 'Ảnh đại diện chỉ hỗ trợ JPG, JPEG, PNG hoặc WEBP.',
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
                        'last_name' => $validated['last_name'],
                        'first_name' => $validated['first_name'],
                        'phone' => $validated['phone'],
                        'cccd' => $validated['cccd'],
                        'email' => $validated['email'],
                        'birthday' => $validated['birthday'] ?? null,
                        'gender' => $validated['gender'],
                        'address' => $validated['address'],
                        'status' => 'active',
                    ]
                );

                $userData = [
                    'name' => trim($validated['last_name'].' '.$validated['first_name']),
                    'email' => $validated['email'],
                ];

                if ($newAvatarPath !== null) {
                    $userData['avatar'] = $newAvatarPath;
                }

                $user->name = $userData['name'];
                $user->email = $userData['email'];

                if (array_key_exists('avatar', $userData)) {
                    $user->avatar = $userData['avatar'];
                }

                $user->save();
            });
        } catch (QueryException $e) {
            if ($newAvatarPath !== null) {
                Storage::disk('public')->delete($newAvatarPath);
            }

            report($e);

            if ((string) $e->getCode() === '23000') {
                return back()
                    ->withInput()
                    ->withErrors(['profile' => 'Email, số điện thoại hoặc CCCD đã được tài khoản khác sử dụng.']);
            }

            return back()->withInput()->withErrors([
                'profile' => 'Không thể cập nhật hồ sơ. Vui lòng thử lại.',
            ]);
        } catch (Throwable $e) {
            if ($newAvatarPath !== null) {
                Storage::disk('public')->delete($newAvatarPath);
            }

            report($e);

            return back()->withInput()->withErrors([
                'profile' => 'Không thể cập nhật hồ sơ. Vui lòng thử lại.',
            ]);
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
        $isGoogleOnly = !empty($user->google_id) && empty($user->password);

        if ($isGoogleOnly) {
            return redirect()
                ->route('user.settings')
                ->withErrors([
                    'password' => 'Tài khoản này được tạo bằng Google nên không sử dụng mật khẩu tại hệ thống.',
                ]);
        }

        $hasPassword = !empty($user->password);

        $request->validate([
            'pass_old' => [$hasPassword ? 'required' : 'nullable', 'string'],
            'pass_new' => ['required', Password::min(8)],
            'pass_re' => ['required', 'same:pass_new'],
        ], [
            'pass_old.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'pass_new.required' => 'Vui lòng nhập mật khẩu mới.',
            'pass_new.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'pass_re.required' => 'Vui lòng nhập lại mật khẩu mới.',
            'pass_re.same' => 'Mật khẩu xác nhận không khớp.',
        ]);

        if ($hasPassword && !Hash::check((string) $request->pass_old, $user->password)) {
            return back()->withErrors([
                'pass_old' => 'Mật khẩu hiện tại không đúng.',
            ]);
        }

        $user->password = Hash::make($request->pass_new);
        $user->save();

        return back()->with('success', 'Đổi mật khẩu thành công.');
    }
}
