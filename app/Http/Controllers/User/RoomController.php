<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\RoomCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'check_in_date' => 'nullable|required_with:check_out_date|date|after_or_equal:today',
            'check_out_date' => 'nullable|required_with:check_in_date|date|after:check_in_date',
            'adult_count' => 'nullable|integer|min:1|max:20',
            'child_count' => 'nullable|integer|min:0|max:10',
            'room_category_id' => 'nullable|exists:room_categories,id',
        ], [
            'check_in_date.required_with' => 'Vui lòng chọn ngày nhận phòng.',
            'check_in_date.date' => 'Ngày nhận phòng không hợp lệ.',
            'check_in_date.after_or_equal' => 'Ngày nhận phòng không được nhỏ hơn hôm nay.',
            'check_out_date.required_with' => 'Vui lòng chọn ngày trả phòng.',
            'check_out_date.date' => 'Ngày trả phòng không hợp lệ.',
            'check_out_date.after' => 'Ngày trả phòng phải sau ngày nhận phòng.',
            'adult_count.integer' => 'Số người lớn không hợp lệ.',
            'adult_count.min' => 'Phải có ít nhất 1 người lớn.',
            'adult_count.max' => 'Số người lớn quá lớn.',
            'child_count.integer' => 'Số trẻ em không hợp lệ.',
            'child_count.min' => 'Số trẻ em không được âm.',
            'child_count.max' => 'Số trẻ em quá lớn.',
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
            ? $checkInDate . ' 14:00:00'
            : null;

        $checkOutAt = $checkOutDate
            ? $checkOutDate . ' 11:00:00'
            : null;

        $hasFilter = $request->filled('check_in_date')
            || $request->filled('check_out_date')
            || $request->filled('adult_count')
            || $request->filled('child_count')
            || $request->filled('room_category_id');

        $hasDateFilter = $checkInDate && $checkOutDate;

        $availableRoomCondition = function ($query) use ($checkInAt, $checkOutAt) {
            $query->where('status', 'available');

            if ($checkInAt && $checkOutAt) {
                $query->whereDoesntHave('bookingRooms.booking', function ($bookingQuery) use ($checkInAt, $checkOutAt) {
                    $bookingQuery->whereIn('status', [
                        'pending',
                        'confirmed',
                        'checked_in',
                    ])
                        ->where('check_in_at', '<', $checkOutAt)
                        ->where('check_out_at', '>', $checkInAt);
                });
            }
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
            ->latest()
            ->get();

        $filterRoomCategories = RoomCategory::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('user.pages.rooms', [
            'roomCategories' => $roomCategories,
            'filterRoomCategories' => $filterRoomCategories,
            'searchData' => [
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'adult_count' => $data['adult_count'] ?? null,
                'child_count' => $data['child_count'] ?? null,
                'room_category_id' => $data['room_category_id'] ?? null,
            ],
            'hasFilter' => $hasFilter,
        ]);
    }

    public function show(RoomCategory $roomCategory)
    {
        $roomCategory->load(['images', 'amenities', 'rooms']);

        return view('user.pages.room-detail', compact('roomCategory'));
    }
}