<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;

use App\Http\Controllers\Controller;
use App\Models\RoomCategory;
use App\Services\HotelPolicyService;
use App\Services\BookingRecommendationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoomAvailabilityController extends Controller
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';
    private const DEFAULT_CLEANING_BUFFER_MINUTES = Booking::DEFAULT_CLEANING_BUFFER_MINUTES;
    private const DEFAULT_LOOKUP_DURATION_HOURS = 2;

    public function index(Request $request)
    {
        $now = now(self::TIMEZONE)->startOfMinute();
        $policies = app(HotelPolicyService::class);
        $cleaningBufferMinutes = max(0, (int) $policies->get('booking.cleaning_buffer_minutes', self::DEFAULT_CLEANING_BUFFER_MINUTES));
        $currentCheckInAt = $now->copy();
        $standardCheckOutTime = (string) $policies->get('stay.standard_check_out_time', '12:00');
        [$defaultCheckOutHour, $defaultCheckOutMinute] = array_pad(array_map('intval', explode(':', $standardCheckOutTime, 2)), 2, 0);
        $defaultCheckOutAt = $currentCheckInAt->copy()->addDay()->setTime($defaultCheckOutHour, $defaultCheckOutMinute, 0);

        $roomCategories = collect();

        $hasSearch = $request->hasAny([
            'check_in_date','check_in_time','check_out_date','check_out_time','adult_count','child_count'
        ]);

        $searchData = [
            'searched' => false,
            'check_in_date' => $request->input('check_in_date', $currentCheckInAt->toDateString()),
            'check_in_time' => $request->input('check_in_time', $currentCheckInAt->format('H:i')),
            'check_out_date' => $request->input('check_out_date', $defaultCheckOutAt->toDateString()),
            'check_out_time' => $request->input('check_out_time', $defaultCheckOutAt->format('H:i')),
            'check_in_at' => null,
            'check_out_at' => null,
            'cleaning_buffer_minutes' => $cleaningBufferMinutes,
            'quick_booking_type' => 'hourly',
            'quick_booking_mode' => 'walk_in',
            'quick_booking_available' => true,
            'adult_count' => max(1, (int) $request->input('adult_count', 2)),
            'child_count' => max(0, (int) $request->input('child_count', 0)),
            'recommendations' => collect(),
        ];

        $uiData = [
            'today' => $now->toDateString(),
            'rounded_now_date' => $currentCheckInAt->toDateString(),
            'rounded_now_time' => $currentCheckInAt->format('H:i'),
            'current_timestamp_ms' => $currentCheckInAt->getTimestampMs(),
            'auto_current_check_in' => !$hasSearch,
            'default_checkout_date' => $defaultCheckOutAt->toDateString(),
            'default_checkout_time' => $defaultCheckOutAt->format('H:i'),
            'cleaning_buffer_minutes' => $cleaningBufferMinutes,
            'max_online_guests' => max(1, (int) $policies->get('booking.max_online_guests', 60)),
        ];

        if (!$hasSearch) {
            return view('admin.pages.room-availability.index', compact('roomCategories', 'searchData', 'uiData'));
        }

        $validator = Validator::make($request->all(), [
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_date' => 'required|date|after_or_equal:check_in_date',
            'check_out_time' => 'required|date_format:H:i',
            'adult_count' => 'required|integer|min:1|max:' . max(1, (int) $policies->get('booking.max_online_guests', 60)),
            'child_count' => 'required|integer|min:0|max:' . max(1, (int) $policies->get('booking.max_online_guests', 60)),
        ], [
            'check_in_date.required' => 'Vui lòng chọn ngày nhận phòng.',
            'check_in_date.date' => 'Ngày nhận phòng không hợp lệ.',
            'check_in_date.after_or_equal' => 'Ngày nhận phòng không được nhỏ hơn hôm nay.',
            'check_in_time.required' => 'Vui lòng chọn giờ nhận phòng.',
            'check_in_time.date_format' => 'Giờ nhận phòng phải đúng định dạng 24 giờ, ví dụ 14:00.',
            'check_out_date.required' => 'Vui lòng chọn ngày trả phòng.',
            'check_out_date.date' => 'Ngày trả phòng không hợp lệ.',
            'check_out_date.after_or_equal' => 'Ngày trả phòng không được nhỏ hơn ngày nhận phòng.',
            'check_out_time.required' => 'Vui lòng chọn giờ trả phòng.',
            'check_out_time.date_format' => 'Giờ trả phòng phải đúng định dạng 24 giờ, ví dụ 12:00.',
        ]);

        $validator->after(function ($validator) use ($request, $now) {
            if (
                !$request->filled('check_in_date')
                || !$request->filled('check_in_time')
                || !$request->filled('check_out_date')
                || !$request->filled('check_out_time')
            ) {
                return;
            }

            try {
                $checkInAt = Carbon::parse(
                    $request->input('check_in_date') . ' ' . $request->input('check_in_time') . ':00',
                    self::TIMEZONE
                );

                $checkOutAt = Carbon::parse(
                    $request->input('check_out_date') . ' ' . $request->input('check_out_time') . ':00',
                    self::TIMEZONE
                );
            } catch (\Throwable $exception) {
                $validator->errors()->add('check_in_date', 'Khoảng thời gian tra cứu không hợp lệ.');
                return;
            }

            if ($checkInAt->lt($now)) {
                $validator->errors()->add(
                    'check_in_time',
                    'Thời gian nhận phòng không được nhỏ hơn thời điểm hiện tại.'
                );
            }

            if ($checkOutAt->lessThanOrEqualTo($checkInAt)) {
                $validator->errors()->add(
                    'check_out_time',
                    'Thời gian trả phòng phải sau thời gian nhận phòng.'
                );
            }
        });

        $maxOnlineGuests = max(1, (int) $policies->get('booking.max_online_guests', 60));
        $validator->after(function ($validator) use ($request, $maxOnlineGuests) {
            $totalGuests = max(0, (int) $request->input('adult_count', 0)) + max(0, (int) $request->input('child_count', 0));
            if ($totalGuests > $maxOnlineGuests) {
                $validator->errors()->add('adult_count', 'Tổng số người lớn và trẻ em không được vượt quá ' . $maxOnlineGuests . ' người trong một lần tra cứu/đặt phòng.');
            }
        });

        if ($validator->fails()) {
            // This is a GET lookup page: keep validation errors local to this
            // response instead of flashing them into the session. Flashing a
            // GET validation bag can make the same error surface on another
            // admin page opened immediately afterwards.
            return view('admin.pages.room-availability.index', compact('roomCategories', 'searchData', 'uiData'))
                ->withErrors($validator);
        }

        $data = $validator->validated();

        $checkInAt = Carbon::parse(
            $data['check_in_date'] . ' ' . $data['check_in_time'] . ':00',
            self::TIMEZONE
        );

        $checkOutAt = Carbon::parse(
            $data['check_out_date'] . ' ' . $data['check_out_time'] . ':00',
            self::TIMEZONE
        );

        $roomCategories = RoomCategory::withCount([
            'rooms as available_rooms_count' => function ($query) use ($checkInAt, $checkOutAt) {
                $query->bookableForPeriod($checkInAt, $checkOutAt);
            },
        ])
            ->where('status', 'active')
            ->orderBy('price')
            ->get();

        $recommendations = app(BookingRecommendationService::class)->recommend(
            $checkInAt->toDateTimeString(), $checkOutAt->toDateTimeString(),
            (int) $data['adult_count'], (int) $data['child_count']
        );

        $quickBookingType = $this->guessQuickBookingType($checkInAt, $checkOutAt, $policies);
        $quickBookingMode = $quickBookingType === 'overnight' ? 'advance' : 'walk_in';
        $quickBookingAvailable = $quickBookingMode !== 'walk_in' || $checkInAt->isSameDay($now);

        $searchData = [
            'searched' => true,
            'check_in_date' => $checkInAt->toDateString(),
            'check_in_time' => $checkInAt->format('H:i'),
            'check_out_date' => $checkOutAt->toDateString(),
            'check_out_time' => $checkOutAt->format('H:i'),
            'check_in_at' => $checkInAt,
            'check_out_at' => $checkOutAt,
            'cleaning_buffer_minutes' => $cleaningBufferMinutes,
            'quick_booking_type' => $quickBookingType,
            'quick_booking_mode' => $quickBookingMode,
            'quick_booking_available' => $quickBookingAvailable,
            'adult_count' => (int) $data['adult_count'],
            'child_count' => (int) $data['child_count'],
            'recommendations' => $recommendations,
        ];

        return view('admin.pages.room-availability.index', compact('roomCategories', 'searchData', 'uiData'));
    }

    private function roundUpTime(Carbon $time, int $stepMinutes = 15): Carbon
    {
        $time->second(0);

        $minute = (int) $time->format('i');
        $roundedMinute = (int) ceil($minute / $stepMinutes) * $stepMinutes;

        if ($roundedMinute >= 60) {
            return $time->addHour()->minute(0);
        }

        return $time->minute($roundedMinute);
    }

    private function guessQuickBookingType(Carbon $checkInAt, Carbon $checkOutAt, HotelPolicyService $policies): string
    {
        $standardCheckIn = (string) $policies->get('stay.standard_check_in_time', '14:00');
        $standardCheckOut = (string) $policies->get('stay.standard_check_out_time', '12:00');
        $isStandardOvernight = $checkInAt->format('H:i') === $standardCheckIn
            && $checkOutAt->format('H:i') === $standardCheckOut
            && $checkOutAt->copy()->startOfDay()->greaterThan($checkInAt->copy()->startOfDay());

        return $isStandardOvernight ? 'overnight' : 'hourly';
    }
}
