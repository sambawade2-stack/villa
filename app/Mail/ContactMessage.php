<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array{name: string, email: string, phone: ?string, subject: string, message: string}  $payload */
    public function __construct(public array $payload) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Message du site : :subject', ['subject' => $this->payload['subject']]),
            // On répond au visiteur, pas à l'expéditeur technique.
            replyTo: [new Address($this->payload['email'], $this->payload['name'])],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.contact', with: ['payload' => $this->payload]);
    }
}
