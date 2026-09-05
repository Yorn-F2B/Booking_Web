<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingFinancialService;
use App\Services\StayPricingPolicyService;
use Illuminate\Support\Facades\Auth;
use PDF;

class InvoiceController extends Controller
{
    public function generate(Booking $booking)
    {
        $this->ensureBookingAccess($booking);
        $data = $this->buildInvoiceData($booking);
        $pdf = PDF::loadView('admin.pages.invoices.pdf', $data);

        return $pdf->download('invoice_' . $booking->booking_code . '.pdf');
    }

    public function view(Booking $booking)
    {
        $this->ensureBookingAccess($booking);

        return view('admin.pages.invoices.pdf', $this->buildInvoiceData($booking));
    }

    private function ensureBookingAccess(Booking $booking): void
    {
        abort_unless(
            $booking->canBeHandledBy(Auth::user()),
            403,
            'Bạn không được phân công xử lý booking này.'
        );
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
        // Giữ nguyên lịch sử tiền đã thu. Khoản hoàn được theo dõi riêng ở booking
        // vì hiện tại hệ thống chưa có bảng giao dịch refund riêng.
        $paidTotal = round((float) $successfulPayments->sum('amount'), 0);
        $refundDueTotal = round(max(0, (float) ($booking->refund_due_amount ?? 0)), 0);
        $refundedTotal = $booking->refund_status === 'completed' ? $refundDueTotal : 0;
        $pendingRefundTotal = $booking->refund_status === 'pending' ? $refundDueTotal : 0;
        $netPaidTotal = max(0, round($paidTotal - $refundedTotal, 0));
        $isCancelled = in_array($booking->status, ['cancelled', 'canceled'], true);

        // Booking đã hủy không còn nghĩa vụ phải thu thêm. Nếu có tiền đã thu thì
        // khoản cần hoàn/đã hoàn được trình bày riêng, không biến thành "còn phải thu".
        $remainingTotal = $isCancelled ? 0 : max(0, round($grandTotal - $netPaidTotal, 0));
        $overpaymentTotal = $isCancelled ? 0 : max(0, round($netPaidTotal - $grandTotal, 0));

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
            'refundDueTotal',
            'refundedTotal',
            'pendingRefundTotal',
            'netPaidTotal',
            'isCancelled',
            'remainingTotal',
            'overpaymentTotal',
            'nightCount',
            'hourlyHours'
        );
    }
}
