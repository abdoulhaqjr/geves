<?php

namespace App\Actions;

use App\Mail\PaymentReceiptMail;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;

/**
 * Confirme un paiement (passage à « réglé ») : garde anti-double-paiement,
 * génération du numéro de reçu, e-mail de reçu au participant.
 */
class ConfirmPayment
{
    /** Levée lorsqu'un participant a déjà réglé. */
    public function handle(Payment $payment): Payment
    {
        if ($payment->status === 'paid') {
            return $payment;
        }

        // Garde-fou : un seul paiement réglé par participant et par édition.
        $already = Payment::where('edition_id', $payment->edition_id)
            ->where('participant_id', $payment->participant_id)
            ->where('status', 'paid')
            ->whereKeyNot($payment->id)
            ->exists();

        if ($already) {
            throw new \DomainException('Ce participant a déjà réglé ses frais.');
        }

        $payment->forceFill([
            'status' => 'paid',
            'paid_at' => now(),
            'receipt_no' => $payment->receipt_no ?: $this->generateReceiptNo($payment),
        ])->save();

        $recipient = $payment->participant?->email;
        if ($recipient) {
            Mail::to($recipient)->send(new PaymentReceiptMail($payment));
        }

        return $payment;
    }

    protected function generateReceiptNo(Payment $payment): string
    {
        $year = $payment->edition?->year ?: date('Y');

        do {
            $seq = Payment::where('edition_id', $payment->edition_id)
                ->whereNotNull('receipt_no')->count() + 1;
            $no = sprintf('REC-%s-%04d', $year, $seq);
            $exists = Payment::where('receipt_no', $no)->exists();
            if ($exists) {
                $no .= '-' . strtoupper(\Illuminate\Support\Str::random(2));
                $exists = Payment::where('receipt_no', $no)->exists();
            }
        } while ($exists);

        return $no;
    }
}
