<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invitation d'un co-auteur à régler ses frais après l'acceptation d'une
 * communication à laquelle il est associé. Contient un lien pré-rempli vers le
 * parcours de paiement dédié aux co-auteurs.
 */
class CoauthorPaymentInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Submission $submission,
        public string $coauthorName,
        public string $paymentUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Communication acceptée — réglez vos frais de co-auteur'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.coauthor-payment');
    }
}
