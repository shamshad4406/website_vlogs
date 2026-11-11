<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlatformNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $heading,
        public string $body,
        public ?string $link = null,
        public ?User $recipient = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->heading);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.notification',
            with: [
                'heading' => $this->heading,
                'body' => $this->body,
                'link' => $this->link,
                'name' => $this->recipient?->name,
            ],
        );
    }
}
