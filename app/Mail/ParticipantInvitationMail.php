<?php

namespace App\Mail;

use App\Models\Participant;
use App\Support\EmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invitation d'un participant à l'événement (rappel des infos pratiques).
 * Sujet/titre/intro proviennent des modèles éditables (EmailTemplates).
 */
class ParticipantInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var array{subject:string, heading:string, intro:string} */
    public array $meta;

    public function __construct(public Participant $participant)
    {
        $this->meta = EmailTemplates::forParticipant($participant);
    }

    public function envelope(): Envelope
    {
        $subject = $this->meta['subject'] !== '' ? $this->meta['subject'] : 'Invitation';

        return new Envelope(subject: $subject.' — '.($this->participant->edition?->title ?? 'Colloque'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.participant-invitation', with: ['meta' => $this->meta]);
    }
}
