<?php

namespace App\Mail;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invitation d'un participant à l'événement (rappel des infos pratiques).
 */
class ParticipantInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Participant $participant) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invitation — ' . ($this->participant->edition?->title ?? 'Colloque'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.participant-invitation');
    }
}
