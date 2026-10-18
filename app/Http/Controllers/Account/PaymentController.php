<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Payment;
use App\Payments\GatewayRegistry;
use App\Support\EventLock;
use App\Support\EventPaymentConfig;
use App\Support\Fees;
use App\Support\PaymentEmbed;
use App\Support\SubmissionFormConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Paiement des frais par le participant (contrôleur + Blade, sans Livewire).
 * Disponible dès qu'au moins une communication est acceptée ; montant déduit du
 * grade. Garde anti-double-paiement (réutilise un paiement en attente existant).
 */
class PaymentController extends Controller
{
    public function show(Event $event): View
    {
        // ⚠️ Pas de garde ici : la page reste CONSULTABLE (un participant doit
        // pouvoir relire ce qu'il a payé). Seul `pay()` est bloqué, et la vue
        // remplace le parcours de règlement par une explication.
        EventPaymentConfig::apply($event); // passerelle propre à l'événement (repli global)
        $account = Auth::guard('account')->user();
        $participant = Participant::where('event_id', $event->id)
            ->where('user_id', $account->id)
            ->first();

        $applicable = $participant ? Fees::resolveAmount($event, $participant->title) : null;
        $amount = $applicable ?? ($participant ? Fees::highestAmount($event) : null);

        // Libellé du tarif applicable (rapproché du grade du participant).
        $applicableLabel = null;
        if ($applicable !== null) {
            foreach (Fees::grid($event) as $tier) {
                if ((float) ($tier['amount'] ?? 0) === (float) $applicable) {
                    $applicableLabel = $tier['label'] ?? null;
                    break;
                }
            }
        }

        return view('account.payment.pay', [
            'event' => $event,
            'frozen' => EventLock::frozen($event),
            'participant' => $participant,
            'participationModes' => (new SubmissionFormConfig($event))->participationModes(),
            'amount' => $amount,
            'tiers' => Fees::grid($event),
            'applicableLabel' => $applicableLabel,
            'eligible' => $participant && $participant->hasAcceptedSubmission(),
            'paid' => $participant && $participant->hasPaid(),
            'paidReceipt' => $participant?->participationPayments()->where('status', 'paid')->latest('id')->first(),
            'gateways' => app(GatewayRegistry::class)->available(),
        ]);
    }

    public function pay(Request $request, Event $event, GatewayRegistry $registry): RedirectResponse
    {
        EventLock::guard($event);
        EventPaymentConfig::apply($event); // passerelle propre à l'événement (repli global)
        $account = Auth::guard('account')->user();
        $participant = Participant::where('event_id', $event->id)
            ->where('user_id', $account->id)
            ->first();

        $amount = $participant
            ? (Fees::resolveAmount($event, $participant->title) ?? Fees::highestAmount($event))
            : null;

        if (! $participant || ! $participant->hasAcceptedSubmission() || $participant->hasPaid() || ! $amount) {
            return back()->withErrors(['pay' => 'Paiement indisponible.']);
        }

        $gateway = $registry->get((string) $request->input('gateway'));
        if (! $gateway || ! $gateway->isAvailable()) {
            return back()->withErrors(['pay' => 'Ce moyen de paiement n’est pas disponible.']);
        }

        // Réutilise un paiement en attente existant plutôt que d'en créer un doublon.
        $payment = Payment::firstOrCreate(
            [
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'status' => 'pending',
            ],
            [
                'amount' => $amount,
                'currency' => config('payments.currency', 'XOF'),
                'method' => $gateway->key(),
                'gateway' => $gateway->key(),
            ]
        );

        // ⚠️⚠️ **UN PAIEMENT EN ATTENTE A PU ÊTRE OUVERT AVEC UNE AUTRE
        // PASSERELLE.** `firstOrCreate` ne pose `method`/`gateway` qu'à la
        // CRÉATION : qui avait d'abord choisi « sur place », puis revenait régler
        // en ligne, repartait sur une ligne restée `manual`. Trois conséquences,
        // toutes silencieuses, et mesurées sur un vrai paiement :
        //
        // - `setOperator()` interroge la passerelle **enregistrée** : la
        //   passerelle hors ligne ne connaît aucun opérateur, donc tout code
        //   était REJETÉ et le moyen choisi n'atteignait jamais le prestataire ;
        // - `payments:reconcile` ne repasse que par la passerelle enregistrée :
        //   un règlement en ligne resté `manual` n'aurait **jamais** été
        //   revérifié, et le filet de sécurité ne couvrait pas cette ligne ;
        // - la comptabilité annonçait un encaissement au guichet pour un
        //   paiement en ligne.
        //
        // D'où le réalignement **avant** `setOperator()` : l'ordre est la règle.
        if ($payment->gateway !== $gateway->key()) {
            // ⚠️ La référence appartenait à l'autre prestataire : la garder
            // reviendrait à lui demander des nouvelles d'une transaction qui
            // n'est pas la sienne. On la JOURNALISE avant de la retirer - c'est
            // la pièce d'un « j'ai payé mais le système dit non ».
            if (filled($payment->gateway_ref)) {
                Log::channel('payments')->warning('Passerelle changée sur un paiement en attente', [
                    'payment' => $payment->id,
                    'de' => $payment->gateway,
                    'vers' => $gateway->key(),
                    'reference_abandonnee' => $payment->gateway_ref,
                ]);
            }

            $payment->update([
                'method' => $gateway->key(),
                'gateway' => $gateway->key(),
                'gateway_ref' => null,
            ]);
        }

        // Opérateur choisi chez nous (pré-sélection sur la page du prestataire).
        $payment->setOperator($request->input('operator'));

        try {
            $result = $gateway->initiate($payment);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['pay' => 'Le service de paiement est momentanément indisponible. Réessayez plus tard.']);
        }

        // On RESTE sur la page : soit le formulaire du prestataire s'y affiche,
        // soit la demande est partie sur le téléphone et l'on attend le verdict.
        if (in_array($result['type'], ['embed', 'push'], true)) {
            return back()->with('ukc_pay_embed', PaymentEmbed::payload($result, $payment, $gateway->key()));
        }

        if ($result['type'] === 'redirect' && ! empty($result['url'])) {
            return redirect()->away($result['url']);
        }

        return back()->with('pay_instructions', $result['message'] ?? 'Votre demande de paiement est enregistrée.');
    }
}
