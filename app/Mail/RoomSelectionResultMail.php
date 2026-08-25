<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RoomSelectionResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public bool $fulfilled;
    public string $handlingNote;

    public function __construct(Booking $booking, bool $fulfilled, string $handlingNote = '')
    {
        $this->booking = $booking->loadMissing([
            'customer',
            'roomCategory',
            'bookingRooms.room.category',
        ]);
        $this->fulfilled = $fulfilled;
        $this->handlingNote = trim($handlingNote);
    }

    public function build()
    {
        return $this
            ->subject(($this->fulfilled ? 'Đã xác nhận phòng theo yêu cầu ' : 'Cần xác nhận phòng dự phòng ') . $this->booking->booking_code . ' - MCuong Hotel')
            ->view('emails.room-selection-result')
            ->with([
                'booking' => $this->booking,
                'fulfilled' => $this->fulfilled,
                'handlingNote' => $this->handlingNote,
            ]);
    }
}
