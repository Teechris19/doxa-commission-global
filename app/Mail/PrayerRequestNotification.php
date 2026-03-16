<?php

namespace App\Mail;

use App\Models\PrayerRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrayerRequestNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PrayerRequest $prayerRequest,
        public string $subject = 'New Prayer Request Submitted'
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.prayer-request-notification',
        );
    }
}
