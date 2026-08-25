<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectStaffFromCustomerArea
{
    /**
     * Staff accounts are operational accounts. They must never enter the
     * customer/public UI while authenticated, even by typing a URL directly.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $staffRoles = [
            'super_admin',
            'manager',
            'receptionist_lead',
            'receptionist',
            'housekeeping_supervisor',
            'housekeeping',
        ];

        if (!in_array((string) $user->role, $staffRoles, true)) {
            return $next($request);
        }

        // Admin routes are the only application workspace for staff.
        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }

        // Reverb/private-channel authentication is a technical endpoint used by
        // the admin workspace. Blocking it would silently break realtime for staff.
        if ($request->is('broadcasting/auth')) {
            return $next($request);
        }

        // Keep authentication/account lifecycle endpoints usable. Dashboard is
        // itself a role-aware redirect and is safe for every authenticated role.
        $routeName = (string) ($request->route()?->getName() ?? '');
        $allowedRouteNames = [
            'dashboard',
            'logout',
            'verification.notice',
            'verification.verify',
            'verification.send',
            'password.confirm',
            'password.update',
            // Admin chat reuses the public attachment download controller;
            // access is still checked against the conversation inside that controller.
            'chat.attachments.download',
        ];

        if (in_array($routeName, $allowedRouteNames, true)) {
            return $next($request);
        }

        $destination = match ((string) $user->role) {
            'super_admin' => 'admin.dashboard',
            'manager', 'receptionist_lead', 'receptionist' => 'admin.bookings.index',
            'housekeeping_supervisor', 'housekeeping' => 'admin.housekeeping.index',
            default => 'dashboard',
        };

        return redirect()
            ->route($destination)
            ->with('warning', 'Tài khoản nhân viên chỉ được sử dụng khu vực quản trị/nghiệp vụ.');
    }
}
