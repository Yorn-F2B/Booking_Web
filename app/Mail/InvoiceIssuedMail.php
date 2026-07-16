<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public Invoice $invoice,
        public string $pdfBinary,
        public string $pdfFileName
    ) {
        $this->booking = $booking->loadMissing([
            'customer.user',
            'roomCategory',
            'bookingRooms.room',
            'payments',
        ]);

        $this->invoice = $invoice->loadMissing([
            'booking',
            'booking.customer.user',
            'booking.bookingRooms.room',
            'booking.payments',
            'customer.user',
            'issuer',
            'creator',
        ]);
    }

    public function build(): self
    {
        return $this
            ->subject('Hóa đơn booking ' . $this->booking->booking_code . ' - MCuong Hotel')
            ->attachData($this->pdfBinary, $this->pdfFileName, [
                'mime' => 'application/pdf',
            ])
            ->view('emails.invoice-issued')
            ->with([
                'booking' => $this->booking,
                'invoice' => $this->invoice,
            ]);
    }
}
