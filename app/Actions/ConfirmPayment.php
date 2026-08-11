<?php

namespace App\Actions;

use App\Mail\PaymentReceiptMail;
use App\Models\Payment;
use App\Support\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

        // Transaction + verrou : la garde anti-double-paiement et l'écriture
        // sont atomiques (deux confirmations concurrentes ne peuvent pas passer).
        DB::transaction(function () use ($payment) {
            $already = Payment::where('edition_id', $payment->edition_id)
                ->where('participant_id', $payment->participant_id)
                ->where('status', 'paid')
                ->whereKeyNot($payment->id)
                ->lockForUpdate()
                ->exists();

            if ($already) {
                throw new \DomainException('Ce participant a déjà réglé ses frais.');
            }

            $payment->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'receipt_no' => $payment->receipt_no ?: $this->generateReceiptNo($payment),
            ])->save();
        });

        // E-mail hors transaction (I/O externe, ne doit pas la faire échouer).
        $recipient = $payment->participant?->email;
        if ($recipient) {
            Mail::to($recipient)->send(new PaymentReceiptMail($payment));
        }

        // Notifications in-app : reçu au participant + info au back-office.
        $amount = number_format((float) $payment->amount, 0, ',', ' ').' '.$payment->currency;
        Notifier::account(
            $payment->participant?->account,
            kind: 'payment',
            title: 'Paiement confirmé',
            body: 'Votre reçu n° '.$payment->receipt_no.' ('.$amount.') est disponible.',
            url: route('account.documents'),
            icon: 'receipt',
        );
        Notifier::staff(
            kind: 'payment',
            title: 'Nouveau paiement réglé',
            body: trim(($payment->participant?->full_name ?: 'Un participant').' — '.$amount),
            url: route('admin.payments.index'),
            icon: 'receipt',
        );

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
                $no .= '-'.strtoupper(Str::random(2));
                $exists = Payment::where('receipt_no', $no)->exists();
            }
        } while ($exists);

        return $no;
    }
}
