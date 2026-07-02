<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RoomDailyViewController extends Controller
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function index(Request $request)
    {
        $today = now(self::TIMEZONE)->toDateString();
        $nowTime = now(self::TIMEZONE)->format('H:i');

        $selectedDate = $request->input('date', $today);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = $today;
        }

        // Time range filter (optional)
        $timeFrom = $request->input('time_from', '');
        $timeTo   = $request->input('time_to', '');
        $hasTimeFilter = $timeFrom !== '' && $timeTo !== '';

        // Validate time format
        if ($hasTimeFilter) {
            if (!preg_match('/^\d{2}:\d{2}$/', $timeFrom) || !preg_match('/^\d{2}:\d{2}$/', $timeTo)) {
                $hasTimeFilter = false;
                $timeFrom = '';
                $timeTo = '';
            }
        }

        $isToday = $selectedDate === $today;

        // Build the time window to query
        if ($hasTimeFilter) {
            $windowStart = Carbon::parse($selectedDate . ' ' . $timeFrom . ':00', self::TIMEZONE);
            $windowEnd   = Carbon::parse($selectedDate . ' ' . $timeTo . ':00', self::TIMEZONE);
            // If to <= from, assume next day wrap (e.g. overnight 22:00–02:00)
            if ($windowEnd->lte($windowStart)) {
                $windowEnd->addDay();
            }
        } else {
            $windowStart = Carbon::parse($selectedDate . ' 00:00:00', self::TIMEZONE);
            $windowEnd   = Carbon::parse($selectedDate . ' 23:59:59', self::TIMEZONE);
        }

        // Load all rooms
        $rooms = Room::with('category')
            ->orderByDesc('floor_number')
            ->orderByRaw('CAST(room_number AS UNSIGNED) ASC')
            ->orderBy('room_number')
            ->get();

        // Booking statuses to include
        $bookingStatuses = ['pending', 'confirmed', 'checked_in', 'inspection_requested'];
        if (!$isToday || $hasTimeFilter) {
            $bookingStatuses = array_merge($bookingStatuses, ['checked_out', 'completed']);
        }

        // Query bookings overlapping the window
        $bookings = Booking::with(['bookingRooms', 'customer'])
            ->whereIn('status', $bookingStatuses)
            ->where('check_in_at', '<', $windowEnd)
            ->where('check_out_at', '>', $windowStart)
            ->get();

        // Map room_id → bookings
        $roomBookingMap = [];
        foreach ($bookings as $booking) {
            foreach ($booking->bookingRooms as $br) {
                $roomBookingMap[$br->room_id][] = $booking;
            }
        }

        $rooms = $rooms->map(function ($room) use ($roomBookingMap, $isToday, $hasTimeFilter) {
            $dailyStatus = $this->resolveDailyStatus(
                $room,
                $roomBookingMap[$room->id] ?? [],
                $isToday,
                $hasTimeFilter
            );
            $room->daily_status   = $dailyStatus['status'];
            $room->daily_bookings = $dailyStatus['bookings'] ?? [];
            return $room;
        });

        $roomsByFloor = $rooms->groupBy(fn($r) => $r->floor_number ?: 'unknown');

        $summary = [
            'total'       => $rooms->count(),
            'available'   => $rooms->where('daily_status', 'available')->count(),
            'reserved'    => $rooms->where('daily_status', 'reserved')->count(),
            'occupied'    => $rooms->where('daily_status', 'occupied')->count(),
            'cleaning'    => $rooms->where('daily_status', 'cleaning')->count(),
            'maintenance' => $rooms->where('daily_status', 'maintenance')->count(),
            'inspection'  => $rooms->where('daily_status', 'inspection')->count(),
        ];

        return view('admin.pages.room-daily.index', compact(
            'rooms', 'roomsByFloor', 'summary',
            'selectedDate', 'today', 'isToday',
            'timeFrom', 'timeTo', 'hasTimeFilter',
            'windowStart', 'windowEnd', 'nowTime'
        ));
    }

    /**
     * Resolve room status for the given window.
     *
     * - TODAY (no time filter): physical status takes precedence.
     * - With time filter or future/past: derived from bookings in that window only.
     * - Multiple bookings possible in one day (back-to-back hourly) → return all of them.
     */
    private function resolveDailyStatus(Room $room, array $bookings, bool $isToday, bool $hasTimeFilter): array
    {
        // Physical status overrides on today (no time filter)
        if ($isToday && !$hasTimeFilter) {
            if (in_array($room->status, ['maintenance', 'cleaning', 'inspection'])) {
                return ['status' => $room->status, 'bookings' => []];
            }
        }

        if (empty($bookings)) {
            $status = ($isToday && !$hasTimeFilter) ? $room->status : 'available';
            return ['status' => $status, 'bookings' => []];
        }

        $list = collect($bookings);

        // Determine dominant status (highest priority booking)
        $checkedIn = $list->first(fn($b) => $b->status === 'checked_in');
        if ($checkedIn) {
            return ['status' => 'occupied', 'bookings' => $list->all()];
        }

        $inspecting = $list->first(fn($b) => $b->status === 'inspection_requested');
        if ($inspecting) {
            return ['status' => 'inspection', 'bookings' => $list->all()];
        }

        $confirmed = $list->first(fn($b) => in_array($b->status, ['confirmed', 'pending']));
        if ($confirmed) {
            if ($isToday && !$hasTimeFilter && in_array($room->status, ['maintenance', 'cleaning'])) {
                return ['status' => $room->status, 'bookings' => []];
            }
            return ['status' => 'reserved', 'bookings' => $list->all()];
        }

        // All past/completed — show available but still attach bookings for history
        $past = $list->filter(fn($b) => in_array($b->status, ['checked_out', 'completed']));
        if ($past->isNotEmpty()) {
            return ['status' => 'available', 'bookings' => $past->all()];
        }

        $status = ($isToday && !$hasTimeFilter) ? $room->status : 'available';
        return ['status' => $status, 'bookings' => []];
    }
}

