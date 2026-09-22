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
 * Informe un utilisateur inscrit qu'il vient d'être associé à une communication
 * (auteur correspondant ou co-auteur) dans le cadre d'un événement. Lien pour
 * consulter la communication + lien pour décliner l'association si ce n'est pas
 * lui (utile pour les rapprochements par nom).
 */
class AuthorLinkedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Submission $submission,
        public string $recipientName,
        public string $roleLabel,
        public string $viewUrl,
        public string $declineUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Vous êtes associé(e) à une communication'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.author-linked');
    }
}
