<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Redirect về trang phù hợp với role khi không có quyền truy cập.
     * Không dùng abort(403) để tránh UX xấu.
     */
    private function redirectByRole($user): Response
    {
        return match ($user->role) {
            'super_admin'             => redirect()->route('admin.dashboard'),
            'manager',
            'receptionist_lead',
            'receptionist'            => redirect()->route('admin.bookings.index'),
            'housekeeping_supervisor',
            'housekeeping'            => redirect()->route('admin.housekeeping.index'),
            default                   => redirect()->route('home'),
        };
    }

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để tiếp tục.');
        }

        if (!in_array($user->role, $roles)) {
            return $this->redirectByRole($user)
                ->with('error', 'Bạn không có quyền truy cập trang này.');
        }

        return $next($request);
    }
}