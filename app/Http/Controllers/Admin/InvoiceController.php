<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingFinancialService;
use App\Services\StayPricingPolicyService;
use PDF;

class InvoiceController extends Controller
{
    public function generate(Booking $booking)
    {
        $data = $this->buildInvoiceData($booking);
        $pdf = PDF::loadView('admin.pages.invoices.pdf', $data);

        return $pdf->download('invoice_' . $booking->booking_code . '.pdf');
    }

    public function view(Booking $booking)
    {
        return view('admin.pages.invoices.pdf', $this->buildInvoiceData($booking));
    }

    private function buildInvoiceData(Booking $booking): array
    {
        $booking->load([
            'customer',
            'roomCategory',
            'bookingRooms.room.category',
            'roomChanges.oldRoom.category',
            'roomChanges.newRoom.category',
            'roomChanges.oldCategory',
            'roomChanges.newCategory',
            'serviceItems.service',
            'payments',
            'roomInspections.items',
        ]);

        $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $checkOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');
        $nightCount = max(1, $checkInAt->copy()->startOfDay()->diffInDays($checkOutAt->copy()->startOfDay()));
        $durationMinutes = max(1, $checkInAt->diffInMinutes($checkOutAt));

        $hourlyPercent = null;
        $hourlyHours = null;
        if ($booking->booking_type === 'hourly') {
            $oneNightRoomTotal = (float) $booking->bookingRooms->sum('price_at_booking');
            $hourlyPolicy = app(StayPricingPolicyService::class)->shortStay(
                $oneNightRoomTotal,
                1,
                $durationMinutes,
                $booking
            );
            $hourlyPercent = (float) $hourlyPolicy['charged_percent'];
            $hourlyHours = (int) $hourlyPolicy['duration_hours'];
        }

        $roomLines = $booking->bookingRooms->map(function ($bookingRoom) use ($booking, $nightCount, $hourlyPercent, $hourlyHours) {
            $base = $booking->booking_type === 'hourly'
                ? round((float) $bookingRoom->price_at_booking * (float) $hourlyPercent, 0)
                : round((float) $bookingRoom->price_at_booking * $nightCount, 0);

            return [
                'room_number' => $bookingRoom->room?->room_number ?? '---',
                'category_name' => $bookingRoom->room?->category?->name
                    ?? $booking->roomCategory?->name
                    ?? '---',
                'quantity_label' => $booking->booking_type === 'hourly'
                    ? $hourlyHours . ' giờ'
                    : $nightCount . ' đêm',
                'unit_price' => (float) $bookingRoom->price_at_booking,
                'base_total' => $base,
                'surcharge' => (float) $bookingRoom->surcharge,
                'surcharge_reason' => $bookingRoom->surcharge_reason,
                'total' => round($base + (float) $bookingRoom->surcharge, 0),
            ];
        })->values();

        $roomChangeLines = $booking->roomChanges
            ->sortBy('created_at')
            ->values();

        $serviceLines = $booking->serviceItems
            ->where('billing_status', 'confirmed')
            ->values();

        $inspectionLines = $booking->roomInspections
            ->flatMap(fn ($inspection) => $inspection->items->where('status', 'approved'))
            ->values();

        $successfulPayments = $booking->payments
            ->where('status', 'success')
            ->sortBy(fn ($payment) => $payment->paid_at ?? $payment->created_at)
            ->values();

        $financialService = app(BookingFinancialService::class);
        $roomTotal = round((float) $roomLines->sum('total'), 0);
        $serviceTotal = round((float) $serviceLines->sum('total'), 0);
        $inspectionTotal = round((float) $inspectionLines->sum('total'), 0);
        $grandTotal = round($financialService->currentTotal($booking), 0);
        $paidTotal = round((float) $successfulPayments->sum('amount'), 0);
        $remainingTotal = max(0, round($grandTotal - $paidTotal, 0));
        $overpaymentTotal = max(0, round($paidTotal - $grandTotal, 0));

        return compact(
            'booking',
            'roomLines',
            'roomChangeLines',
            'serviceLines',
            'inspectionLines',
            'successfulPayments',
            'roomTotal',
            'serviceTotal',
            'inspectionTotal',
            'grandTotal',
            'paidTotal',
            'remainingTotal',
            'overpaymentTotal',
            'nightCount',
            'hourlyHours'
        );
    }
}
