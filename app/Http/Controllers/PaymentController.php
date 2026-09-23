<?php

namespace App\Http\Controllers;

use App\Actions\ConfirmPayment;
use App\Models\Payment;
use App\Payments\Contracts\OnlineGateway;
use App\Payments\GatewayRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Retour du participant et notifications (webhooks) des passerelles en ligne.
 * La confirmation s'appuie toujours sur une vérification serveur du statut
 * (jamais sur des paramètres d'URL), avec garde anti-double-paiement.
 */
class PaymentController extends Controller
{
    public function __construct(
        protected GatewayRegistry $registry,
        protected ConfirmPayment $confirm,
    ) {}

    /** Retour du navigateur après paiement : vérifie puis confirme. */
    public function return(Request $request, string $gateway): RedirectResponse
    {
        $gw = $this->registry->get($gateway);
        $payment = Payment::find($request->query('payment'));

        if (! $gw instanceof OnlineGateway || ! $payment) {
            return redirect()->route('account.dashboard');
        }

        $this->settle($payment, $gw->verify($payment));

        $flash = $payment->fresh()->status === 'paid'
            ? ['ukc_status' => 'Paiement confirmé. Votre reçu vous a été envoyé par e-mail.']
            : ['ukc_error' => 'Paiement non confirmé pour le moment. Il le sera dès réception de la confirmation.'];

        // Parcours co-auteur (sans compte) : retour sur la page publique dédiée.
        if (($payment->meta['flow'] ?? null) === 'coauthor' && $payment->edition?->slug) {
            return redirect()->route('public.coauthor', [
                'slug' => $payment->edition->slug,
                'email' => $payment->participant?->email,
            ])->with($flash);
        }

        // Inscription à une formation : retour sur l'espace « Mes formations ».
        if ($payment->flow() === 'formation') {
            return redirect()->route('account.formations')->with($flash);
        }

        return redirect()->route('account.payment', $payment->edition_id)->with($flash);
    }

    /** Notification serveur-à-serveur (webhook), signature vérifiée. */
    public function webhook(Request $request, string $gateway): Response
    {
        $gw = $this->registry->get($gateway);

        if ($gw instanceof OnlineGateway && ($event = $gw->parseWebhook($request))) {
            $payment = Payment::where('gateway', $gateway)
                ->where('gateway_ref', $event['reference'])
                ->first();

            if ($payment) {
                $this->settle($payment, $event['status']);
            }
        }

        // Toujours accuser réception (2xx) - traitement idempotent au-dessus.
        return response('OK', 200);
    }

    /** Applique le statut vérifié au paiement (idempotent, anti-double-paiement). */
    protected function settle(Payment $payment, string $status): void
    {
        if ($status === 'paid') {
            try {
                $this->confirm->handle($payment);
            } catch (\DomainException) {
                // Déjà réglé (ou un autre paiement l'est) : rien à faire.
            }
        } elseif ($status === 'failed' && $payment->status === 'pending') {
            $payment->update(['status' => 'failed']);
        }
    }
}
