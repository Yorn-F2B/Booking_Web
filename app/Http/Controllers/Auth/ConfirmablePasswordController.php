<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(): View|RedirectResponse
    {
        $user = request()->user();

        if (!empty($user->google_id) && empty($user->password)) {
            return redirect()
                ->route('home')
                ->with('error', 'Tài khoản Google không sử dụng chức năng xác nhận mật khẩu.');
        }

        return view('auth.confirm-password');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!empty($user->google_id) && empty($user->password)) {
            return redirect()
                ->route('home')
                ->with('error', 'Tài khoản Google không sử dụng chức năng xác nhận mật khẩu.');
        }

        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
