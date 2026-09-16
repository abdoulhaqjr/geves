<?php

namespace App\Mail;

use App\Models\Edition;
use App\Models\Participant;
use App\Support\Locales;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Relance de paiement : rappelle à un participant (communication acceptée) de
 * régler ses frais d'inscription, avec le montant applicable et le lien de
 * paiement. Envoyée par le secrétariat. Mise en file (ShouldQueue).
 */
class PaymentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Participant $participant,
        public Edition $edition,
        public string $payUrl,
        public ?float $amount = null,
    ) {
        $this->locale(Locales::resolve($participant->user?->locale));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Rappel : frais d’inscription à régler').' — '.($this->edition->title ?? __('Colloque')));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.payment-reminder', with: [
            'payUrl' => $this->payUrl,
            'amount' => $this->amount,
        ]);
    }
}
