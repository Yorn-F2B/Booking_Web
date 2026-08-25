<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HotelPolicy;
use App\Services\HotelPolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HotelPolicyController extends Controller
{
    private const GROUP_LABELS = [
        'booking' => 'Đặt phòng',
        'payment' => 'Cọc & thanh toán',
        'stay' => 'Nhận / trả phòng & lưu trú',
        'room_issue' => 'Sự cố phòng',
        'housekeeping' => 'Buồng phòng',
        'chat' => 'Tin nhắn',
    ];

    public function index(HotelPolicyService $policies)
    {
        $rows = HotelPolicy::query()->orderBy('sort_order')->orderBy('id')->get();
        $groups = $rows->groupBy('policy_group');

        return view('admin.pages.policies.index', [
            'groups' => $groups,
            'groupLabels' => self::GROUP_LABELS,
            'definitions' => $policies->definitions(),
        ]);
    }

    public function update(Request $request, HotelPolicyService $service)
    {
        $data = $request->validate([
            'values' => 'required|array',
            'values.*' => 'nullable|string|max:5000',
        ]);

        $allRows = HotelPolicy::query()->orderBy('id')->get();
        $rowsById = $allRows->keyBy('id');
        $proposedByKey = $allRows->mapWithKeys(fn (HotelPolicy $row) => [$row->key => (string) $row->value])->all();

        foreach ($data['values'] as $id => $rawValue) {
            $row = $rowsById->get((int) $id);
            if (!$row) {
                continue;
            }

            $value = trim((string) $rawValue);
            $this->validatePolicyValue($row, $value, (int) $id);
            $proposedByKey[$row->key] = $value;
        }

        $this->validatePolicyRelationships($proposedByKey, $allRows);

        DB::transaction(function () use ($data, $rowsById) {
            foreach ($data['values'] as $id => $rawValue) {
                $row = $rowsById->get((int) $id);
                if (!$row) {
                    continue;
                }

                $row->update(['value' => trim((string) $rawValue)]);
            }
        });

        $service->flush();

        return back()->with('success', 'Đã cập nhật chính sách. Booking mới dùng mức mới; booking đã có snapshot giữ nguyên chính sách đã chốt.');
    }

    private function validatePolicyValue(HotelPolicy $policy, string $value, int $id): void
    {
        $fail = fn (string $message) => throw ValidationException::withMessages([
            'values.' . $id => $policy->label . ': ' . $message,
        ]);

        if ($value === '') {
            $fail('không được để trống.');
        }

        if ($policy->type === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $fail('phải là số nguyên.');
        }

        if ($policy->type === 'decimal' && !is_numeric($value)) {
            $fail('phải là số.');
        }

        if ($policy->type === 'boolean' && !in_array(strtolower($value), ['0', '1', 'true', 'false'], true)) {
            $fail('phải là true/false hoặc 1/0.');
        }

        if ($policy->type === 'time' && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
            $fail('phải theo định dạng HH:MM.');
        }

        if ($policy->type === 'json' && json_decode($value, true) === null && strtolower($value) !== 'null') {
            $fail('JSON không hợp lệ.');
        }

        if (str_contains($policy->key, 'percent') && ((float) $value < 0 || (float) $value > 100)) {
            $fail('phần trăm phải từ 0 đến 100.');
        }

        if ((str_contains($policy->key, 'minutes') || str_contains($policy->key, 'hours') || str_contains($policy->key, 'age')) && (float) $value < 0) {
            $fail('không được là số âm.');
        }

        $numericLimits = [
            'booking.min_age' => [16, 100],
            'booking.cleaning_buffer_minutes' => [0, 1440],
            'booking.hourly_cancel_grace_minutes' => [0, 1440],
            'booking.manual_room_selection_fee' => [0, 10000000],
            'payment.vnpay_expire_minutes' => [5, 10080],
            'payment.admin_vnpay_expire_minutes' => [5, 43200],
            'stay.late_checkout_free_minutes' => [0, 180],
            'stay.late_arrival_grace_minutes' => [0, 1440],
            'stay.rescheduled_after_cutoff_grace_minutes' => [0, 2880],
            'stay.priority_cleaning_window_minutes' => [15, 1440],
            'stay.late_arrival_form_expire_minutes' => [5, 10080],
            'stay.short_stay_min_minutes' => [15, 1440],
            'stay.short_stay_to_overnight_hours' => [1, 48],
            'stay.short_stay_base_hours' => [1, 23],
            'room_issue.proposal_hold_minutes' => [1, 1440],
            'housekeeping.slow_room_alert_minutes' => [15, 10080],
            'chat.archive_retention_days' => [30, 3650],
        ];

        if (isset($numericLimits[$policy->key]) && is_numeric($value)) {
            [$min, $max] = $numericLimits[$policy->key];
            if ((float) $value < $min || (float) $value > $max) {
                $fail('phải nằm trong khoảng ' . $min . ' đến ' . $max . '.');
            }
        }
    }

    private function validatePolicyRelationships(array $values, $rows): void
    {
        $idsByKey = $rows->mapWithKeys(fn (HotelPolicy $row) => [$row->key => (int) $row->id]);
        $fail = function (string $key, string $message) use ($idsByKey): never {
            $field = 'values.' . ($idsByKey[$key] ?? 'policy');
            throw ValidationException::withMessages([$field => $message]);
        };

        $minutes = static function (string $time): int {
            [$hour, $minute] = array_map('intval', explode(':', $time));
            return $hour * 60 + $minute;
        };

        $early1 = $minutes((string) ($values['stay.early_checkin_tier1_end'] ?? '06:00'));
        $early2 = $minutes((string) ($values['stay.early_checkin_tier2_end'] ?? '09:00'));
        $earlyFree = $minutes((string) ($values['stay.early_checkin_free_from'] ?? '12:00'));
        $standardIn = $minutes((string) ($values['stay.standard_check_in_time'] ?? '14:00'));
        if (!($early1 <= $early2 && $early2 <= $earlyFree && $earlyFree <= $standardIn)) {
            $fail('stay.early_checkin_tier1_end', 'Các mốc check-in sớm phải tăng dần và không được vượt giờ check-in tiêu chuẩn.');
        }

        $standardOut = $minutes((string) ($values['stay.standard_check_out_time'] ?? '12:00'));
        $late1 = $minutes((string) ($values['stay.late_checkout_tier1_end'] ?? '13:00'));
        $late2 = $minutes((string) ($values['stay.late_checkout_tier2_end'] ?? '14:00'));
        $late3 = $minutes((string) ($values['stay.late_checkout_tier3_end'] ?? '15:00'));
        $lateFull = $minutes((string) ($values['stay.late_checkout_full_night_from'] ?? '18:00'));
        if (!($standardOut <= $late1 && $late1 <= $late2 && $late2 <= $late3 && $late3 <= $lateFull)) {
            $fail('stay.late_checkout_tier1_end', 'Các mốc check-out muộn phải tăng dần từ giờ check-out tiêu chuẩn đến mốc tính thêm đêm.');
        }

        $grace = (int) ($values['stay.late_checkout_free_minutes'] ?? 0);
        if ($standardOut + $grace > $late1) {
            $fail('stay.late_checkout_free_minutes', 'Ân hạn check-out không được kéo dài qua mốc phụ thu đầu tiên.');
        }

        $directCancel = $minutes((string) ($values['booking.direct_cancel_cutoff_time'] ?? '14:00'));
        $arrivalCutoff = $minutes((string) ($values['stay.late_arrival_cutoff_time'] ?? '18:00'));
        if ($directCancel > $arrivalCutoff) {
            $fail('booking.direct_cancel_cutoff_time', 'Mốc hủy trực tiếp không được sau giờ G giữ phòng.');
        }

        $arrivalTier1 = $minutes((string) ($values['stay.late_arrival_tier1_end'] ?? '21:00'));
        if ($arrivalTier1 <= $arrivalCutoff) {
            $fail('stay.late_arrival_tier1_end', 'Mốc đến muộn mức 1 phải sau giờ G giữ phòng.');
        }

        $baseHours = (int) ($values['stay.short_stay_base_hours'] ?? 2);
        $overnightThreshold = (int) ($values['stay.short_stay_to_overnight_hours'] ?? 12);
        if ($baseHours > $overnightThreshold) {
            $fail('stay.short_stay_base_hours', 'Số giờ cơ bản không được lớn hơn ngưỡng chuyển sang qua đêm.');
        }

        $basePercent = (float) ($values['stay.short_stay_base_percent'] ?? 50);
        $maxPercent = (float) ($values['stay.short_stay_max_percent'] ?? 80);
        if ($maxPercent < $basePercent) {
            $fail('stay.short_stay_max_percent', 'Trần giá ở theo giờ không được thấp hơn giá gói giờ cơ bản.');
        }
    }
}
