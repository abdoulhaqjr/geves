<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Informe une personne NON inscrite qu'elle est associée à une communication
 * dans le cadre d'un événement, et l'invite à créer son compte : à l'inscription,
 * la communication sera automatiquement rattachée à son espace.
 */
class AuthorInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Submission $submission,
        public string $recipientName,
        public string $roleLabel,
        public string $registerUrl,
        public string $declineUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('On vous a associé(e) à une communication — créez votre compte'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.author-invite');
    }
}
