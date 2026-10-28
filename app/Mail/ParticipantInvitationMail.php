<?php

namespace App\Mail;

use App\Documents\InvitationLetter;
use App\Models\Participant;
use App\Support\EmailTemplates;
use App\Support\Locales;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Invitation d'un participant à l'événement (rappel des infos pratiques).
 * Sujet/titre/intro proviennent des modèles éditables (EmailTemplates).
 * Mise en file (ShouldQueue).
 */
class ParticipantInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Participant $participant)
    {
        $this->locale(Locales::resolve($participant->user?->locale));
    }

    /** @return array{subject:string, heading:string, intro:string} */
    protected function meta(): array
    {
        return EmailTemplates::forParticipant($this->participant);
    }

    public function envelope(): Envelope
    {
        $meta = $this->meta();
        $subject = $meta['subject'] !== '' ? $meta['subject'] : __('Invitation');

        return new Envelope(subject: $subject.' - '.($this->participant->event?->title ?? __('Événement')));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.participant-invitation', with: ['meta' => $this->meta()]);
    }

    /**
     * Lettre d'invitation personnalisée en pièce jointe (PDF). Le rendu dompdf
     * tourne sur le worker (ce mailable est `ShouldQueue`), depuis le modèle
     * enregistré du type « invitation ».
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $name = 'invitation-'.(Str::slug($this->participant->displayName()) ?: 'participant').'.pdf';

        return [
            Attachment::fromData(
                fn () => InvitationLetter::pdf($this->participant)->output(),
                $name,
            )->withMime('application/pdf'),
        ];
    }
}
