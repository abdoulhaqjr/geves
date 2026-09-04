<?php

namespace App\Http\Controllers\Public;

use App\Actions\AskGesco;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use App\Support\AiAgents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Assistante Gesco (page dédiée + endpoint JSON), sans Livewire. La page rend
 * une coquille Blade ; la conversation est pilotée côté client (Alpine) et
 * chaque tour appelle {@see ask()} en JSON. Gesco informe et oriente : elle ne
 * réalise aucune action à la place de l'utilisateur.
 */
class GescoController extends Controller
{
    /** Page dédiée /assistante. */
    public function page(): View
    {
        // Identité de l'assistante (nom + avatar) paramétrée dans Réglages · Agents IA.
        $agent = AiAgents::find('gesco') ?? AiAgents::defaults()[0];
        $avatarUrl = filled($agent['avatar'] ?? null) ? '/storage/'.ltrim((string) $agent['avatar'], '/') : null;

        return view('public.gesco', [
            'agentName' => $agent['name'] ?: 'Gesco',
            'agentAvatar' => $avatarUrl,
        ]);
    }

    /** Endpoint conversationnel : reçoit l'historique + la question, renvoie la réponse. */
    public function ask(Request $request, AskGesco $ask): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'history' => ['array'],
            'history.*.role' => ['string'],
            'history.*.text' => ['string'],
        ]);

        $key = 'gesco:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json([
                'answer' => 'Vous avez envoyé beaucoup de messages. Merci de réessayer un peu plus tard.',
            ]);
        }
        RateLimiter::hit($key, 3600);

        // Normalise l'historique reçu au format attendu par l'action.
        $history = collect($data['history'] ?? [])
            ->map(fn ($t) => ['role' => (string) ($t['role'] ?? 'bot'), 'text' => (string) ($t['text'] ?? '')])
            ->filter(fn ($t) => $t['text'] !== '')
            ->values()
            ->all();

        // Contexte personnel (données réelles) si un participant est connecté.
        $account = Auth::guard('account')->user();
        $personal = $account ? $this->personalContext($account) : null;

        try {
            $answer = $ask->handle($history, trim($data['question']), $this->activeEdition(), $personal);
        } catch (\Throwable $e) {
            $answer = $e->getMessage();
        }

        return response()->json(['answer' => $answer]);
    }

    /**
     * Résumé des données réelles du participant connecté, pour que Gesco réponde
     * à ses questions personnelles (soumissions, paiement, badge, attestations).
     */
    private function personalContext(Account $account): string
    {
        $participantIds = Participant::where('account_id', $account->id)->pluck('id');

        $head = 'Utilisateur connecté : '.$account->full_name.' ('.$account->email.').'
            .' Profil : '.($account->affiliation ?: 'affiliation non renseignée').($account->grade ? ' · '.$account->grade : '').'.';

        if ($participantIds->isEmpty()) {
            return $head.' Aucune participation enregistrée : aucune soumission, aucun paiement, aucun document pour l’instant.';
        }

        $subs = Submission::whereIn('participant_id', $participantIds)
            ->where('revision_of', 0)->with('edition')->latest('id')->get();

        $subLines = $subs->map(fn (Submission $s) => '- « '.Str::limit((string) $s->title, 70).' » ('
            .($s->edition?->title ?: $s->edition?->year).') — statut : '.$s->statusLabel()
            .($s->track ? ' · axe '.$s->track : '').($s->ref_code ? ' · réf '.$s->ref_code : ''))->implode("\n");

        $accepted = $subs->where('status', 'accepted')->count();
        $paidCount = Payment::whereIn('participant_id', $participantIds)->where('status', 'paid')->count();
        $pending = Payment::whereIn('participant_id', $participantIds)->where('status', 'pending')->count();
        $att = Attestation::whereIn('participant_id', $participantIds)->where('revoked', false)->get();

        $paymentLine = match (true) {
            $paidCount > 0 => 'Frais d’inscription RÉGLÉS (reçu disponible dans Documents).',
            $accepted > 0 => 'Frais d’inscription À RÉGLER (au moins une communication acceptée)'.($pending > 0 ? ', un paiement est en attente' : '').'.',
            default => 'Paiement non requis tant qu’aucune communication n’est acceptée.',
        };

        return implode("\n", array_filter([
            $head,
            'Communications ('.$subs->count().') :',
            $subs->isEmpty() ? '- aucune soumission déposée' : $subLines,
            $paymentLine,
            'Badge participant : '.($paidCount > 0 ? 'disponible (généré après paiement), non téléchargeable ici — imprimé par l’organisation.' : 'sera disponible après confirmation du paiement.'),
            'Attestations : '.($att->isNotEmpty() ? $att->map->typeLabel()->unique()->implode(', ') : 'aucune émise pour l’instant').'.',
            $account->hasVerifiedEmail() ? 'E-mail vérifié.' : 'E-mail NON vérifié (à confirmer pour soumettre).',
        ]));
    }

    private function activeEdition(): ?Edition
    {
        return Edition::with('colloque')->where('status', 'open')->latest('id')->first();
    }
}
