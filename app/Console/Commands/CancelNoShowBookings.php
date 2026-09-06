<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingCancellationService;
use App\Services\BookingFinancialService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CancelNoShowBookings extends Command
{
    protected $signature = 'bookings:cancel-no-show
                            {--now= : Thời điểm giả lập để kiểm thử, định dạng Y-m-d H:i:s. Bỏ trống để dùng giờ thực tế}';

    protected $description = 'Tự động hủy booking quá hạn giữ hoặc đã hết thời gian lưu trú nhưng khách chưa check-in';

    public function handle(BookingFinancialService $financials, BookingCancellationService $cancellations): int
    {
        try {
            $now = $this->resolveNow();
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $bookings = Booking::query()
            ->where('status', 'confirmed')
            ->whereNull('actual_check_in')
            ->whereNotNull('check_in_at')
            ->whereNotNull('check_out_at')
            ->get();

        $count = 0;

        foreach ($bookings as $booking) {
            $usesNoShowPolicy = $booking->usesLateArrivalNoShowPolicy();
            $holdLimitAt = $usesNoShowPolicy
                ? $booking->lateArrivalHoldLimitAt()
                : Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

            if (!$holdLimitAt || $now->lt($holdLimitAt)) {
                continue;
            }

            try {
                $policy = $financials->cancellationPolicy($booking);
                $historyPrefix = $usesNoShowPolicy
                    ? $this->buildNoShowHistoryPrefix($booking, $holdLimitAt, $now)
                    : $this->buildExpiredStayHistoryPrefix($booking, $holdLimitAt, $now);

                $cancellations->cancel(
                    $booking,
                    $policy,
                    null,
                    'system_no_show_cancelled',
                    'Hệ thống'
                );

                $this->clarifyLatestCancellationHistory($booking->id, $historyPrefix);

                $booking->refresh()->update([
                    'late_arrival_policy' => $historyPrefix,
                ]);

                $count++;
            } catch (\Throwable $e) {
                $this->warn('Không thể hủy booking #' . $booking->id . ': ' . $e->getMessage());
            }
        }

        if ($this->option('now')) {
            $this->line('Thời điểm kiểm thử: ' . $now->format('d/m/Y H:i:s'));
        }

        $this->info('Đã tự động hủy ' . $count . ' booking no-show.');

        return self::SUCCESS;
    }

    private function buildExpiredStayHistoryPrefix(Booking $booking, Carbon $checkOutAt, Carbon $now): string
    {
        return 'Hệ thống tự động hủy booking vì đã quá toàn bộ thời gian lưu trú nhưng khách chưa từng check-in. '
            . 'Loại đơn: ' . ($booking->booking_mode === 'walk_in' ? 'đặt tại quầy' : 'đặt trước')
            . ' / ' . ($booking->booking_type === 'hourly' ? 'theo giờ' : 'qua đêm') . '. '
            . 'Giờ nhận dự kiến: ' . Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i') . '. '
            . 'Giờ trả dự kiến: ' . $checkOutAt->format('d/m/Y H:i') . '. '
            . 'Thời điểm hệ thống xử lý: ' . $now->format('d/m/Y H:i') . '.';
    }

    private function buildNoShowHistoryPrefix(Booking $booking, Carbon $holdLimitAt, Carbon $now): string
    {
        $timezone = 'Asia/Ho_Chi_Minh';
        $checkInDate = Carbon::parse($booking->check_in_date, $timezone);
        $originalCutoffAt = Carbon::parse($booking->check_in_date . ' ' . $booking->lateArrivalCutoffTime(), $timezone);
        $hasExtendedHold = $booking->late_arrival_confirmed_at !== null
            && $booking->late_arrival_hours !== null
            && (float) $booking->late_arrival_hours > 0;

        if (!$hasExtendedHold) {
            return 'Hệ thống tự động hủy booking do khách không check-in trước giờ G. '
                . 'Giờ G: ' . $originalCutoffAt->format('d/m/Y H:i') . '. '
                . 'Khách không xác nhận giữ phòng sau giờ G. '
                . 'Thời điểm hệ thống xử lý: ' . $now->format('d/m/Y H:i') . '.';
        }

        $expectedArrivalAt = $originalCutoffAt->copy()->addHours((float) $booking->late_arrival_hours);

        return 'Hệ thống tự động hủy booking do khách không check-in trước hạn giữ phòng đã gia hạn. '
            . 'Giờ G ban đầu: ' . $originalCutoffAt->format('d/m/Y H:i') . '. '
            . 'Khách đã xác nhận dự kiến đến lúc: ' . $expectedArrivalAt->format('d/m/Y H:i') . '. '
            . 'Hạn giữ mới: ' . $holdLimitAt->format('d/m/Y H:i') . '. '
            . 'Thời điểm hệ thống xử lý: ' . $now->format('d/m/Y H:i') . '.';
    }

    private function clarifyLatestCancellationHistory(int $bookingId, string $historyPrefix): void
    {
        $log = DB::table('booking_logs')
            ->where('booking_id', $bookingId)
            ->where('action', 'system_no_show_cancelled')
            ->orderByDesc('id')
            ->first();

        if (!$log) {
            return;
        }

        $existingDescription = trim((string) $log->description);
        $description = $historyPrefix;

        if ($existingDescription !== '') {
            $description .= ' ' . $existingDescription;
        }

        DB::table('booking_logs')
            ->where('id', $log->id)
            ->update([
                'description' => $description,
                'updated_at' => now(),
            ]);
    }

    private function resolveNow(): Carbon
    {
        $timezone = 'Asia/Ho_Chi_Minh';
        $testNow = trim((string) $this->option('now'));

        if ($testNow === '') {
            return Carbon::now($timezone);
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $testNow, $timezone);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                'Giá trị --now không hợp lệ. Hãy dùng định dạng: --now="2026-07-20 18:01:00".'
            );
        }
    }
}
