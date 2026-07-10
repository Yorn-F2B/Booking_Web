<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Booking;
use App\Models\BookingRoom;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RoomDailyController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->get('date', now('Asia/Ho_Chi_Minh')->format('Y-m-d'));
        $selectedDateCarbon = Carbon::parse($selectedDate, 'Asia/Ho_Chi_Minh')->startOfDay();
        $selectedCategory = $request->get('category', 'all');

        $roomCategories = RoomCategory::where('status', 'active')
            ->orderBy('name')
            ->get();

        $roomsQuery = Room::with(['category', 'bookingRooms.booking'])
            ->where('status', '!=', 'deleted');

        if ($selectedCategory !== 'all') {
            $roomsQuery->where('room_category_id', $selectedCategory);
        }

        $rooms = $roomsQuery->orderBy('room_number')->get();

        // Calculate room status for selected date
        $roomStatuses = [];

        foreach ($rooms as $room) {
            $status = $this->calculateRoomStatus($room, $selectedDateCarbon);
            $roomStatuses[$room->id] = $status;
        }

        return view('admin.pages.rooms.daily', compact(
            'selectedDate',
            'selectedDateCarbon',
            'selectedCategory',
            'roomCategories',
            'rooms',
            'roomStatuses'
        ));
    }

    private function calculateRoomStatus(Room $room, Carbon $date): array
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        // Check if room is under maintenance
        if ($room->status === 'maintenance') {
            return [
                'status' => 'maintenance',
                'label' => 'Bảo trì',
                'color' => 'bg-danger',
                'icon' => '🔧',
                'booking' => null,
            ];
        }

        // Find bookings for this room on the selected date
        $bookingRooms = BookingRoom::where('room_id', $room->id)
            ->whereHas('booking', function ($query) use ($dayStart, $dayEnd) {
                $query->whereNotIn('status', ['cancelled', 'no_show'])
                    ->where(function ($q) use ($dayStart, $dayEnd) {
                        $q->whereBetween('check_in_at', [$dayStart, $dayEnd])
                            ->orWhereBetween('check_out_at', [$dayStart, $dayEnd])
                            ->orWhere(function ($subQ) use ($dayStart, $dayEnd) {
                                $subQ->where('check_in_at', '<=', $dayStart)
                                    ->where('check_out_at', '>=', $dayEnd);
                            });
                    });
            })
            ->with('booking')
            ->get();

        if ($bookingRooms->isEmpty()) {
            return [
                'status' => 'available',
                'label' => 'Trống',
                'color' => 'bg-success',
                'icon' => '✅',
                'booking' => null,
            ];
        }

        // Check the most relevant booking for this day
        $bookingRoom = $bookingRooms->first();
        $booking = $bookingRoom->booking;

        $now = now('Asia/Ho_Chi_Minh');
        $checkInAt = Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $checkOutAt = Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

        // If the selected date is today, check real-time status
        if ($date->isSameDay($now)) {
            if ($booking->status === 'checked_in') {
                return [
                    'status' => 'occupied',
                    'label' => 'Đang sử dụng',
                    'color' => 'bg-primary',
                    'icon' => '🏨',
                    'booking' => $booking,
                ];
            }

            if ($booking->status === 'confirmed' && $now->gte($checkInAt) && $now->lt($checkOutAt)) {
                return [
                    'status' => 'awaiting_checkin',
                    'label' => 'Chờ nhận phòng',
                    'color' => 'bg-info',
                    'icon' => '⏰',
                    'booking' => $booking,
                ];
            }
        }

        // For past or future dates
        if ($date->lt($now->startOfDay())) {
            return [
                'status' => 'past',
                'label' => 'Đã sử dụng',
                'color' => 'bg-secondary',
                'icon' => '📅',
                'booking' => $booking,
            ];
        }

        if ($date->gt($now->endOfDay())) {
            return [
                'status' => 'booked',
                'label' => 'Đã đặt',
                'color' => 'bg-warning',
                'icon' => '📋',
                'booking' => $booking,
            ];
        }

        // Default for today but not checked in yet
        return [
            'status' => 'booked',
            'label' => 'Đã đặt',
            'color' => 'bg-warning',
            'icon' => '📋',
            'booking' => $booking,
        ];
    }
}
