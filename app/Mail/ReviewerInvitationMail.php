<?php

namespace App\Mail;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invitation d'un relecteur : lien vers son portail de relecture (accès par
 * jeton, sans compte). Mise en file (ShouldQueue).
 */
class ReviewerInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Participant $reviewer,
        public int $assignedCount = 1,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invitation à relire des communications');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.reviewer-invitation', with: [
            'portalUrl' => route('reviewer.portal', $this->reviewer->access_token),
        ]);
    }
}
