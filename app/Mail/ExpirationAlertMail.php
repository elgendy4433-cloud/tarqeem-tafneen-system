<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpirationAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public string $moduleType,
        public string $title,
        public string $recordName,
        public string $identifier,
        public string $profession,
        public string $branch,
        public string $expiryDate,
        public int $daysRemaining,
        public ?string $notes = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alert',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
