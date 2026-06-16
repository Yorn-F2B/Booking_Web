<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomCategory;
use Illuminate\Http\Request;

class RoomAvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $roomCategories = collect();

        $data = $request->validate([
            'check_in_date' => 'nullable|date|after_or_equal:today',
            'check_out_date' => 'nullable|date|after:check_in_date',
        ], [
            'check_in_date.date' => 'Ngày nhận phòng không hợp lệ.',
            'check_in_date.after_or_equal' => 'Ngày nhận phòng không được nhỏ hơn hôm nay.',
            'check_out_date.date' => 'Ngày trả phòng không hợp lệ.',
            'check_out_date.after' => 'Ngày trả phòng phải sau ngày nhận phòng.',
        ]);

        $checkInDate = $data['check_in_date'] ?? null;
        $checkOutDate = $data['check_out_date'] ?? null;

        if ($checkInDate && $checkOutDate) {
            $checkInAt = $checkInDate . ' 14:00:00';
            $checkOutAt = $checkOutDate . ' 11:00:00';

            $roomCategories = RoomCategory::withCount([
                'rooms as available_rooms_count' => function ($query) use ($checkInAt, $checkOutAt) {
                    $query->where('status', 'available')
                        ->whereDoesntHave('bookingRooms.booking', function ($bookingQuery) use ($checkInAt, $checkOutAt) {
                            $bookingQuery->whereIn('status', [
                                'pending',
                                'confirmed',
                                'checked_in',
                            ])
                                ->where('check_in_at', '<', $checkOutAt)
                                ->where('check_out_at', '>', $checkInAt);
                        });
                },
            ])
                ->where('status', 'active')
                ->having('available_rooms_count', '>', 0)
                ->get();
        }

        return view('admin.pages.room-availability.index', [
            'roomCategories' => $roomCategories,
            'searchData' => [
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
            ],
        ]);
    }
}