<?php

namespace App\Mail;

use App\Models\Participant;
use App\Support\EmailTemplates;
use App\Support\Locales;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

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

        return new Envelope(subject: $subject.' — '.($this->participant->edition?->title ?? __('Colloque')));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.participant-invitation', with: ['meta' => $this->meta()]);
    }
}
