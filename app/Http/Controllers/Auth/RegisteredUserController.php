<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\Customer;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */

    public function store(Request $request): RedirectResponse
    {
        $request->validate([

            'first_name' => ['required', 'string', 'max:255'],

            'last_name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users'
            ],

            'phone' => [
                'required',
                'unique:customers'
            ],

            'cccd' => [
                'required',
                'unique:customers'
            ],

            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],

        ]);

        // TẠO USER
        $user = User::create([

            'name' =>
                $request->first_name . ' ' .
                $request->last_name,

            'email' => $request->email,

            'password' => Hash::make($request->password),

            'role' => 'customer',

            'status' => 'active',
        ]);

        // TẠO CUSTOMER
        Customer::create([

            'user_id' => $user->id,

            'first_name' => $request->first_name,

            'last_name' => $request->last_name,

            'phone' => $request->phone,

            'cccd' => $request->cccd,

            'email' => $request->email,

            'birthday' => $request->birthday,

            'gender' => $request->gender,

            'address' => $request->address,
        ]);

        // LOGIN NGAY SAU REGISTER
        Auth::login($user);

        // CHUYỂN TRANG
        return redirect('/');
    }

}
