<?php

namespace App\Mail;

use App\Models\Submission;
use App\Support\EmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail à l'auteur correspondant lors d'un événement du workflow de sa
 * communication (accusé de réception, mise en relecture, correction, décision).
 * Sujet/titre/intro proviennent des modèles éditables (EmailTemplates).
 */
class SubmissionStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var array{subject:string, heading:string, intro:string} */
    public array $meta;

    public function __construct(
        public Submission $submission,
        public string $statusKey,
        public ?string $note = null,
    ) {
        $this->meta = EmailTemplates::forSubmission($submission, $statusKey);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->meta['subject']);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.submission-status');
    }
}
