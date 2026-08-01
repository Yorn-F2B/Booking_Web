<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GuestBookingCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public float $paidAmount,
        public string $reason
    ) {
    }

    public function build()
    {
        return $this
            ->subject('Xác nhận đã hủy booking ' . $this->booking->booking_code . ' - MCuong Hotel')
            ->view('emails.guest-booking-cancelled');
    }
}
