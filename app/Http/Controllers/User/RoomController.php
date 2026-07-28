<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\RoomCategory;
use App\Models\HotelReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoomController extends Controller
{
    private const ONLINE_CHECK_IN_TIME = '14:00:00';
    private const ONLINE_CHECK_OUT_TIME = '12:00:00';
    private const ONLINE_CHECK_IN_LABEL = '14:00';
    private const EARLY_CHECK_IN_LABEL = '13:00';
    private const ONLINE_CHECK_OUT_LABEL = '12:00';

    public function index(Request $request)
    {
        $minOnlineCheckInDate = $this->getOnlineMinCheckInDate();
        $minOnlineCheckOutDate = $this->getOnlineMinCheckOutDate($request->input('check_in_date'));
        $onlineBookingClosedToday = $this->isOnlineBookingClosedToday();

        $maxAdultCapacity = max(
            1,
            (int) RoomCategory::where('status', 'active')->max('adult_capacity')
        );

        $maxChildCapacity = max(
            0,
            (int) RoomCategory::where('status', 'active')->max('child_capacity')
        );

        /*
        |--------------------------------------------------------------------------
        | Nếu user chọn hạng phòng cụ thể:
        | - Tự lấy sức chứa hạng đó làm số người lớn/trẻ em để lọc
        | - Không bắt khách tự chọn số lượng nữa
        |--------------------------------------------------------------------------
        */
        if ($request->filled('room_category_id')) {
            $selectedCategory = RoomCategory::where('status', 'active')
                ->find($request->input('room_category_id'));

            if ($selectedCategory) {
                $request->merge([
                    'adult_count' => (int) $selectedCategory->adult_capacity,
                    'child_count' => (int) $selectedCategory->child_capacity,
                ]);
            }
        }

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
            'check_in_date.after_or_equal' => 'Đã quá mốc giữ phòng online hôm nay lúc ' . self::ONLINE_CHECK_IN_LABEL . '. Vui lòng chọn ngày nhận phòng từ ' . Carbon::parse($minOnlineCheckInDate)->format('d/m/Y') . '.',
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
            'child_count.max' => 'Số trẻ em vượt quá sức chứa tối đa hiện có trong hệ thống là ' . $maxChildCapacity . ' trẻ em.',
            'room_category_id.exists' => 'Hạng phòng không tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('rooms')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        $checkInDate = $data['check_in_date'] ?? null;
        $checkOutDate = $data['check_out_date'] ?? null;

        $checkInAt = $checkInDate
            ? $checkInDate . ' ' . self::ONLINE_CHECK_IN_TIME
            : null;

        $checkOutAt = $checkOutDate
            ? $checkOutDate . ' ' . self::ONLINE_CHECK_OUT_TIME
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
            && $data['child_count'] !== null;

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

        if (!empty($data['adult_count'])) {
            $roomCategories->where('adult_capacity', '>=', $data['adult_count']);
        }

        if (array_key_exists('child_count', $data) && $data['child_count'] !== null) {
            $roomCategories->where('child_capacity', '>=', $data['child_count']);
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

                // Ưu tiên phòng có sức chứa gần với số người được chọn
                if (!empty($data['adult_count'])) {
                    $adultDiff = abs($category->adult_capacity - $data['adult_count']);
                    $score -= $adultDiff * 10;
                }

                if (array_key_exists('child_count', $data) && $data['child_count'] !== null) {
                    $childDiff = abs($category->child_capacity - $data['child_count']);
                    $score -= $childDiff * 5;
                }

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

        return view('user.pages.rooms', [
            'roomCategories' => $roomCategories,
            'filterRoomCategories' => $filterRoomCategories,
            'searchData' => [
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'early_check_in_time' => self::EARLY_CHECK_IN_LABEL,
                'check_in_time' => self::ONLINE_CHECK_IN_LABEL,
                'check_out_time' => self::ONLINE_CHECK_OUT_LABEL,
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
        ]);
    }

    public function show(RoomCategory $roomCategory)
    {
        $roomCategory->load(['images', 'amenities', 'rooms']);

        $minOnlineCheckInDate = $this->getOnlineMinCheckInDate();
        $minOnlineCheckOutDate = $this->getOnlineMinCheckOutDate($minOnlineCheckInDate);
        $onlineBookingClosedToday = $this->isOnlineBookingClosedToday();

        // Các ngày hạng phòng đã kín hoàn toàn trong 90 ngày tới.
        // Đây là lớp hỗ trợ giao diện; lúc xác nhận và thanh toán hệ thống vẫn kiểm tra lại để chống trùng lịch.
        $fullyBookedDates = [];
        $cursor = Carbon::parse($minOnlineCheckInDate, 'Asia/Ho_Chi_Minh');
        for ($i = 0; $i < 90; $i++) {
            $date = $cursor->copy()->addDays($i);
            $checkInAt = $date->copy()->setTime(14, 0);
            $checkOutAt = $date->copy()->addDay()->setTime(12, 0);
            $hasRoom = $roomCategory->rooms()
                ->bookableForPeriod($checkInAt, $checkOutAt)
                ->exists();
            if (!$hasRoom) {
                $fullyBookedDates[] = $date->toDateString();
            }
        }

        $approvedReviews = HotelReview::approved()
            ->with(['customer', 'booking.roomCategory', 'replier'])
            ->where('room_category_id', $roomCategory->id)
            ->latest('approved_at')
            ->paginate(6, ['*'], 'reviews_page');

        $reviewStats = HotelReview::approved()
            ->where('room_category_id', $roomCategory->id)
            ->selectRaw('COUNT(*) as review_count, AVG(rating) as average_rating, AVG(cleanliness_rating) as cleanliness_average, AVG(service_rating) as service_average, AVG(location_rating) as location_average, AVG(value_rating) as value_average')
            ->first();

        return view('user.pages.room-detail', compact(
            'roomCategory',
            'minOnlineCheckInDate',
            'minOnlineCheckOutDate',
            'onlineBookingClosedToday',
            'fullyBookedDates',
            'approvedReviews',
            'reviewStats'
        ));
    }

    private function getOnlineMinCheckInDate(): string
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $todayCheckInDeadline = $now->copy()->setTimeFromTimeString(self::ONLINE_CHECK_IN_TIME);

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
            $now->copy()->setTimeFromTimeString(self::ONLINE_CHECK_IN_TIME)
        );
    }
}
