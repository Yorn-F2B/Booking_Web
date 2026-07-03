<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserSettingController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để tiếp tục.');
        }

        $customer = Customer::where('user_id', $user->id)->first();

        if (!$customer) {
            $customer = new Customer();
            $customer->user_id = $user->id;
            $customer->first_name = '';
            $customer->last_name = '';
            $customer->phone = 'temp_' . $user->id . '_' . time(); // Temporary unique phone
            $customer->gender = null;
            $customer->address = '';
            $customer->email = $user->email;
            $customer->status = 'active';
            $customer->save();
        }

        $bookings = Booking::with(['roomCategory', 'bookingRooms.room'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->get();

        $bookingCount = $bookings->count();

        return view('user.pages.user-settings', compact(
            'user',
            'customer',
            'bookings',
            'bookingCount'
        ));
    }

    public function update(Request $request)
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        if ($customer) {
            $customer->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'cccd' => $request->cccd,
                'email' => $request->email,
                'birthday' => $request->birthday,
                'gender' => $request->gender,
                'address' => $request->address,
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $data = [
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
        ];

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return back()->with('success', 'Cập nhật thành công');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'pass_old' => 'required',
            'pass_new' => 'required|min:8',
            'pass_re' => 'required|same:pass_new',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!Hash::check($request->pass_old, $user->password)) {
            return back()->withErrors([
                'pass_old' => 'Mật khẩu hiện tại không đúng',
            ]);
        }

        $user->update([
            'password' => bcrypt($request->pass_new),
        ]);

        return back()->with('success', 'Đổi mật khẩu thành công');
    }
}