<?php

namespace App\Services;

use Carbon\Carbon;

class StayPricingPolicyService
{
    public const STANDARD_CHECK_IN_MINUTES = 14 * 60;
    public const STANDARD_CHECK_OUT_MINUTES = 12 * 60;
    public const FREE_LATE_CHECKOUT_MINUTES = 15;

    public function earlyCheckIn(Carbon $checkInAt, float $oneNightTotal): array
    {
        $minutes = ((int) $checkInAt->format('H')) * 60 + (int) $checkInAt->format('i');

        if ($minutes < 6 * 60) {
            $percent = 100;
            $text = 'Check-in sớm trước 06:00, phụ thu 100% giá 1 đêm.';
        } elseif ($minutes < 9 * 60) {
            $percent = 50;
            $text = 'Check-in sớm từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm.';
        } elseif ($minutes < 12 * 60) {
            $percent = 20;
            $text = 'Check-in sớm từ 09:00 đến trước 12:00, phụ thu 20% giá 1 đêm.';
        } else {
            $percent = 0;
            $text = $minutes < self::STANDARD_CHECK_IN_MINUTES
                ? 'Check-in từ 12:00 đến trước 14:00, miễn phụ thu nếu phòng đã sẵn sàng.'
                : 'Check-in từ 14:00, không phụ thu.';
        }

        return [
            'percent' => $percent,
            'amount' => round($oneNightTotal * $percent / 100, 0),
            'policy_text' => $text,
        ];
    }

    public function lateCheckOut(Carbon $checkOutAt, float $oneNightTotal): array
    {
        $minutes = ((int) $checkOutAt->format('H')) * 60 + (int) $checkOutAt->format('i');
        $freeUntil = self::STANDARD_CHECK_OUT_MINUTES + self::FREE_LATE_CHECKOUT_MINUTES;

        if ($minutes <= $freeUntil) {
            $percent = 0;
            $text = 'Trả phòng đến 12:15, miễn phụ thu.';
        } elseif ($minutes <= 13 * 60) {
            $percent = 20;
            $text = 'Trả phòng sau 12:15 đến 13:00, phụ thu 20% giá 1 đêm.';
        } elseif ($minutes <= 14 * 60) {
            $percent = 40;
            $text = 'Trả phòng sau 13:00 đến 14:00, phụ thu 40% giá 1 đêm.';
        } elseif ($minutes <= 15 * 60) {
            $percent = 60;
            $text = 'Trả phòng sau 14:00 đến 15:00, phụ thu 60% giá 1 đêm.';
        } elseif ($minutes < 18 * 60) {
            $percent = 80;
            $text = 'Trả phòng sau 15:00 đến trước 18:00, phụ thu 80% giá 1 đêm.';
        } else {
            $percent = 100;
            $text = 'Trả phòng từ 18:00 trở đi, tính thêm 1 đêm.';
        }

        return [
            'percent' => $percent,
            'amount' => round($oneNightTotal * $percent / 100, 0),
            'policy_text' => $text,
        ];
    }


    public function lateArrival(Carbon $expectedArrivalAt, float $oneNightTotal, ?Carbon $cutoffAt = null): array
    {
        $cutoffAt = $cutoffAt ?: $expectedArrivalAt->copy()->setTime(18, 0, 0);

        if ($expectedArrivalAt->lessThanOrEqualTo($cutoffAt)) {
            return [
                'percent' => 0,
                'amount' => 0,
                'hours_after_cutoff' => 0,
                'policy_text' => 'Khách đến từ 14:00 đến hết giờ G 18:00, không phụ thu đến muộn.',
            ];
        }

        $minutesAfterCutoff = $cutoffAt->diffInMinutes($expectedArrivalAt);
        $hoursAfterCutoff = round($minutesAfterCutoff / 60, 2);
        $arrivalMinutes = ((int) $expectedArrivalAt->format('H')) * 60 + (int) $expectedArrivalAt->format('i');

        if ($expectedArrivalAt->toDateString() !== $cutoffAt->toDateString() || $arrivalMinutes === 0) {
            $percent = 100;
            $text = 'Khách dự kiến đến từ 00:00 ngày hôm sau, phụ thu 100% giá 1 đêm để tiếp tục giữ phòng.';
        } elseif ($arrivalMinutes <= 21 * 60) {
            $percent = 20;
            $text = 'Khách dự kiến đến sau 18:00 đến 21:00, phụ thu 20% giá 1 đêm để tiếp tục giữ phòng.';
        } else {
            $percent = 50;
            $text = 'Khách dự kiến đến sau 21:00 đến trước 00:00, phụ thu 50% giá 1 đêm để tiếp tục giữ phòng.';
        }

        return [
            'percent' => $percent,
            'amount' => round(max(0, $oneNightTotal) * $percent / 100, 0),
            'hours_after_cutoff' => $hoursAfterCutoff,
            'policy_text' => $text,
        ];
    }

    public function shortStay(float $nightPrice, int $roomQuantity, int $durationMinutes): array
    {
        $durationHours = max(1, (int) ceil($durationMinutes / 60));
        $chargedPercent = 0.5;

        if ($durationHours > 2) {
            $chargedPercent = min(0.8, 0.5 + (($durationHours - 2) * 0.1));
        }

        return [
            'duration_hours' => $durationHours,
            'charged_percent' => $chargedPercent,
            'amount' => round($nightPrice * max(1, $roomQuantity) * $chargedPercent, 0),
            'policy_text' => $durationHours <= 2
                ? 'Ở theo giờ: tối thiểu 2 giờ đầu bằng 50% giá 1 đêm.'
                : ($chargedPercent >= 0.8
                    ? 'Ở theo giờ đạt ngưỡng 80% giá 1 đêm.'
                    : 'Ở theo giờ: 2 giờ đầu 50%, mỗi giờ tiếp theo cộng 10% giá 1 đêm.'),
        ];
    }

    public function longStay(Carbon $checkInAt, Carbon $checkOutAt, float $nightPrice, int $roomQuantity): array
    {
        $oneNightTotal = $nightPrice * max(1, $roomQuantity);
        $nightCount = max(1, $checkInAt->copy()->startOfDay()->diffInDays($checkOutAt->copy()->startOfDay()));
        $baseAmount = round($oneNightTotal * $nightCount, 0);
        $early = $this->earlyCheckIn($checkInAt, $oneNightTotal);
        $late = $this->lateCheckOut($checkOutAt, $oneNightTotal);

        return [
            'night_count' => $nightCount,
            'base_amount' => $baseAmount,
            'early' => $early,
            'late' => $late,
            'surcharge_amount' => $early['amount'] + $late['amount'],
            'total_amount' => $baseAmount + $early['amount'] + $late['amount'],
            'policy_text' => 'Tự động tính theo giá qua đêm vì thời lượng vượt 12 giờ. '
                . $nightCount . ' đêm. ' . $early['policy_text'] . ' ' . $late['policy_text'],
        ];
    }
}
