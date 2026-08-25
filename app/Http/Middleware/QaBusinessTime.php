<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class QaBusinessTime
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        $clockFile = storage_path('framework/qa_business_time.txt');
        if (!is_file($clockFile)) {
            return $next($request);
        }

        $raw = trim((string) file_get_contents($clockFile));
        if ($raw === '') {
            return $next($request);
        }

        try {
            $fakeNow = Carbon::parse($raw, config('app.timezone', 'Asia/Ho_Chi_Minh'));
        } catch (\Throwable) {
            return $next($request);
        }

        $previousCarbon = Carbon::getTestNow();
        $previousImmutable = CarbonImmutable::getTestNow();
        $dbClockChanged = false;

        Carbon::setTestNow($fakeNow);
        CarbonImmutable::setTestNow($fakeNow->toImmutable());

        // Đồng bộ cả NOW()/CURRENT_TIMESTAMP của MariaDB trong đúng request nghiệp vụ.
        // CSRF/session đã chạy trước middleware này; finally sẽ trả clock về giờ thật
        // trước khi middleware session ghi response/cookie.
        try {
            DB::statement('SET timestamp = '.(int) $fakeNow->timestamp);
            $dbClockChanged = true;
        } catch (\Throwable) {
            // Không chặn request nếu DB chưa sẵn sàng ở một route không cần DB.
        }

        try {
            $response = $next($request);
            $response->headers->set('X-QA-Business-Time', $fakeNow->format('Y-m-d H:i:s'));

            return $response;
        } finally {
            if ($dbClockChanged) {
                try {
                    DB::statement('SET timestamp = 0');
                } catch (\Throwable) {
                    // Connection có thể đã đóng; không làm hỏng response.
                }
            }

            Carbon::setTestNow($previousCarbon);
            CarbonImmutable::setTestNow($previousImmutable);
        }
    }
}
