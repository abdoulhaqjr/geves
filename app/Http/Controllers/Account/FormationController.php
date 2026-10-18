<?php

namespace App\Http\Controllers\Account;

use App\Actions\UpsertParticipantForAccount;
use App\Http\Controllers\Controller;
use App\Mail\FormationEnrollmentMail;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Payment;
use App\Payments\GatewayRegistry;
use App\Support\CurrentEvent;
use App\Support\EventLock;
use App\Support\EventPaymentConfig;
use App\Support\Notifier;
use App\Support\PaymentEmbed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Espace participant : formations de l'**événement sélectionné** (une formation
 * appartient toujours à un événement = l'événement), avec inscription. Gratuite →
 * inscription directe ; payante → paiement via la couche Payment
 * (`meta.flow = 'formation'`). Si l'événement courant ne propose pas de formation
 * (module désactivé ou aucune formation publiée), la page n'existe pas (404) et
 * le lien du menu est masqué.
 */
class FormationController extends Controller
{
    public function index(Request $request, GatewayRegistry $registry): View
    {
        $user = $request->user();
        $event = CurrentEvent::current($user);
        abort_unless(Formation::offeredIn($event), 404);

        $formations = Formation::with('trainers')
            ->publishedIn($event)
            ->orderByRaw('starts_at is null, starts_at')
            ->get();

        $enrollments = FormationEnrollment::where('user_id', $user->id)
            ->whereIn('formation_id', $formations->pluck('id'))
            ->get()->keyBy('formation_id');

        return view('account.formations.index', [
            'event' => $event,
            'formations' => $formations,
            'enrollments' => $enrollments,
            'gateways' => $registry->available(),
        ]);
    }

    public function enroll(Request $request, Formation $formation, UpsertParticipantForAccount $upsert, GatewayRegistry $registry): RedirectResponse
    {
        abort_unless($formation->published && (bool) $formation->event?->formationEnabled(), 404);
        EventLock::guard($formation->event);
        EventPaymentConfig::apply($formation->event); // passerelle propre à l'événement
        $user = $request->user();
        $back = redirect()->route('account.formations');

        // Un formateur anime la formation : il n'a ni à s'inscrire ni à payer
        // (il rejoint la visio en modérateur).
        if ($formation->isTrainer($user)) {
            return $back->with('ukc_status', 'Vous animez cette formation : aucune inscription n’est requise.');
        }

        // Formation payante : le profil doit être complet (identité, téléphone,
        // affiliation) - le téléphone pré-remplit la page de la passerelle.
        if (! $formation->isFree() && ! $user->profileComplete()) {
            return redirect()->route('account.profile')->with(
                'ukc_error',
                'Complétez votre profil avant de régler une formation ('.implode(', ', $user->missingProfileFields()).').',
            );
        }

        // Participant de l'événement (compte unifié ; ne rétrograde pas un rôle fort).
        $participant = $upsert->handle($formation->event, $user, role: 'trainee');

        $enrollment = FormationEnrollment::firstOrCreate(
            ['formation_id' => $formation->id, 'user_id' => $user->id],
            [
                'event_id' => $formation->event_id,
                'participant_id' => $participant->id,
                'amount' => (float) $formation->fee,
                'status' => 'registered',
            ],
        );

        // Confirmation d'inscription (une seule fois, à la création) : e-mail dédié
        // (gaté par la préférence) + notification in-app.
        if ($enrollment->wasRecentlyCreated) {
            if ($user->email && Notifier::emailAllowed($user, 'formation')) {
                Mail::to($user->email)->send(new FormationEnrollmentMail($enrollment));
            }
            Notifier::account(
                $user,
                kind: 'formation',
                title: __('Inscription à une formation'),
                body: __('Votre inscription à « :titre » est enregistrée.', ['titre' => $formation->title]),
                url: route('account.formations'),
                icon: 'book',
            );
        }

        if ($enrollment->isPaid()) {
            return $back->with('ukc_status', 'Vous êtes déjà inscrit(e) à cette formation.');
        }

        // Formation gratuite : inscription immédiate, sans paiement.
        if ($formation->isFree()) {
            return $back->with('ukc_status', 'Inscription confirmée.');
        }

        // Formation payante : règlement via une passerelle.
        $gateway = $registry->get((string) $request->input('gateway'));
        if (! $gateway || ! $gateway->isAvailable()) {
            return $back->withErrors(['pay' => 'Ce moyen de paiement n’est pas disponible.']);
        }

        $payment = Payment::create([
            'event_id' => $formation->event_id,
            'participant_id' => $participant->id,
            'amount' => (float) $formation->fee,
            'method' => $gateway->key(),
            'gateway' => $gateway->key(),
            'meta' => ['flow' => 'formation', 'formation_id' => $formation->id, 'enrollment_id' => $enrollment->id],
        ]);
        $enrollment->update(['payment_id' => $payment->id]);
        // Opérateur choisi chez nous (pré-sélection sur la page du prestataire).
        $payment->setOperator($request->input('operator'));

        try {
            $result = $gateway->initiate($payment);
        } catch (\Throwable) {
            return $back->withErrors(['pay' => 'Le service de paiement est momentanément indisponible. Réessayez plus tard.']);
        }

        // On RESTE sur la page : soit le formulaire du prestataire s'y affiche,
        // soit la demande est partie sur le téléphone et l'on attend le verdict.
        if (in_array($result['type'], ['embed', 'push'], true)) {
            return $back->with('ukc_pay_embed', PaymentEmbed::payload($result, $payment, $gateway->key()));
        }

        if ($result['type'] === 'redirect' && ! empty($result['url'])) {
            return redirect()->away($result['url']);
        }

        return $back->with('pay_instructions', $result['message'] ?? 'Votre demande de paiement est enregistrée.');
    }
}
