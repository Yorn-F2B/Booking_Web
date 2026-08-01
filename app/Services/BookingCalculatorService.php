<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingRoom;
use Carbon\Carbon;

/** The single source of truth for booking, invoice, report and audit totals. */
class BookingCalculatorService
{
    public function calculateTotal(Booking $booking): array
    {
        $booking->loadMissing(['bookingRooms.periods', 'serviceItems', 'roomInspections.items']);
        $rooms = $booking->bookingRooms->map(fn (BookingRoom $room) => $this->calculateRoom($booking, $room));
        $services = (float) $booking->serviceItems->where('billing_status', 'confirmed')->sum('total');
        $inspection = (float) $booking->roomInspections->flatMap->items->where('status', 'approved')->sum('total');
        $roomGross = (float) $rooms->sum('gross');
        $roomSupport = (float) $rooms->sum('support');
        // Booking-level discounts remain global. Incident support is already deducted on its own room.
        $discount = min(max(0, (float) $booking->discount_amount), $roomGross + $services + $inspection - $roomSupport);
        return [
            'rooms' => $rooms->values()->all(), 'room_total' => round($roomGross, 0),
            'support_total' => round($roomSupport, 0), 'services_total' => round($services, 0),
            'inspection_total' => round($inspection, 0), 'booking_discount' => round($discount, 0),
            'total' => round(max(0, $roomGross - $roomSupport + $services + $inspection - $discount), 0),
        ];
    }

    public function calculateRoom(Booking $booking, BookingRoom $room): array
    {
        $periods = $room->periods;
        if ($periods->isEmpty()) {
            $start = Carbon::parse($booking->check_in_at ?: $booking->check_in_date)->startOfDay();
            $end = Carbon::parse($booking->check_out_at ?: $booking->check_out_date)->startOfDay();
            $periods = collect([(object) ['room_id' => $room->room_id, 'start_date' => $start, 'end_date' => $end, 'price_per_night' => $room->price_at_booking]]);
        }
        $lines = $periods->map(function ($period) {
            $nights = max(0, Carbon::parse($period->start_date)->startOfDay()->diffInDays(Carbon::parse($period->end_date)->startOfDay()));
            return ['room_id' => (int) $period->room_id, 'start_date' => Carbon::parse($period->start_date)->toDateString(), 'end_date' => Carbon::parse($period->end_date)->toDateString(), 'nights' => $nights, 'price_per_night' => (float) $period->price_per_night, 'total' => round($nights * (float) $period->price_per_night, 0)];
        })->values();
        $gross = (float) $lines->sum('total') + (float) $room->surcharge;
        $support = min(max(0, (float) ($room->support_amount ?? 0)), max(0, $gross));
        return ['booking_room_id' => $room->id, 'periods' => $lines->all(), 'room_price' => round((float) $lines->sum('total'), 0), 'surcharge' => round((float) $room->surcharge, 0), 'support' => round($support, 0), 'gross' => round($gross, 0), 'total' => round($gross - $support, 0)];
    }
}
