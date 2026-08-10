<?php

namespace App\Mail;

use App\Models\Edition;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Message envoyé depuis le formulaire de contact public d'une édition, à
 * l'adresse de contact de l'organisation. Le « répondre à » pointe vers
 * l'expéditeur du message.
 */
class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Edition $edition,
        public string $senderName,
        public string $senderEmail,
        public string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Contact — '.($this->edition->title ?: 'Colloque'),
            replyTo: [$this->senderEmail],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.contact-message');
    }
}
