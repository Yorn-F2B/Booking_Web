<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// QA clock cho các command/scheduler nghiệp vụ. Không đổi giờ Windows.
// Bật/tắt bằng http://127.0.0.1:8000/qa-time.php khi APP_ENV=local.
if (app()->environment(['local', 'testing'])) {
    $qaClockFile = storage_path('framework/qa_business_time.txt');

    if (is_file($qaClockFile)) {
        $qaClockRaw = trim((string) file_get_contents($qaClockFile));

        if ($qaClockRaw !== '') {
            try {
                $qaClockNow = \Carbon\Carbon::parse($qaClockRaw, config('app.timezone', 'Asia/Ho_Chi_Minh'));
                \Carbon\Carbon::setTestNow($qaClockNow);
                \Carbon\CarbonImmutable::setTestNow($qaClockNow->toImmutable());

                try {
                    \Illuminate\Support\Facades\DB::statement('SET timestamp = '.(int) $qaClockNow->timestamp);
                } catch (\Throwable) {
                    // Command không dùng DB vẫn được phép chạy.
                }
            } catch (\Throwable) {
                // File clock sai định dạng thì bỏ qua và dùng giờ thật.
            }
        }
    }
}

Schedule::command('bookings:expire-unpaid')->everyMinute()->withoutOverlapping();

Schedule::command('bookings:cancel-no-show')->everyMinute()->withoutOverlapping();
