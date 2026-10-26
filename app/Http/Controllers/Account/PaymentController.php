<?php

namespace App\Http\Controllers\Account;

use App\Actions\RegisterAttendee;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Payment;
use App\Payments\GatewayRegistry;
use App\Support\EventLock;
use App\Support\EventPaymentConfig;
use App\Support\Fees;
use App\Support\ParticipantTypes;
use App\Support\PaymentEmbed;
use App\Support\SubmissionFormConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
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

        // ⚠️ `forParticipant` est LE point de résolution : il fait passer la
        // catégorie hors grade déclarée devant le grade. Résoudre ici depuis le
        // seul grade afficherait un montant que `pay()` ne facturerait pas.
        $applicable = Fees::forParticipant($participant);
        $amount = $applicable ?? ($participant ? Fees::highestAmount($event) : null);
        $applicableLabel = $applicable !== null ? Fees::labelForParticipant($participant) : null;

        return view('account.payment.pay', [
            'event' => $event,
            'frozen' => EventLock::frozen($event),
            'participant' => $participant,
            'participationModes' => (new SubmissionFormConfig($event))->participationModes(),
            'amount' => $amount,
            'tiers' => Fees::grid($event),
            'applicableLabel' => $applicableLabel,
            // Catégories hors grade proposées à la déclaration (ONG, entreprise…).
            'categoriesDeclarables' => Fees::selectable($event),
            // ⚠️ « Éligible » = IL Y A DES FRAIS À RÉGLER, pas « il a une
            // communication acceptée » : le participant simple en doit aussi.
            'eligible' => $participant && $participant->owesParticipationFees(),
            // Inscription possible : aucune fiche sur cet événement, et la
            // fenêtre d'inscription est ouverte.
            'canRegister' => ! $participant && $event->registrationIsOpen(),
            // ⚠️ Inscrit, mais le paiement des participants simples n'est pas
            // encore ouvert : il faut le lui DIRE. Le message générique parlait
            // d'une communication à faire accepter, ce qui ne le concerne pas.
            'attendRegleMentPourSimple' => $participant !== null
                && $participant->isRegisteredAttendee()
                && ! $participant->hasAcceptedSubmission()
                && ! $event->attendeePaymentIsOpen(),
            'attendeePaymentOpen' => $event->attendeePaymentIsOpen(),
            'grades' => ParticipantTypes::all(),
            'accountGrade' => $account?->grade,
            'accountAffiliation' => $account?->affiliation,
            'paid' => $participant && $participant->hasPaid(),
            'paidReceipt' => $participant?->participationPayments()->where('status', 'paid')->latest('id')->first(),
            // ⚠️ La passerelle RÉGLÉE pour l'événement, et elle seule : qui
            // encaisse en ligne ne propose pas le guichet (cf. `offered()`).
            'gateways' => app(GatewayRegistry::class)->offered(),
        ]);
    }

    /**
     * **S'inscrire à l'événement sans déposer de communication.**
     *
     * ⚠️ **Le grade est le seul champ qui décide de quelque chose** : c'est lui
     * qui résout le tarif ({@see Fees::resolveAmount()}). L'affiliation et le
     * mode de participation sont du confort, pré-remplis depuis le profil.
     *
     * ⚠️ **Pas de garde `hasPaid()` ici** : on ne paie pas en s'inscrivant, et
     * quelqu'un qui a déjà une fiche n'arrive pas sur ce formulaire. La garde
     * qui compte est celle de la FENÊTRE - une inscription hors délai doit être
     * refusée même si le bouton traîne encore dans une page ouverte.
     */
    public function register(Request $request, Event $event, RegisterAttendee $register): RedirectResponse
    {
        EventLock::guard($event);
        abort_unless($event->registrationIsOpen(), 403);

        $account = Auth::guard('account')->user();

        // ⚠️ Le grade reste EXIGÉ même quand une catégorie hors grade est
        // déclarée : il sert ailleurs (badge, registre, attestations) et c'est
        // lui qui reprendra la main si l'organisation retire la catégorie.
        $declarables = array_column(Fees::selectable($event), 'key');
        $data = $request->validate([
            'grade' => ['required', 'string', Rule::in(ParticipantTypes::all())],
            'affiliation' => ['nullable', 'string', 'max:200'],
            'participation_mode' => ['nullable', Rule::in(array_keys((new SubmissionFormConfig($event))->participationModes()))],
            'fee_category' => ['nullable', Rule::in(array_merge([''], $declarables))],
        ], [
            'grade.required' => 'Choisissez votre catégorie : c’est elle qui détermine votre tarif.',
            'fee_category.in' => 'Cette catégorie n’est pas proposée pour cet événement.',
        ]);

        $participant = $register->handle(
            $event,
            $account,
            $data['grade'],
            $data['affiliation'] ?? null,
            $data['participation_mode'] ?? null,
        );

        if (filled($data['fee_category'] ?? null)) {
            $participant->setFeeCategory($data['fee_category']);
            $participant->save();
        }

        return redirect()->route('account.payment', $event)
            ->with('ukc_status', 'Votre inscription est enregistrée. Il vous reste à régler vos frais.');
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
            ? (Fees::forParticipant($participant) ?? Fees::highestAmount($event))
            : null;

        if (! $participant || ! $participant->owesParticipationFees() || $participant->hasPaid() || ! $amount) {
            return back()->withErrors(['pay' => 'Paiement indisponible.']);
        }

        // ⚠️ On ferme la porte, pas seulement le formulaire : une clé postée à
        // la main ne doit pas contourner la passerelle réglée pour l'événement.
        $gateway = $registry->get((string) $request->input('gateway'));
        if (! $gateway || ! $registry->offers($gateway->key())) {
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
        // en ligne, repartait sur une ligne restée `manual`. Deux conséquences,
        // toutes deux silencieuses, et mesurées sur un vrai paiement :
        //
        // - `payments:reconcile` ne repasse que par la passerelle enregistrée :
        //   un règlement en ligne resté `manual` n'aurait **jamais** été
        //   revérifié, et le filet de sécurité ne couvrait pas cette ligne ;
        // - la comptabilité annonçait un encaissement au guichet pour un
        //   paiement en ligne.
        //
        // D'où le réalignement **avant** d'engager la passerelle.
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

        try {
            $result = $gateway->initiate($payment);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['pay' => 'Le service de paiement est momentanément indisponible. Réessayez plus tard.']);
        }

        // Formulaire embarqué : on RESTE sur la page, le prestataire s'y affiche.
        if ($result['type'] === 'embed') {
            return back()->with('ukc_pay_embed', PaymentEmbed::payload($result, $payment, $gateway->key()));
        }

        if ($result['type'] === 'redirect' && ! empty($result['url'])) {
            return redirect()->away($result['url']);
        }

        return back()->with('pay_instructions', $result['message'] ?? 'Votre demande de paiement est enregistrée.');
    }
}
