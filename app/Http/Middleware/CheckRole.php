<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Vui lòng đăng nhập để tiếp tục.');
        }

        if (($user->status ?? 'active') !== 'active') {
            abort(403, $user->status === 'banned'
                ? 'Tài khoản đã bị khóa.'
                : 'Tài khoản đang bị vô hiệu hóa.');
        }

        if (!in_array($user->role, $roles, true)) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}