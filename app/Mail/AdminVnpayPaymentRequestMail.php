<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\BookingPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminVnpayPaymentRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public BookingPayment $payment,
        public string $paymentUrl,
        public $expiresAt
    ) {
    }

    public function build(): self
    {
        return $this->subject('Yêu cầu thanh toán booking ' . $this->booking->booking_code)
            ->view('emails.admin-vnpay-payment-request')
            ->with([
                'booking' => $this->booking,
                'payment' => $this->payment,
                'paymentUrl' => $this->paymentUrl,
                'expiresAt' => $this->expiresAt,
            ]);
    }
}
