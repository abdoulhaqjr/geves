<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail à l'auteur correspondant lors d'un événement du workflow de sa
 * communication (accusé de réception, mise en relecture, correction, décision).
 */
class SubmissionStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    /** Sujet + intro par statut (le statut « submitted » = accusé de réception). */
    public const MESSAGES = [
        'submitted' => [
            'subject' => 'Accusé de réception de votre communication',
            'heading' => 'Communication bien reçue',
            'intro' => 'Nous accusons réception de votre communication. Elle sera examinée par le comité scientifique.',
        ],
        'under_review' => [
            'subject' => 'Votre communication est en relecture',
            'heading' => 'Communication en relecture',
            'intro' => 'Votre communication a été transmise à la relecture. Vous serez informé(e) de la décision.',
        ],
        'revision' => [
            'subject' => 'Correction demandée pour votre communication',
            'heading' => 'Correction demandée',
            'intro' => 'Le comité vous demande d’apporter des corrections à votre communication avant décision.',
        ],
        'accepted' => [
            'subject' => 'Votre communication est acceptée',
            'heading' => 'Communication acceptée',
            'intro' => 'Nous avons le plaisir de vous informer que votre communication est acceptée.',
        ],
        'rejected' => [
            'subject' => 'Décision concernant votre communication',
            'heading' => 'Communication non retenue',
            'intro' => 'Après examen, votre communication n’a pas été retenue pour cette édition.',
        ],
    ];

    public array $meta;

    public function __construct(
        public Submission $submission,
        public string $statusKey,
        public ?string $note = null,
    ) {
        $this->meta = self::MESSAGES[$statusKey] ?? self::MESSAGES['submitted'];
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
