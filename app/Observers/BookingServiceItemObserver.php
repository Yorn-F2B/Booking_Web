<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\BookingServiceItem;
use App\Support\Realtime;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BookingServiceItemObserver implements ShouldHandleEventsAfterCommit
{
    public function created(BookingServiceItem $item): void
    {
        $this->broadcastBooking($item, 'service_updated');
    }

    public function updated(BookingServiceItem $item): void
    {
        $this->broadcastBooking($item, 'service_updated');
    }

    public function deleted(BookingServiceItem $item): void
    {
        $this->broadcastBooking($item, 'service_updated');
    }

    private function broadcastBooking(BookingServiceItem $item, string $action): void
    {
        $booking = null;

        if (method_exists($item, 'booking')) {
            $booking = $item->booking;
        }

        if (!$booking && $item->booking_id) {
            $booking = Booking::find($item->booking_id);
        }

        Realtime::booking($booking, $action, false);
    }
}
