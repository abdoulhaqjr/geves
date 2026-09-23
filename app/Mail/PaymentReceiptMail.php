<?php

namespace App\Mail;

use App\Models\Payment;
use App\Support\Locales;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail « paiement reçu » au participant, avec le numéro de reçu.
 * Mis en file (ShouldQueue).
 */
class PaymentReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment)
    {
        $this->locale(Locales::resolve($payment->participant?->user?->locale));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Paiement reçu - reçu :ref', ['ref' => $this->payment->receipt_no]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.payment-receipt');
    }
}
