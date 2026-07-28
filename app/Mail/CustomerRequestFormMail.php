<?php
namespace App\Mail;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
class CustomerRequestFormMail extends Mailable
{
 use Queueable,SerializesModels;
 public function __construct(public Booking $booking,public string $formUrl,public $expiresAt,public string $type){}
 public function build():self{return $this->subject('Biểu mẫu yêu cầu - Booking '.$this->booking->booking_code)->view('emails.customer-request-form');}
}
