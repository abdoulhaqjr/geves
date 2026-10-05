<?php

namespace App\Http\Controllers\Public;

use App\Actions\AskGesco;
use App\Http\Controllers\Controller;
use App\Models\Attestation;
use App\Models\Claim;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use App\Models\User;
use App\Support\AiAgents;
use App\Support\Notifier;
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
        $avatarUrl = filled($agent['avatar']) ? '/storage/'.ltrim((string) $agent['avatar'], '/') : null;

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
            // Identifiant de la discussion en cours (généré côté client, remis à zéro
            // par « Nouvelle discussion ») : rattache les tours d'une même conversation
            // à UNE seule réclamation.
            'conversation_id' => ['nullable', 'string', 'max:64'],
        ]);

        // Limite d'usage du chatbot public : X questions par JOUR et par utilisateur
        // (par IP pour un visiteur anonyme). Les administrateurs et gestionnaires
        // (capacité editions_write) ne sont PAS limités.
        $requester = Auth::guard('account')->user() ?? Auth::guard('web')->user();
        $unlimited = $requester instanceof User
            && ($requester->isColloqueSuperAdmin() || $requester->canAnyEdition('editions_write'));

        if (! $unlimited) {
            $scope = $requester ? 'u'.$requester->id : 'ip'.$request->ip();

            // 1) Rate-limit court terme (anti-flood) : N questions / heure.
            $burstKey = 'gesco-chat-burst:'.$scope;
            if (RateLimiter::tooManyAttempts($burstKey, max(1, (int) config('services.ai.public_rate_limit', 20)))) {
                return response()->json([
                    'answer' => 'Vous envoyez des messages trop rapidement. Merci de patienter un instant avant de réessayer.',
                ]);
            }

            // 2) Limite journalière : N questions / jour.
            $limit = max(1, (int) config('services.ai.public_daily_limit', 30));
            $dayKey = 'gesco-chat:'.now()->toDateString().':'.$scope;
            if (RateLimiter::tooManyAttempts($dayKey, $limit)) {
                return response()->json([
                    'answer' => 'Vous avez atteint la limite de '.$limit.' questions pour aujourd’hui. '
                        .'Merci de réessayer demain, ou de contacter la DRI (dri@univkara.tg) pour une demande précise.',
                ]);
            }

            RateLimiter::hit($burstKey, 3600);  // fenêtre 1 h
            RateLimiter::hit($dayKey, 86400);   // fenêtre 24 h
        }

        // Normalise l'historique reçu au format attendu par l'action.
        $history = collect($data['history'] ?? [])
            ->map(fn ($t) => ['role' => (string) ($t['role'] ?? 'bot'), 'text' => (string) ($t['text'] ?? '')])
            ->filter(fn ($t) => $t['text'] !== '')
            ->values()
            ->all();

        // Contexte personnel (données réelles) si un participant est connecté.
        $account = Auth::guard('account')->user();
        $personal = $account ? $this->personalContext($account) : null;
        $contact = $account ? ['name' => $account->full_name, 'email' => $account->email] : null;
        $event = $this->activeEdition();

        $claimRef = null;
        try {
            $result = $ask->handle($history, trim($data['question']), $event, $personal, $contact);
            $answer = $result['answer'];

            // Gesco n'a pas pu résoudre → enregistrement d'une réclamation/incident.
            if ($result['claim'] !== null) {
                $recorded = $this->recordClaim($result['claim'], $account, $event, $data['conversation_id'] ?? null);
                if ($recorded !== null) {
                    $claimRef = $recorded['reference'];
                    $answer .= $recorded['updated']
                        ? "\n\nVotre demande (**référence {$claimRef}**) a été mise à jour avec ces précisions. "
                            .'Elle sera traitée par l’organisation.'
                        : "\n\nVotre demande a été enregistrée. **Référence de suivi : {$claimRef}**. "
                            .'Elle sera traitée par l’organisation.';
                }
            }
        } catch (\Throwable $e) {
            $answer = $e->getMessage();
        }

        return response()->json(['answer' => $answer, 'claim' => $claimRef ? ['reference' => $claimRef] : null]);
    }

    /**
     * Enregistre (ou MET À JOUR) une réclamation/incident préparé par Gesco, si un
     * e-mail de contact est disponible, et notifie le staff à la création.
     *
     * Au fil d'une même discussion (même `conversation_id`), les tours suivants -
     * souvent des précisions que Gesco a demandées - **modifient la réclamation
     * existante** au lieu d'en créer une nouvelle : une seule demande, une seule
     * référence. Une nouvelle réclamation n'est créée que pour une nouvelle
     * conversation, ou quand la précédente a déjà été traitée (résolue/clôturée).
     *
     * @param  array<string, mixed>  $claim
     * @return array{reference: string, updated: bool}|null
     */
    private function recordClaim(array $claim, ?User $account, ?Event $event, ?string $conversationId = null): ?array
    {
        $email = $account?->email ?: (filled($claim['contactEmail'] ?? null) ? (string) $claim['contactEmail'] : null);
        if ($email === null) {
            return null; // pas de contact fiable : Gesco redemandera l'e-mail
        }

        $conversationId = filled($conversationId) ? (string) $conversationId : null;

        $attributes = [
            'event_id' => $event?->id,
            'user_id' => $account?->id,
            'name' => $account?->full_name ?: ((string) ($claim['contactName'] ?? '') ?: null),
            'email' => $email,
            'type' => (string) ($claim['type'] ?? 'reclamation'),
            'category' => filled($claim['category'] ?? null) ? (string) $claim['category'] : null,
            'subject' => (string) ($claim['subject'] ?? 'Réclamation'),
            'message' => (string) ($claim['summary'] ?? ''),
        ];

        // Réclamation déjà ouverte pour CETTE conversation : on l'enrichit des
        // dernières précisions plutôt que d'en créer une nouvelle. On la restreint
        // au même demandeur (garde-fou si un identifiant de conversation fuyait).
        $existing = $conversationId !== null
            ? Claim::query()
                ->where('conversation', $conversationId)
                ->where('source', 'chatbot')
                ->whereIn('status', ['open', 'in_progress'])
                ->when($account, fn ($q) => $q->where('user_id', $account->id), fn ($q) => $q->whereNull('user_id')->where('email', $email))
                ->latest('id')
                ->first()
            : null;

        if ($existing !== null) {
            // On ne rétrograde jamais le travail du staff : le statut et l'affectation
            // restent, seuls le contenu et la catégorie suivent la conversation.
            $existing->fill($attributes)->save();

            return ['reference' => $existing->reference, 'updated' => true];
        }

        $record = Claim::create($attributes + [
            'status' => 'open',
            'source' => 'chatbot',
            'conversation' => $conversationId,
        ]);

        Notifier::staff(
            kind: 'contact',
            title: 'Nouvelle '.mb_strtolower($record->typeLabel()),
            body: $record->subject.' - '.($record->name ?: $record->email),
            url: route('admin.claims.show', $record),
            icon: 'inbox',
        );

        return ['reference' => $record->reference, 'updated' => false];
    }

    /**
     * Résumé des données réelles du participant connecté, pour que Gesco réponde
     * à ses questions personnelles (soumissions, paiement, badge, attestations).
     */
    private function personalContext(User $account): string
    {
        $participantIds = Participant::where('user_id', $account->id)->pluck('id');

        $head = 'Utilisateur connecté : '.$account->full_name.' ('.$account->email.').'
            .' Profil : '.($account->affiliation ?: 'affiliation non renseignée').($account->grade ? ' · '.$account->grade : '').'.';

        if ($participantIds->isEmpty()) {
            return $head.' Aucune participation enregistrée : aucune soumission, aucun paiement, aucun document pour l’instant.';
        }

        $subs = Submission::whereIn('participant_id', $participantIds)
            ->where('revision_of', 0)->with('event')->latest('id')->get();

        $subLines = $subs->map(fn (Submission $s) => '- « '.Str::limit((string) $s->title, 70).' » ('
            .($s->event?->title ?: $s->event?->year).') - statut : '.$s->statusLabel()
            .($s->track ? ' · axe '.$s->track : '').($s->ref_code ? ' · réf '.$s->ref_code : ''))->implode("\n");

        $accepted = $subs->where('status', 'accepted')->count();
        $paidCount = Payment::whereIn('participant_id', $participantIds)->forParticipation()->where('status', 'paid')->count();
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
            'Badge participant : '.($paidCount > 0 ? 'disponible (généré après paiement), non téléchargeable ici - imprimé par l’organisation.' : 'sera disponible après confirmation du paiement.'),
            'Attestations : '.($att->isNotEmpty() ? $att->map->typeLabel()->unique()->implode(', ') : 'aucune émise pour l’instant').'.',
            $account->hasVerifiedEmail() ? 'E-mail vérifié.' : 'E-mail NON vérifié (à confirmer pour soumettre).',
        ]));
    }

    private function activeEdition(): ?Event
    {
        return Event::with('series')->where('status', 'open')->latest('id')->first();
    }
}
