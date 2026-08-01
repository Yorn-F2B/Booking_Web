<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GuestBookingOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $otp,
        public int $expiresInMinutes
    ) {
    }

    public function build()
    {
        return $this
            ->subject('Mã xác thực booking ' . $this->booking->booking_code . ' - MCuong Hotel')
            ->view('emails.guest-booking-otp');
    }
}
