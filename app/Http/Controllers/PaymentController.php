<?php

namespace App\Http\Controllers;

use App\Actions\ReconcilePayments;
use App\Models\Edition;
use App\Models\Payment;
use App\Payments\Contracts\OnlineGateway;
use App\Payments\GatewayRegistry;
use App\Support\EditionPaymentConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Retour du participant et notifications (webhooks) des passerelles en ligne.
 * La confirmation s'appuie toujours sur une vérification serveur du statut
 * (jamais sur des paramètres d'URL), avec garde anti-double-paiement.
 */
class PaymentController extends Controller
{
    public function __construct(
        protected GatewayRegistry $registry,
        protected ReconcilePayments $reconciler,
    ) {}

    /** Retour du navigateur après paiement : vérifie puis confirme. */
    public function return(Request $request, string $gateway): RedirectResponse
    {
        $gw = $this->registry->get($gateway);
        $payment = Payment::find($request->query('payment'));

        if (! $gw instanceof OnlineGateway || ! $payment) {
            return redirect()->route('account.dashboard');
        }

        // Vérifier avec les identifiants de l'édition (repli global) - mêmes que
        // ceux utilisés à l'initiation du paiement.
        EditionPaymentConfig::apply($payment->edition);

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

    /**
     * Notification serveur-à-serveur (webhook), signature vérifiée.
     *
     * La signature doit être vérifiée AVANT de savoir à quelle édition le paiement
     * se rattache : on essaie donc la config globale puis chaque édition ayant ses
     * propres identifiants pour cette passerelle (chaque essai est isolé, cf.
     * `EditionPaymentConfig::using`). Si l'URL porte déjà l'édition (URL dédiée
     * déclarée chez le marchand), on n'essaie que celle-là.
     *
     * Reste **fail-closed** : sans signature valide, rien n'est confirmé. Tous les
     * rejets sont journalisés (canal `payments`) pour pouvoir diagnostiquer un
     * « j'ai payé mais le système dit non ».
     */
    public function webhook(Request $request, string $gateway, ?Edition $edition = null): Response
    {
        $gw = $this->registry->get($gateway);

        if (! $gw instanceof OnlineGateway) {
            Log::channel('payments')->warning('Webhook paiement : passerelle inconnue ou hors ligne.', ['gateway' => $gateway]);

            return response('OK', 200);
        }

        $candidates = $edition ? [$edition] : EditionPaymentConfig::candidates($gateway);

        foreach ($candidates as $candidate) {
            $event = EditionPaymentConfig::using($candidate, fn () => $gw->parseWebhook($request));
            if (! $event) {
                continue; // signature invalide pour cette configuration : on essaie la suivante
            }

            $payment = Payment::where('gateway', $gateway)
                ->where('gateway_ref', $event['reference'])
                ->first();

            if (! $payment) {
                Log::channel('payments')->warning('Webhook paiement : aucune transaction connue pour cette référence.', [
                    'gateway' => $gateway,
                    'reference' => $event['reference'],
                ]);

                return response('OK', 200);
            }

            // Étanchéité : le marchand d'une édition ne peut confirmer que SES
            // paiements (la config globale, elle, couvre toutes les éditions).
            if ($candidate && $payment->edition_id !== $candidate->id) {
                Log::channel('payments')->warning('Webhook paiement : édition signataire ≠ édition du paiement (ignoré).', [
                    'gateway' => $gateway,
                    'payment' => $payment->id,
                    'signataire' => $candidate->id,
                    'paiement_edition' => $payment->edition_id,
                ]);

                return response('OK', 200);
            }

            $this->settle($payment, $event['status']);

            Log::channel('payments')->info('Webhook paiement traité.', [
                'gateway' => $gateway,
                'payment' => $payment->id,
                'statut_evenement' => $event['status'],
                'statut_final' => $payment->fresh()?->status,
            ]);

            return response('OK', 200);
        }

        Log::channel('payments')->warning('Webhook paiement : signature non vérifiée (aucune configuration ne correspond).', [
            'gateway' => $gateway,
            'edition' => $edition?->id,
            'configurations_essayees' => count($candidates),
        ]);

        // Toujours accuser réception (2xx) - traitement idempotent au-dessus.
        return response('OK', 200);
    }

    /** Applique le statut vérifié au paiement (idempotent, anti-double-paiement). */
    protected function settle(Payment $payment, string $status): void
    {
        $this->reconciler->settle($payment, $status);
    }
}
