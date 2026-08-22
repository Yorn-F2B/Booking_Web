<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

class StayPricingPolicyService
{
    public function earlyCheckIn(Carbon $checkInAt, float $oneNightTotal, ?Booking $booking = null): array
    {
        $policy = app(HotelPolicyService::class);
        $minutes = $this->minuteOfDay($checkInAt->format('H:i'));
        $tier1End = $this->minuteOfDay((string) $policy->forBooking($booking, 'stay.early_checkin_tier1_end', '06:00'));
        $tier2End = $this->minuteOfDay((string) $policy->forBooking($booking, 'stay.early_checkin_tier2_end', '09:00'));
        $freeFrom = $this->minuteOfDay((string) $policy->forBooking($booking, 'stay.early_checkin_free_from', '12:00'));
        $standard = $this->minuteOfDay((string) $policy->forBooking($booking, 'stay.standard_check_in_time', '14:00'));
        $p1 = (float) $policy->forBooking($booking, 'stay.early_checkin_percent_1', 100);
        $p2 = (float) $policy->forBooking($booking, 'stay.early_checkin_percent_2', 50);
        $p3 = (float) $policy->forBooking($booking, 'stay.early_checkin_percent_3', 20);

        if ($minutes < $tier1End) {
            $percent = $p1;
            $text = 'Check-in sớm trước ' . $this->formatMinutes($tier1End) . ', phụ thu ' . $this->formatPercent($p1) . '% giá 1 đêm.';
        } elseif ($minutes < $tier2End) {
            $percent = $p2;
            $text = 'Check-in sớm từ ' . $this->formatMinutes($tier1End) . ' đến trước ' . $this->formatMinutes($tier2End) . ', phụ thu ' . $this->formatPercent($p2) . '% giá 1 đêm.';
        } elseif ($minutes < $freeFrom) {
            $percent = $p3;
            $text = 'Check-in sớm từ ' . $this->formatMinutes($tier2End) . ' đến trước ' . $this->formatMinutes($freeFrom) . ', phụ thu ' . $this->formatPercent($p3) . '% giá 1 đêm.';
        } else {
            $percent = 0;
            $text = $minutes < $standard
                ? 'Check-in từ ' . $this->formatMinutes($freeFrom) . ' đến trước ' . $this->formatMinutes($standard) . ', miễn phụ thu nếu phòng đã sẵn sàng.'
                : 'Check-in từ ' . $this->formatMinutes($standard) . ', không phụ thu.';
        }

        return [
            'percent' => $percent,
            'amount' => round($oneNightTotal * $percent / 100, 0),
            'policy_text' => $text,
        ];
    }

    public function lateCheckOut(Carbon $checkOutAt, float $oneNightTotal, ?Booking $booking = null): array
    {
        $policy = app(HotelPolicyService::class);
        $minutes = $this->minuteOfDay($checkOutAt->format('H:i'));
        $standard = $this->minuteOfDay((string) $policy->forBooking($booking, 'stay.standard_check_out_time', '12:00'));
        $freeUntil = $standard + max(0, (int) $policy->forBooking($booking, 'stay.late_checkout_free_minutes', 15));
        $tier1 = $this->minuteOfDay((string) $policy->forBooking($booking, 'stay.late_checkout_tier1_end', '13:00'));
        $tier2 = $this->minuteOfDay((string) $policy->forBooking($booking, 'stay.late_checkout_tier2_end', '14:00'));
        $tier3 = $this->minuteOfDay((string) $policy->forBooking($booking, 'stay.late_checkout_tier3_end', '15:00'));
        $full = $this->minuteOfDay((string) $policy->forBooking($booking, 'stay.late_checkout_full_night_from', '18:00'));
        $percents = [
            (float) $policy->forBooking($booking, 'stay.late_checkout_percent_1', 20),
            (float) $policy->forBooking($booking, 'stay.late_checkout_percent_2', 40),
            (float) $policy->forBooking($booking, 'stay.late_checkout_percent_3', 60),
            (float) $policy->forBooking($booking, 'stay.late_checkout_percent_4', 80),
            (float) $policy->forBooking($booking, 'stay.late_checkout_percent_full', 100),
        ];

        if ($minutes <= $freeUntil) {
            $percent = 0;
            $text = 'Trả phòng đến ' . $this->formatMinutes($freeUntil) . ', miễn phụ thu.';
        } elseif ($minutes <= $tier1) {
            $percent = $percents[0];
            $text = 'Trả phòng sau ' . $this->formatMinutes($freeUntil) . ' đến ' . $this->formatMinutes($tier1) . ', phụ thu ' . $this->formatPercent($percent) . '% giá 1 đêm.';
        } elseif ($minutes <= $tier2) {
            $percent = $percents[1];
            $text = 'Trả phòng sau ' . $this->formatMinutes($tier1) . ' đến ' . $this->formatMinutes($tier2) . ', phụ thu ' . $this->formatPercent($percent) . '% giá 1 đêm.';
        } elseif ($minutes <= $tier3) {
            $percent = $percents[2];
            $text = 'Trả phòng sau ' . $this->formatMinutes($tier2) . ' đến ' . $this->formatMinutes($tier3) . ', phụ thu ' . $this->formatPercent($percent) . '% giá 1 đêm.';
        } elseif ($minutes < $full) {
            $percent = $percents[3];
            $text = 'Trả phòng sau ' . $this->formatMinutes($tier3) . ' đến trước ' . $this->formatMinutes($full) . ', phụ thu ' . $this->formatPercent($percent) . '% giá 1 đêm.';
        } else {
            $percent = $percents[4];
            $text = 'Trả phòng từ ' . $this->formatMinutes($full) . ' trở đi, phụ thu ' . $this->formatPercent($percent) . '% giá 1 đêm.';
        }

        return [
            'percent' => $percent,
            'amount' => round($oneNightTotal * $percent / 100, 0),
            'policy_text' => $text,
        ];
    }

    public function lateArrival(Carbon $expectedArrivalAt, float $oneNightTotal, ?Carbon $cutoffAt = null, ?Booking $booking = null): array
    {
        $policy = app(HotelPolicyService::class);
        if (!$cutoffAt) {
            [$hour, $minute] = $this->hourMinute((string) $policy->forBooking($booking, 'stay.late_arrival_cutoff_time', '18:00'));
            $cutoffAt = $expectedArrivalAt->copy()->setTime($hour, $minute, 0);
        }

        if ($expectedArrivalAt->lessThanOrEqualTo($cutoffAt)) {
            return [
                'percent' => 0,
                'amount' => 0,
                'hours_after_cutoff' => 0,
                'policy_text' => 'Khách đến trước hoặc đúng giờ G ' . $cutoffAt->format('H:i') . ', không phụ thu đến muộn.',
            ];
        }

        $minutesAfterCutoff = $cutoffAt->diffInMinutes($expectedArrivalAt);
        $hoursAfterCutoff = round($minutesAfterCutoff / 60, 2);
        $arrivalMinutes = $this->minuteOfDay($expectedArrivalAt->format('H:i'));
        $tier1End = $this->minuteOfDay((string) $policy->forBooking($booking, 'stay.late_arrival_tier1_end', '21:00'));

        if ($expectedArrivalAt->toDateString() !== $cutoffAt->toDateString() || $arrivalMinutes === 0) {
            $percent = (float) $policy->forBooking($booking, 'stay.late_arrival_percent_next_day', 100);
            $text = 'Khách dự kiến đến từ ngày hôm sau, phụ thu ' . $this->formatPercent($percent) . '% giá 1 đêm để tiếp tục giữ phòng.';
        } elseif ($arrivalMinutes <= $tier1End) {
            $percent = (float) $policy->forBooking($booking, 'stay.late_arrival_percent_1', 20);
            $text = 'Khách dự kiến đến sau giờ G đến ' . $this->formatMinutes($tier1End) . ', phụ thu ' . $this->formatPercent($percent) . '% giá 1 đêm để tiếp tục giữ phòng.';
        } else {
            $percent = (float) $policy->forBooking($booking, 'stay.late_arrival_percent_2', 50);
            $text = 'Khách dự kiến đến sau ' . $this->formatMinutes($tier1End) . ' đến trước 00:00, phụ thu ' . $this->formatPercent($percent) . '% giá 1 đêm để tiếp tục giữ phòng.';
        }

        return [
            'percent' => $percent,
            'amount' => round(max(0, $oneNightTotal) * $percent / 100, 0),
            'hours_after_cutoff' => $hoursAfterCutoff,
            'policy_text' => $text,
        ];
    }

    public function shortStay(float $nightPrice, int $roomQuantity, int $durationMinutes, ?Booking $booking = null): array
    {
        $policy = app(HotelPolicyService::class);
        $baseHours = max(1, (int) $policy->forBooking($booking, 'stay.short_stay_base_hours', 2));
        $basePercent = max(0, (float) $policy->forBooking($booking, 'stay.short_stay_base_percent', 50)) / 100;
        $extraPercent = max(0, (float) $policy->forBooking($booking, 'stay.short_stay_extra_hour_percent', 10)) / 100;
        $maxPercent = max($basePercent, (float) $policy->forBooking($booking, 'stay.short_stay_max_percent', 80) / 100);
        $durationHours = max(1, (int) ceil($durationMinutes / 60));
        $chargedPercent = $basePercent;

        if ($durationHours > $baseHours) {
            $chargedPercent = min($maxPercent, $basePercent + (($durationHours - $baseHours) * $extraPercent));
        }

        return [
            'duration_hours' => $durationHours,
            'charged_percent' => $chargedPercent,
            'amount' => round($nightPrice * max(1, $roomQuantity) * $chargedPercent, 0),
            'policy_text' => $durationHours <= $baseHours
                ? 'Ở theo giờ: ' . $baseHours . ' giờ đầu bằng ' . $this->formatPercent($basePercent * 100) . '% giá 1 đêm.'
                : ($chargedPercent >= $maxPercent
                    ? 'Ở theo giờ đạt ngưỡng ' . $this->formatPercent($maxPercent * 100) . '% giá 1 đêm.'
                    : 'Ở theo giờ: ' . $baseHours . ' giờ đầu ' . $this->formatPercent($basePercent * 100) . '%, mỗi giờ tiếp theo cộng ' . $this->formatPercent($extraPercent * 100) . '% giá 1 đêm.'),
        ];
    }

    public function longStay(Carbon $checkInAt, Carbon $checkOutAt, float $nightPrice, int $roomQuantity, ?Booking $booking = null): array
    {
        $oneNightTotal = $nightPrice * max(1, $roomQuantity);
        $nightCount = max(1, $checkInAt->copy()->startOfDay()->diffInDays($checkOutAt->copy()->startOfDay()));
        $baseAmount = round($oneNightTotal * $nightCount, 0);
        $early = $this->earlyCheckIn($checkInAt, $oneNightTotal, $booking);
        $late = $this->lateCheckOut($checkOutAt, $oneNightTotal, $booking);

        $overnightThresholdHours = max(1, (int) app(HotelPolicyService::class)->forBooking($booking, 'stay.short_stay_to_overnight_hours', 12));

        return [
            'night_count' => $nightCount,
            'base_amount' => $baseAmount,
            'early' => $early,
            'late' => $late,
            'surcharge_amount' => $early['amount'] + $late['amount'],
            'total_amount' => $baseAmount + $early['amount'] + $late['amount'],
            'policy_text' => 'Tự động tính theo giá qua đêm vì thời lượng vượt ' . $overnightThresholdHours . ' giờ. '
                . $nightCount . ' đêm. ' . $early['policy_text'] . ' ' . $late['policy_text'],
        ];
    }

    private function minuteOfDay(string $time): int
    {
        [$hour, $minute] = $this->hourMinute($time);
        return ($hour * 60) + $minute;
    }

    private function hourMinute(string $time): array
    {
        $parts = array_map('intval', explode(':', $time));
        return [$parts[0] ?? 0, $parts[1] ?? 0];
    }

    private function formatMinutes(int $minutes): string
    {
        $minutes = max(0, $minutes);
        return sprintf('%02d:%02d', intdiv($minutes, 60) % 24, $minutes % 60);
    }

    private function formatPercent(float $percent): string
    {
        return rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.');
    }
}
