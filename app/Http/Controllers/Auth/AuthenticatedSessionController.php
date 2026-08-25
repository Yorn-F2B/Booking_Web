<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ChatAssignmentService;
use App\Services\ChatPresenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private ChatPresenceService $chatPresenceService,
        private ChatAssignmentService $chatAssignmentService,
    ) {
    }

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

        if (($user->status ?? 'active') !== 'active') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['email' => $user->status === 'banned'
                ? 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.'
                : 'Tài khoản đang bị vô hiệu hóa.']);
        }

        if ($this->chatPresenceService->isEligible($user)) {
            $this->chatPresenceService->markOnline($user);
            $this->chatAssignmentService->handoffStaleOnlineStaff();
            $this->chatAssignmentService->assignWaitingConversations();
            $this->chatAssignmentService->assignUnassignedBookings();
            // Nhân viên mới vào ca nhận một phần các gói an toàn, không giật booking
            // đã check-in/đang thao tác hay assignment được quản lý ghim thủ công.
            $this->chatAssignmentService->softRebalance();
        }

        return match ($user->role) {
            'super_admin' => redirect()->route('admin.dashboard'),
            'manager' => redirect()->route('admin.bookings.index'),
            'receptionist', 'receptionist_lead' => redirect()->route('admin.bookings.index'),
            'housekeeping', 'housekeeping_supervisor' => redirect()->route('admin.housekeeping.index'),
            default => redirect()->route('home'),
        };
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user && $this->chatPresenceService->isEligible($user)) {
            $this->chatPresenceService->markOffline($user);
            $this->chatAssignmentService->handoffAll($user, 'rebalance');
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}