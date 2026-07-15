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

        return match ($user->role) {
            'super_admin'            => redirect()->route('admin.dashboard'),
            'manager'                => redirect()->route('admin.bookings.index'),
            'receptionist_lead'      => redirect()->route('admin.bookings.index'),
            'receptionist'           => redirect()->route('admin.bookings.index'),
            'housekeeping_supervisor' => redirect()->route('admin.housekeeping.index'),
            'housekeeping'           => redirect()->route('admin.housekeeping.index'),
            default                  => redirect()->route('home'),
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