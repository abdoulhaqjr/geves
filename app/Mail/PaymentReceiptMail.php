<?php

namespace App\Mail;

use App\Documents\BrandedPdf;
use App\Models\Payment;
use App\Support\Locales;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

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

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        $receipt = Str::slug((string) $this->payment->receipt_no) ?: 'paiement-'.$this->payment->getKey();

        return [
            Attachment::fromData(
                fn () => BrandedPdf::stamp(
                    Pdf::loadView('pdf.receipt', ['payment' => $this->payment]),
                    'Reçu de paiement - '.$this->payment->receipt_no,
                )->output(),
                'recu-'.$receipt.'.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
