<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        if (config('account_restrictions.enabled', false) && $user->booking_locked_until && now()->lt($user->booking_locked_until)) {
            $until = $user->booking_locked_until->timezone('Asia/Ho_Chi_Minh');
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Tài khoản đang bị khóa đến ' . $until->format('H:i d/m/Y')
                    . '. Lý do: ' . ($user->booking_lock_reason ?: 'Vi phạm chính sách sử dụng.'),
            ])->with('locked_until', $until->toIso8601String());
        }

        if (($user->status ?? 'active') === 'inactive') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['email' => 'Tài khoản đang bị vô hiệu hóa.']);
        }

        return match ($user->role) {
            'super_admin' => redirect()->route('admin.dashboard'),
            'manager' => redirect()->route('admin.rooms.index'),
            'receptionist' => redirect()->route('admin.bookings.index'),
            'housekeeping' => redirect()->route('admin.housekeeping.index'),
            default => redirect()->route('home'),
        };
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}