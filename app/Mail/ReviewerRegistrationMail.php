<?php

namespace App\Mail;

use App\Models\Participant;
use App\Support\Locales;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Informe un évaluateur de son inscription au vivier, avant toute attribution. */
class ReviewerRegistrationMail extends Mailable
{
    public function __construct(public Participant $reviewer, public bool $fromApplication = false)
    {
        $this->locale(Locales::resolve($reviewer->user?->locale));
    }

    public function envelope(): Envelope
    {
        $subject = $this->fromApplication
            ? __('Votre candidature d’évaluateur a été acceptée')
            : __('Vous avez été ajouté comme évaluateur');

        // ⚠️ `data_get` plutôt que `->event?->title` : l'analyse tient la relation
        // pour non-nulle et refuse le nullsafe à gauche d'un `??`. Elle l'est
        // pourtant - un évaluateur ajouté hors événement n'en aurait pas.
        $titre = trim((string) data_get($this->reviewer, 'event.title'));

        return new Envelope(subject: $subject.' - '.($titre !== '' ? $titre : __('Événement')));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.reviewer-registration', with: [
            'portalUrl' => $this->reviewer->user ? route('account.dashboard') : route('account.register'),
        ]);
    }
}
