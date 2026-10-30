<?php

namespace App\Http\Controllers;

use App\Actions\ReconcilePayments;
use App\Actions\RefundPayment;
use App\Models\Event;
use App\Models\Payment;
use App\Payments\Contracts\OnlineGateway;
use App\Payments\GatewayRegistry;
use App\Support\EventPaymentConfig;
use Illuminate\Http\JsonResponse;
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
        protected RefundPayment $refund,
    ) {}

    /** Retour du navigateur après paiement : vérifie puis confirme. */
    public function return(Request $request, string $gateway): RedirectResponse
    {
        $gw = $this->registry->get($gateway);
        $payment = Payment::find($request->query('payment'));

        if (! $gw instanceof OnlineGateway || ! $payment) {
            return redirect()->route('account.dashboard');
        }

        // Vérifier avec les identifiants de l'événement (repli global) - mêmes que
        // ceux utilisés à l'initiation du paiement.
        EventPaymentConfig::apply($payment->event);

        $this->settle($payment, $gw->verify($payment), $gw);

        $flash = $payment->fresh()->status === 'paid'
            ? ['ukc_status' => 'Paiement confirmé. Votre reçu vous a été envoyé par e-mail.']
            : ['ukc_error' => 'Paiement non confirmé pour le moment. Il le sera dès réception de la confirmation.'];

        // Parcours co-auteur (sans compte) : retour sur la page publique dédiée.
        if (($payment->meta['flow'] ?? null) === 'coauthor' && $payment->event?->slug) {
            return redirect()->route('public.coauthor', [
                'slug' => $payment->event->slug,
                'email' => $payment->participant?->email,
            ])->with($flash);
        }

        // Inscription à une formation : retour sur l'espace « Mes formations ».
        if ($payment->flow() === 'formation') {
            return redirect()->route('account.formations')->with($flash);
        }

        return redirect()->route('account.payment', $payment->event_id)->with($flash);
    }

    /**
     * Fin du formulaire EMBARQUÉ : le navigateur annonce que le payeur a terminé.
     *
     * ⚠️ **On ne le croit pas.** Cette route ne lit aucun statut de la requête :
     * elle interroge le prestataire, exactement comme le retour navigateur. Le
     * navigateur ne fait que DONNER L'HEURE - « regardez maintenant » - et c'est
     * tout ce qu'on lui accorde. Croire son verdict reviendrait à laisser le
     * payeur écrire lui-même qu'il a payé.
     *
     * ⚠️ Et si cet appel n'arrive jamais (onglet fermé, réseau coupé), le paiement
     * se confirme quand même : `payments:reconcile` repasse toutes les 10 minutes.
     * Cette route accélère, elle ne décide pas.
     */
    public function verify(Request $request, string $gateway): JsonResponse
    {
        $gw = $this->registry->get($gateway);
        $payment = Payment::find($request->input('payment'));

        if (! $gw instanceof OnlineGateway || ! $payment) {
            return response()->json(['status' => 'unknown'], 404);
        }

        EventPaymentConfig::apply($payment->event);
        $this->settle($payment, $gw->verify($payment), $gw);

        return response()->json(['status' => $payment->fresh()->status]);
    }

    /**
     * Notification serveur-à-serveur (webhook), signature vérifiée.
     *
     * La signature doit être vérifiée AVANT de savoir à quelle événement le paiement
     * se rattache : on essaie donc la config globale puis chaque événement ayant ses
     * propres identifiants pour cette passerelle (chaque essai est isolé, cf.
     * `EventPaymentConfig::using`). Si l'URL porte déjà l'événement (URL dédiée
     * déclarée chez le marchand), on n'essaie que celle-là.
     *
     * Reste **fail-closed** : sans signature valide, rien n'est confirmé. Tous les
     * rejets sont journalisés (canal `payments`) pour pouvoir diagnostiquer un
     * « j'ai payé mais le système dit non ».
     */
    public function webhook(Request $request, string $gateway, ?string $event = null): Response
    {
        $gw = $this->registry->get($gateway);

        if (! $gw instanceof OnlineGateway) {
            Log::channel('payments')->warning('Webhook paiement : passerelle inconnue ou hors ligne.', ['gateway' => $gateway]);

            return response('OK', 200);
        }

        // URL dédiée « /{gateway}/{edition} » : on vise la configuration de CETTE
        // édition. ⚠️⚠️ `{event}` N'EST PLUS LIÉ IMPLICITEMENT à un modèle Event
        // (d'où le type `?string`, et la résolution à la main ci-dessous) : un
        // identifiant périmé ou inconnu dans l'URL - réimport qui change les id,
        // édition supprimée, id saisi à la main chez le prestataire - faisait
        // répondre **404 EN AMONT du contrôleur**, que le prestataire lit comme un
        // échec de livraison et réessaie en boucle, alors que le paiement est bien
        // là. Un id obsolète dans une URL EXTERNE ne doit jamais faire perdre un
        // paiement : s'il ne correspond à rien, on RETOMBE sur le comportement
        // générique (toutes les configurations), la signature restant le vrai
        // juge. La forme générique `/{gateway}` reste recommandée, mais la forme
        // dédiée ne pénalise plus un id devenu faux.
        $scoped = null;
        if ($event !== null && $event !== '') {
            $scoped = Event::find((int) $event);
            if (! $scoped) {
                Log::channel('payments')->warning('Webhook paiement : édition inconnue dans l\'URL, repli sur toutes les configurations.', [
                    'gateway' => $gateway,
                    'edition_url' => $event,
                ]);
            }
        }

        $candidates = $scoped ? [$scoped] : EventPaymentConfig::candidates($gateway);

        foreach ($candidates as $candidate) {
            $parsed = EventPaymentConfig::using($candidate, fn () => $gw->parseWebhook($request));
            if (! $parsed) {
                continue; // signature invalide pour cette configuration : on essaie la suivante
            }

            $payment = Payment::where('gateway', $gateway)
                ->where('gateway_ref', $parsed['reference'])
                ->first();

            if (! $payment) {
                Log::channel('payments')->warning('Webhook paiement : aucune transaction connue pour cette référence.', [
                    'gateway' => $gateway,
                    'reference' => $parsed['reference'],
                ]);

                return response('OK', 200);
            }

            // Étanchéité : le marchand d'un événement ne peut confirmer que SES
            // paiements (la config globale, elle, couvre tous les événements).
            if ($candidate && $payment->event_id !== $candidate->id) {
                Log::channel('payments')->warning('Webhook paiement : événement signataire ≠ événement du paiement (ignoré).', [
                    'gateway' => $gateway,
                    'payment' => $payment->id,
                    'signataire' => $candidate->id,
                    'paiement_edition' => $payment->event_id,
                ]);

                return response('OK', 200);
            }

            // ⚠️⚠️ **FedaPay → GEVES : un remboursement fait chez le prestataire
            // RETIRE les droits ici.** Sans ça, rembourser au tableau de bord FedaPay
            // laissait le participant avec une « inscription réglée » et des
            // attestations encore valides - les deux systèmes de remboursement
            // n'étaient pas synchronisés. Même effet EXACT que le bouton du
            // back-office (source unique `RefundPayment`), idempotent : le clic
            // manuel et ce webhook peuvent arriver tous deux sans double effet.
            if ($parsed['status'] === 'refunded') {
                $revoked = $this->refund->handle($payment, source: 'gateway');

                Log::channel('payments')->info('Webhook paiement : remboursement appliqué.', [
                    'gateway' => $gateway,
                    'payment' => $payment->id,
                    'attestations_revoquees' => $revoked,
                    'statut_final' => $payment->fresh()?->status,
                ]);

                return response('OK', 200);
            }

            $this->settle($payment, $parsed['status'], $gw);

            Log::channel('payments')->info('Webhook paiement traité.', [
                'gateway' => $gateway,
                'payment' => $payment->id,
                'statut_evenement' => $parsed['status'],
                'statut_final' => $payment->fresh()?->status,
            ]);

            return response('OK', 200);
        }

        Log::channel('payments')->warning('Webhook paiement : signature non vérifiée (aucune configuration ne correspond).', [
            'gateway' => $gateway,
            'edition_url' => $event,
            'configurations_essayees' => count($candidates),
        ]);

        // Toujours accuser réception (2xx) - traitement idempotent au-dessus.
        return response('OK', 200);
    }

    /**
     * Applique le statut vérifié au paiement (idempotent, anti-double-paiement).
     *
     * ⚠️ La passerelle suit le statut : c'est elle qui sait relever le détail de
     * l'encaissement - opérateur employé, référence, numéro débité - et ces
     * lignes-là sont les seules qui rattachent un paiement contesté à un téléphone.
     */
    protected function settle(Payment $payment, string $status, ?OnlineGateway $gw = null): void
    {
        $this->reconciler->settle($payment, $status, $gw);
    }
}
