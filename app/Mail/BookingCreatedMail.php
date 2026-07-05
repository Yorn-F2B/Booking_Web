<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public string $source;

    public function __construct(Booking $booking, string $source = 'user_online')
    {
        $this->booking = $booking->loadMissing([
            'customer',
            'roomCategory',
            'bookingRooms.room',
            'serviceItems.service',
            'payments',
        ]);
        $this->source = $source;
    }

    public function build()
    {
        $subject = $this->source === 'payment_success'
            ? 'Xác nhận thanh toán và đặt phòng ' . $this->booking->booking_code . ' - MCuong Hotel'
            : 'Xác nhận đặt phòng ' . $this->booking->booking_code . ' - MCuong Hotel';

        return $this
            ->subject($subject)
            ->view('emails.booking-created')
            ->with([
                'booking' => $this->booking,
                'source' => $this->source,
            ]);
    }
}
