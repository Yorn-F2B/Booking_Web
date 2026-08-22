<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\BookingStaffAssignment;
use App\Support\Realtime;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BookingObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Booking $booking): void
    {
        Realtime::booking($booking, 'created');
    }

    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('status') && in_array($booking->status, ['checked_out', 'completed', 'cancelled', 'canceled'], true)) {
            BookingStaffAssignment::query()
                ->where('booking_id', $booking->id)
                ->where('status', 'active')
                ->update(['status' => 'done']);
        }

        Realtime::booking($booking, $this->detectAction($booking));
    }

    public function deleted(Booking $booking): void
    {
        Realtime::booking($booking, 'deleted');
    }

    private function detectAction(Booking $booking): string
    {
        // Thanh toán có thể đồng thời chuyển pending -> confirmed. Ưu tiên một
        // thông báo "thanh toán" duy nhất thay vì phát cả "xác nhận" và
        // "cập nhật thanh toán" cho cùng thao tác.
        if ($booking->wasChanged('payment_status')
            || $booking->wasChanged('deposit_amount')
            || $booking->wasChanged('overpayment_amount')) {
            return 'payment_updated';
        }

        if ($booking->wasChanged('status')) {
            return match ($booking->status) {
                'confirmed' => 'confirmed',
                'checked_in' => 'checked_in',
                'inspection_requested' => 'inspection_requested',
                'completed' => 'completed',
                'checked_out' => 'checked_out',
                'cancelled', 'canceled' => 'cancelled',
                default => 'status_updated',
            };
        }

        if ($booking->wasChanged('estimated_total')) {
            return 'total_updated';
        }

        if ($booking->wasChanged('check_out_at') || $booking->wasChanged('check_out_date')) {
            return 'extended';
        }

        if ($booking->wasChanged('note')) {
            return 'note_updated';
        }

        return 'updated';
    }
}
