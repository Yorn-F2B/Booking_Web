<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BookingCodeGenerator
{
    private array $heldLocks = [];

    public function generate(?Carbon $date = null): string
    {
        $date ??= Carbon::now('Asia/Ho_Chi_Minh');
        $prefix = 'BK' . $date->format('dmY') . '-';
        $lockName = 'booking_code_' . $date->format('Ymd');

        $lock = DB::selectOne('SELECT GET_LOCK(?, 5) AS acquired', [$lockName]);
        if ((int) ($lock->acquired ?? 0) !== 1) {
            throw new RuntimeException('Không thể cấp số booking lúc này. Vui lòng thử lại.');
        }

        // Giữ named lock đến khi request kết thúc để booking được INSERT trước khi
        // request khác có thể lấy số tiếp theo. Unique index booking_code là lớp bảo vệ cuối.
        if (!in_array($lockName, $this->heldLocks, true)) {
            $this->heldLocks[] = $lockName;
            app()->terminating(function () use ($lockName): void {
                try {
                    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
                } catch (\Throwable) {
                }
            });
        }

        $latestCode = Booking::withTrashed()
            ->where('booking_code', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(booking_code, "-", -1) AS UNSIGNED) DESC')
            ->value('booking_code');

        $lastSequence = 0;
        if (is_string($latestCode) && preg_match('/-(\d+)$/', $latestCode, $matches)) {
            $lastSequence = (int) $matches[1];
        }

        return $prefix . str_pad((string) ($lastSequence + 1), 3, '0', STR_PAD_LEFT);
    }
}
