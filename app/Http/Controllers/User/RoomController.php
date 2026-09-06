<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\RoomCategory;
use App\Models\HotelReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\HotelPolicyService;
use App\Services\BookingRecommendationService;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $minOnlineCheckInDate = $this->getOnlineMinCheckInDate();
        $minOnlineCheckOutDate = $this->getOnlineMinCheckOutDate($request->input('check_in_date'));
        $onlineBookingClosedToday = $this->isOnlineBookingClosedToday();

        $maxOnlineGuests = max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_guests', 60));
        $maxAdultCapacity = $maxOnlineGuests;
        $maxChildCapacity = $maxOnlineGuests;

        $hasSearchAttempt = $request->filled('check_in_date')
            || $request->filled('check_out_date')
            || $request->filled('adult_count')
            || $request->filled('child_count')
            || $request->filled('room_category_id');

        $guestCountRule = $hasSearchAttempt ? 'required' : 'nullable';

        $validator = Validator::make($request->all(), [
            'check_in_date' => 'nullable|required_with:check_out_date|date|after_or_equal:' . $minOnlineCheckInDate,
            'check_out_date' => 'nullable|required_with:check_in_date|date|after:check_in_date',
            'adult_count' => $guestCountRule . '|integer|min:1|max:' . $maxAdultCapacity,
            'child_count' => $guestCountRule . '|integer|min:0|max:' . $maxChildCapacity,
            'room_category_id' => 'nullable|exists:room_categories,id',
        ], [
            'check_in_date.required_with' => 'Vui lòng chọn ngày nhận phòng.',
            'check_in_date.date' => 'Ngày nhận phòng không hợp lệ.',
            'check_in_date.after_or_equal' => 'Đã quá mốc giữ phòng online hôm nay lúc ' . $this->standardCheckInLabel() . '. Vui lòng chọn ngày nhận phòng từ ' . Carbon::parse($minOnlineCheckInDate)->format('d/m/Y') . '.',
            'check_out_date.required_with' => 'Vui lòng chọn ngày trả phòng.',
            'check_out_date.date' => 'Ngày trả phòng không hợp lệ.',
            'check_out_date.after' => 'Ngày trả phòng phải sau ngày nhận phòng.',
            'adult_count.required' => 'Vui lòng chọn số người lớn để lọc phòng.',
            'adult_count.integer' => 'Số người lớn không hợp lệ.',
            'adult_count.min' => 'Phải có ít nhất 1 người lớn.',
            'adult_count.max' => 'Số người lớn vượt quá sức chứa tối đa hiện có trong hệ thống là ' . $maxAdultCapacity . ' người.',

            'child_count.required' => 'Vui lòng chọn số trẻ em để lọc phòng. Nếu không có trẻ em, hãy chọn 0.',
            'child_count.integer' => 'Số trẻ em không hợp lệ.',
            'child_count.min' => 'Số trẻ em không được âm.',
            'child_count.max' => 'Số trẻ em vượt quá giới hạn hiện có là ' . $maxChildCapacity . '.',
            'room_category_id.exists' => 'Hạng phòng không tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('rooms')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        if ($hasSearchAttempt && ((int) ($data['adult_count'] ?? 0) + (int) ($data['child_count'] ?? 0)) > $maxOnlineGuests) {
            return redirect()->route('rooms')->withErrors([
                'adult_count' => 'Tổng số người lớn và trẻ em không được vượt quá ' . $maxOnlineGuests . ' người trong một lần đặt.',
            ])->withInput();
        }

        $checkInDate = $data['check_in_date'] ?? null;
        $checkOutDate = $data['check_out_date'] ?? null;

        $checkInAt = $checkInDate
            ? $checkInDate . ' ' . $this->standardCheckInTime()
            : null;

        $checkOutAt = $checkOutDate
            ? $checkOutDate . ' ' . $this->standardCheckOutTime()
            : null;

        $hasFilter = $request->filled('check_in_date')
            || $request->filled('check_out_date')
            || $request->filled('adult_count')
            || $request->filled('child_count')
            || $request->filled('room_category_id');

        $hasDateFilter = $checkInDate && $checkOutDate;

        $hasCompleteBookingSearch = $hasDateFilter
            && !empty($data['adult_count'])
            && array_key_exists('child_count', $data)
            && $data['child_count'] !== null
;

        $availableRoomCondition = function ($query) use ($checkInAt, $checkOutAt) {
            if ($checkInAt && $checkOutAt) {
                $query->bookableForPeriod($checkInAt, $checkOutAt);
                return;
            }

            $query->whereNotIn('status', ['maintenance']);
        };


        $roomCategories = RoomCategory::with(['images', 'amenities'])
            ->withCount([
                'rooms as available_rooms_count' => $availableRoomCondition,
            ])
            ->where('status', 'active');

        if (!empty($data['room_category_id'])) {
            $roomCategories->where('id', $data['room_category_id']);
        }

        if ($hasDateFilter) {
            $roomCategories->whereHas('rooms', $availableRoomCondition);
        }

        $roomCategories = $roomCategories
            ->get()
            ->sortBy(function ($category) use ($data) {
                $score = 0;

                // Ưu tiên hạng phòng được chọn
                if (!empty($data['room_category_id']) && $category->id == $data['room_category_id']) {
                    $score += 1000;
                }

                // Với đoàn nhiều người, không so sức chứa của một phòng với tổng số khách.
                // Recommendation phía dưới sẽ tính số phòng tối thiểu theo tồn phòng thật.

                // Ưu tiên hạng còn nhiều phòng trống hơn.
                $score += min((int) ($category->available_rooms_count ?? 0), 10) * 2;

                return -$score;
            })
            ->values();

        $filterRoomCategories = RoomCategory::where('status', 'active')
            ->orderBy('name')
            ->get();

        $roomCategoryReviewStats = HotelReview::approved()
            ->select(
                'room_category_id',
                DB::raw('COUNT(*) as review_count'),
                DB::raw('AVG(rating) as average_rating')
            )
            ->whereIn('room_category_id', $roomCategories->pluck('id'))
            ->groupBy('room_category_id')
            ->get()
            ->keyBy('room_category_id');

        $roomRecommendations = collect();
        if ($hasCompleteBookingSearch) {
            $roomRecommendations = app(BookingRecommendationService::class)->recommend(
                $checkInAt,
                $checkOutAt,
                (int) $data['adult_count'],
                (int) ($data['child_count'] ?? 0),
                !empty($data['room_category_id']) ? (int) $data['room_category_id'] : null
            );
        }

        return view('user.pages.rooms', [
            'roomCategories' => $roomCategories,
            'filterRoomCategories' => $filterRoomCategories,
            'searchData' => [
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'early_check_in_time' => $this->earlyCheckInFreeFromLabel(),
                'check_in_time' => $this->standardCheckInLabel(),
                'check_out_time' => $this->standardCheckOutLabel(),
                'adult_count' => $data['adult_count'] ?? null,
                'child_count' => $data['child_count'] ?? null,
                'room_category_id' => $data['room_category_id'] ?? null,
            ],
            'hasFilter' => $hasFilter,
            'hasCompleteBookingSearch' => $hasCompleteBookingSearch,
            'maxAdultCapacity' => $maxAdultCapacity,
            'maxChildCapacity' => $maxChildCapacity,
            'minOnlineCheckInDate' => $minOnlineCheckInDate,
            'minOnlineCheckOutDate' => $minOnlineCheckOutDate,
            'onlineBookingClosedToday' => $onlineBookingClosedToday,
            'roomCategoryReviewStats' => $roomCategoryReviewStats,
            'roomRecommendations' => $roomRecommendations,
        ]);
    }

    public function show(RoomCategory $roomCategory)
    {
        abort_unless($roomCategory->status === 'active', 404);
        $roomCategory->load(['images', 'amenities']);

        $minOnlineCheckInDate = $this->getOnlineMinCheckInDate();
        $minOnlineCheckOutDate = $this->getOnlineMinCheckOutDate($minOnlineCheckInDate);
        $onlineBookingClosedToday = $this->isOnlineBookingClosedToday();

        $approvedReviews = HotelReview::approved()
            ->with(['customer', 'booking.roomCategory', 'replier'])
            ->where('room_category_id', $roomCategory->id)
            ->latest('approved_at')
            ->paginate(6, ['*'], 'reviews_page');

        $reviewStats = HotelReview::approved()
            ->where('room_category_id', $roomCategory->id)
            ->selectRaw('COUNT(*) as review_count, AVG(rating) as average_rating, AVG(cleanliness_rating) as cleanliness_average, AVG(location_rating) as room_quality_average, AVG(staff_rating) as staff_average, AVG(service_rating) as service_average, AVG(comfort_rating) as comfort_average, AVG(value_rating) as value_average')
            ->first();

        return view('user.pages.room-detail', compact(
            'roomCategory',
            'minOnlineCheckInDate',
            'minOnlineCheckOutDate',
            'onlineBookingClosedToday',
            'approvedReviews',
            'reviewStats'
        ));
    }

    public function availability(Request $request, RoomCategory $roomCategory)
    {
        abort_unless($roomCategory->status === 'active', 404);

        $maxGuests = max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_guests', 60));
        $maxRooms = max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_rooms', 30));
        $minCheckInDate = $this->getOnlineMinCheckInDate();

        $validator = Validator::make($request->all(), [
            'check_in_date' => 'required|date_format:Y-m-d|after_or_equal:' . $minCheckInDate,
            'check_out_date' => 'required|date_format:Y-m-d|after:check_in_date',
            'adult_count' => 'required|integer|min:1|max:' . $maxGuests,
            'child_count' => 'nullable|integer|min:0|max:' . $maxGuests,
            'room_quantity' => 'nullable|integer|min:1|max:' . $maxRooms,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $adults = (int) $data['adult_count'];
        $children = (int) ($data['child_count'] ?? 0);
        if (($adults + $children) > $maxGuests) {
            return response()->json([
                'message' => 'Tổng số người lớn và trẻ em không được vượt quá ' . $maxGuests . ' người.',
            ], 422);
        }

        $checkInAt = Carbon::parse($data['check_in_date'] . ' ' . $this->standardCheckInTime(), 'Asia/Ho_Chi_Minh');
        $checkOutAt = Carbon::parse($data['check_out_date'] . ' ' . $this->standardCheckOutTime(), 'Asia/Ho_Chi_Minh');
        $availableRooms = (int) $roomCategory->rooms()
            ->bookableForPeriod($checkInAt, $checkOutAt)
            ->count();

        $adultCapacity = max(1, (int) $roomCategory->adult_capacity);
        $childCapacity = max(0, (int) $roomCategory->child_capacity);
        $roomsForAdults = (int) ceil($adults / $adultCapacity);
        $minorCount = $children;
        $roomsForChildren = $minorCount === 0
            ? 1
            : ($childCapacity > 0 ? (int) ceil($minorCount / $childCapacity) : PHP_INT_MAX);
        $minimumRooms = max(1, $roomsForAdults, $roomsForChildren);
        $maxBookableRooms = min($availableRooms, $maxRooms, $adults);
        $capacityPossible = $roomsForChildren !== PHP_INT_MAX && $minimumRooms <= $maxRooms && $minimumRooms <= $adults;
        $inventoryEnoughForMinimum = $capacityPossible && $availableRooms >= $minimumRooms;

        $requestedRooms = isset($data['room_quantity']) ? (int) $data['room_quantity'] : null;
        $requestedEnough = $requestedRooms === null || (
            $capacityPossible
            && $requestedRooms >= $minimumRooms
            && $requestedRooms <= $maxBookableRooms
            && $adults <= $adultCapacity * $requestedRooms
            && $children <= $childCapacity * $requestedRooms
        );

        if (!$capacityPossible) {
            $message = $childCapacity < 1 && $children > 0
                ? 'Hạng phòng này không phù hợp với số khách đã chọn.'
                : 'Số khách không thể phân vào hạng phòng này.';
        } else {
            $message = 'Còn ' . $availableRooms . ' phòng';
        }

        return response()->json([
            'available_rooms' => $availableRooms,
            'minimum_rooms' => $minimumRooms === PHP_INT_MAX ? null : $minimumRooms,
            'max_bookable_rooms' => $maxBookableRooms,
            'capacity_possible' => $capacityPossible,
            'inventory_enough' => $inventoryEnoughForMinimum,
            'requested_enough' => $requestedEnough,
            'message' => $message,
        ]);
    }

    private function getOnlineMinCheckInDate(): string
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $todayCheckInDeadline = $now->copy()->setTimeFromTimeString($this->standardCheckInTime());

        if ($now->greaterThanOrEqualTo($todayCheckInDeadline)) {
            return $now->copy()->addDay()->toDateString();
        }

        return $now->toDateString();
    }

    private function getOnlineMinCheckOutDate(?string $checkInDate = null): string
    {
        $baseCheckInDate = $checkInDate ?: $this->getOnlineMinCheckInDate();

        return Carbon::parse($baseCheckInDate, 'Asia/Ho_Chi_Minh')
            ->addDay()
            ->toDateString();
    }

    private function isOnlineBookingClosedToday(): bool
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        return $now->greaterThanOrEqualTo(
            $now->copy()->setTimeFromTimeString($this->standardCheckInTime())
        );
    }
    private function standardCheckInLabel(): string
    {
        return (string) app(HotelPolicyService::class)->get('stay.standard_check_in_time', '14:00');
    }

    private function standardCheckOutLabel(): string
    {
        return (string) app(HotelPolicyService::class)->get('stay.standard_check_out_time', '12:00');
    }

    private function earlyCheckInFreeFromLabel(): string
    {
        return (string) app(HotelPolicyService::class)->get('stay.early_checkin_free_from', '12:00');
    }

    private function standardCheckInTime(): string
    {
        return $this->standardCheckInLabel() . ':00';
    }

    private function standardCheckOutTime(): string
    {
        return $this->standardCheckOutLabel() . ':00';
    }

}
