<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OperationalNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $notificationTitle,
        public string $notificationMessage,
        public ?string $targetUrl = null
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject($this->notificationTitle . ' - MCuong Hotel')
            ->view('emails.operational-notification')
            ->with([
                'notificationTitle' => $this->notificationTitle,
                'notificationMessage' => $this->notificationMessage,
                'targetUrl' => $this->targetUrl,
            ]);
    }
}
