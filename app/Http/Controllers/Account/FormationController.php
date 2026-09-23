<?php

namespace App\Http\Controllers\Account;

use App\Actions\UpsertParticipantForAccount;
use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Participant;
use App\Models\Payment;
use App\Payments\GatewayRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Espace participant : formations proposées (publiées) des éditions ouvertes ou
 * auxquelles le compte participe, avec inscription. Gratuite → inscription
 * directe ; payante → paiement via la couche Payment (`meta.flow = 'formation'`).
 */
class FormationController extends Controller
{
    public function index(Request $request, GatewayRegistry $registry): View
    {
        $user = $request->user();
        $editionIds = Participant::where('user_id', $user->id)->pluck('edition_id')->all();

        $formations = Formation::with('edition.colloque', 'trainers')
            ->where('published', true)
            ->where(function ($q) use ($editionIds) {
                $q->whereHas('edition', fn ($e) => $e->where('status', 'open'))
                    ->orWhereIn('edition_id', $editionIds);
            })
            ->orderByRaw('starts_at is null, starts_at')
            ->get();

        $enrollments = FormationEnrollment::where('user_id', $user->id)
            ->get()->keyBy('formation_id');

        return view('account.formations.index', [
            'formations' => $formations,
            'enrollments' => $enrollments,
            'gateways' => $registry->available(),
        ]);
    }

    public function enroll(Request $request, Formation $formation, UpsertParticipantForAccount $upsert, GatewayRegistry $registry): RedirectResponse
    {
        abort_unless($formation->published, 404);
        $user = $request->user();
        $back = redirect()->route('account.formations');

        // Participant de l'édition (compte unifié ; ne rétrograde pas un rôle fort).
        $participant = $upsert->handle($formation->edition, $user, role: 'trainee');

        $enrollment = FormationEnrollment::firstOrCreate(
            ['formation_id' => $formation->id, 'user_id' => $user->id],
            [
                'edition_id' => $formation->edition_id,
                'participant_id' => $participant->id,
                'amount' => (float) $formation->fee,
                'status' => 'registered',
            ],
        );

        if ($enrollment->isPaid()) {
            return $back->with('ukc_status', 'Vous êtes déjà inscrit·e à cette formation.');
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
            'edition_id' => $formation->edition_id,
            'participant_id' => $participant->id,
            'amount' => (float) $formation->fee,
            'method' => $gateway->key(),
            'gateway' => $gateway->key(),
            'meta' => ['flow' => 'formation', 'formation_id' => $formation->id, 'enrollment_id' => $enrollment->id],
        ]);
        $enrollment->update(['payment_id' => $payment->id]);

        try {
            $result = $gateway->initiate($payment);
        } catch (\Throwable) {
            return $back->withErrors(['pay' => 'Le service de paiement est momentanément indisponible. Réessayez plus tard.']);
        }

        if ($result['type'] === 'redirect' && ! empty($result['url'])) {
            return redirect()->away($result['url']);
        }

        return $back->with('pay_instructions', $result['message'] ?? 'Votre demande de paiement est enregistrée.');
    }
}
