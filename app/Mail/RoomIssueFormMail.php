<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RoomIssueFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $formUrl,
        public $expiresAt
    ) {
        $this->booking->loadMissing(['customer', 'bookingRooms.room.category']);
    }

    public function build(): self
    {
        return $this
            ->subject('Biểu mẫu báo sự cố phòng - Booking ' . $this->booking->booking_code)
            ->view('emails.room-issue-form');
    }
}
