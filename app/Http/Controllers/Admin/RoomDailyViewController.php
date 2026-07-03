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
        $today   = now(self::TIMEZONE)->toDateString();
        $nowTime = now(self::TIMEZONE)->format('H:i');

        // ── Date from / Date to ───────────────────────────────────────
        $dateFrom = $request->input('date_from', $today);
        $dateTo   = $request->input('date_to',   $today);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = $today;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = $today;
        }
        // Ensure from <= to
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        // ── Time from / Time to (optional) ───────────────────────────
        $timeFrom = $request->input('time_from', '');
        $timeTo   = $request->input('time_to',   '');
        $hasTimeFilter = $timeFrom !== '' && $timeTo !== '';

        if ($hasTimeFilter) {
            if (!preg_match('/^\d{2}:\d{2}$/', $timeFrom) || !preg_match('/^\d{2}:\d{2}$/', $timeTo)) {
                $hasTimeFilter = false;
                $timeFrom = '';
                $timeTo   = '';
            }
        }

        // ── Build window ──────────────────────────────────────────────
        $windowStart = Carbon::parse(
            $dateFrom . ' ' . ($hasTimeFilter ? $timeFrom . ':00' : '00:00:00'),
            self::TIMEZONE
        );
        $windowEnd = Carbon::parse(
            $dateTo . ' ' . ($hasTimeFilter ? $timeTo . ':00' : '23:59:59'),
            self::TIMEZONE
        );

        // Sanity: if end <= start (e.g. same day, time_to <= time_from), push end +1 day
        if ($windowEnd->lte($windowStart)) {
            $windowEnd->addDay();
        }

        $isRange   = $dateFrom !== $dateTo;                  // multi-day range
        $isToday   = !$isRange && $dateFrom === $today;       // single day = today
        $isPast    = !$isRange && $dateFrom < $today;
        $isFuture  = !$isRange && $dateFrom > $today;

        // booking-derived: khi có range, có time filter, hoặc không phải hôm nay
        // → không dùng physical room->status làm override, chỉ nhìn vào booking
        $bookingDerived = $isRange || $hasTimeFilter || $isPast || $isFuture;

        // ── Load rooms ────────────────────────────────────────────────
        $rooms = Room::with('category')
            ->orderByDesc('floor_number')
            ->orderByRaw('CAST(room_number AS UNSIGNED) ASC')
            ->orderBy('room_number')
            ->get();

        // ── Booking statuses ──────────────────────────────────────────
        $bookingStatuses = ['pending', 'confirmed', 'checked_in', 'inspection_requested'];
        if (!$isToday || $hasTimeFilter || $isRange) {
            $bookingStatuses = array_merge($bookingStatuses, ['checked_out', 'completed']);
        }

        // ── Query bookings overlapping window ─────────────────────────
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

        $rooms = $rooms->map(function ($room) use ($roomBookingMap, $isToday, $bookingDerived) {
            $dailyStatus = $this->resolveDailyStatus(
                $room,
                $roomBookingMap[$room->id] ?? [],
                $isToday,
                $bookingDerived
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
            'dateFrom', 'dateTo', 'today',
            'isToday', 'isRange', 'isPast', 'isFuture',
            'timeFrom', 'timeTo', 'hasTimeFilter',
            'windowStart', 'windowEnd', 'nowTime'
        ));
    }

    /**
     * Resolve room status for the given window.
     *
     * - TODAY single day (no time/range filter): physical status takes precedence.
     * - With time filter, range, or future/past: derived from bookings only.
     */
    private function resolveDailyStatus(Room $room, array $bookings, bool $isToday, bool $bookingDerived): array
    {
        // Physical status overrides on today (no time/range filter)
        if ($isToday && !$bookingDerived) {
            if (in_array($room->status, ['maintenance', 'cleaning', 'inspection'])) {
                return ['status' => $room->status, 'bookings' => []];
            }
        }

        if (empty($bookings)) {
            $status = ($isToday && !$bookingDerived) ? $room->status : 'available';
            return ['status' => $status, 'bookings' => []];
        }

        $list = collect($bookings);

        $checkedIn = $list->first(fn($b) => $b->status === 'checked_in');
        if ($checkedIn) {
            if ($isToday && !$bookingDerived && in_array($room->status, ['maintenance', 'cleaning', 'inspection'])) {
                return ['status' => $room->status, 'bookings' => []];
            }
            return ['status' => 'occupied', 'bookings' => $list->all()];
        }

        $inspecting = $list->first(fn($b) => $b->status === 'inspection_requested');
        if ($inspecting) {
            if ($isToday && !$bookingDerived && in_array($room->status, ['maintenance', 'cleaning'])) {
                return ['status' => $room->status, 'bookings' => []];
            }
            return ['status' => 'inspection', 'bookings' => $list->all()];
        }

        $confirmed = $list->first(fn($b) => in_array($b->status, ['confirmed', 'pending']));
        if ($confirmed) {
            if ($isToday && !$bookingDerived && in_array($room->status, ['maintenance', 'cleaning', 'inspection'])) {
                return ['status' => $room->status, 'bookings' => []];
            }
            return ['status' => 'reserved', 'bookings' => $list->all()];
        }

        $past = $list->filter(fn($b) => in_array($b->status, ['checked_out', 'completed']));
        if ($past->isNotEmpty()) {
            return ['status' => 'available', 'bookings' => $past->all()];
        }

        $status = ($isToday && !$bookingDerived) ? $room->status : 'available';
        return ['status' => $status, 'bookings' => []];
    }
}
