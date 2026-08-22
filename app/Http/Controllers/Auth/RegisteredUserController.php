<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:customers,email'],
            'phone' => ['required', 'regex:/^0[0-9]{9}$/', 'unique:customers,phone'],
            'cccd' => ['required', 'regex:/^[0-9]{12}$/', 'unique:customers,cccd'],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'phone.regex' => 'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0.',
            'cccd.regex' => 'Số CCCD phải gồm đúng 12 chữ số.',
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $email = mb_strtolower(trim($validated['email']));

            $user = User::create([
                'name' => trim($validated['last_name'].' '.$validated['first_name']),
                'email' => $email,
                'password' => Hash::make($validated['password']),
                'role' => 'customer',
                'status' => 'active',
            ]);

            Customer::create([
                'user_id' => $user->id,
                'first_name' => trim($validated['first_name']),
                'last_name' => trim($validated['last_name']),
                'phone' => $validated['phone'],
                'cccd' => $validated['cccd'],
                'email' => $email,
                'birthday' => $validated['birthday'] ?? null,
                'gender' => $validated['gender'],
                'address' => $validated['address'] ?? null,
                'status' => 'active',
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')
            ->with('success', 'Đăng ký tài khoản thành công.');
    }
}
