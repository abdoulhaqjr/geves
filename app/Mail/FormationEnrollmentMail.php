<?php

namespace App\Mail;

use App\Models\FormationEnrollment;
use App\Support\Locales;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmation d'inscription à une formation. Rappelle les infos (titre, dates,
 * lieu/salle) et l'état des frais (gratuite / réglés / à régler). Mise en file.
 */
class FormationEnrollmentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public FormationEnrollment $enrollment)
    {
        $this->locale(Locales::resolve($enrollment->user?->locale));
    }

    public function envelope(): Envelope
    {
        // La formation est toujours présente (FK requise ; la suppression cascade
        // sur les inscriptions, donc le mail ne part jamais orphelin).
        return new Envelope(subject: __('Inscription à la formation').' - '.$this->enrollment->formation->title);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.formation-enrollment', with: [
            'enrollment' => $this->enrollment,
            'formation' => $this->enrollment->formation,
        ]);
    }
}
