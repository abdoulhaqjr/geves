<?php

namespace App\Mail;

use App\Models\Submission;
use App\Support\EmailTemplates;
use App\Support\Locales;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail à l'auteur correspondant lors d'un événement du workflow de sa
 * communication (accusé de réception, mise en relecture, correction, décision).
 * Sujet/titre/intro proviennent des modèles éditables (EmailTemplates).
 * Mis en file (ShouldQueue) pour ne pas bloquer la requête.
 */
class SubmissionStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Submission $submission,
        public string $statusKey,
        public ?string $note = null,
    ) {
        // Rendu dans la langue de l'auteur (i18n) ; sujet/contenu calculés au rendu.
        $this->locale(Locales::resolve($submission->participant?->account?->locale));
    }

    /** @return array{subject:string, heading:string, intro:string} */
    protected function meta(): array
    {
        return EmailTemplates::forSubmission($this->submission, $this->statusKey);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->meta()['subject']);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.submission-status', with: ['meta' => $this->meta()]);
    }
}
