<?php

namespace App\Observers;

use App\Models\Booking;
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
        Realtime::booking($booking, $this->detectAction($booking));
    }

    public function deleted(Booking $booking): void
    {
        Realtime::booking($booking, 'deleted');
    }

    private function detectAction(Booking $booking): string
    {
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

        if ($booking->wasChanged('payment_status') || $booking->wasChanged('deposit_amount')) {
            return 'payment_updated';
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
