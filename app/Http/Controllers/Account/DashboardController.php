<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Attestation;
use App\Models\Edition;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Slot;
use App\Models\Submission;
use App\Support\AccountActivity;
use App\Support\CurrentColloque;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Tableau de bord du participant (Blade, fidèle au mockup espace-participant.html) :
 * parcours (stepper), prochaine étape, et blocs personnels - communications,
 * paiements & reçus, attestations, documents, sessions en direct, rôles dérivés.
 * Lecture seule → Blade.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $account = Auth::guard('account')->user();

        // Colloque courant (sélecteur) : le tableau de bord se scope dessus.
        $current = CurrentColloque::current($account);

        $participantIds = Participant::where('user_id', $account->id)
            ->when($current, fn ($q) => $q->where('edition_id', $current->id))
            ->pluck('id');

        // Rôles et activité dérivés (inter-éditions, par rapprochement d'e-mail).
        $activity = new AccountActivity($account);

        // Documents du colloque courant (médias + livret).
        $editionIds = $current
            ? collect([$current->id])
            : Participant::where('user_id', $account->id)->pluck('edition_id')->unique()->filter();

        // Sessions en direct : uniquement les séances EN COURS aujourd'hui qui le
        // concernent - soit il y présente une communication, soit elles portent sur
        // l'un de ses axes thématiques.
        $mySubmissionIds = Submission::whereIn('participant_id', $participantIds)
            ->where('revision_of', 0)->pluck('id');
        $myTracks = Submission::whereIn('id', $mySubmissionIds)
            ->pluck('track')->filter()
            ->map(fn ($t) => mb_strtolower(trim((string) $t)))->unique();
        $mySessionIds = Slot::whereIn('submission_id', $mySubmissionIds)
            ->whereNotNull('session_id')->pluck('session_id')->unique();

        // Agenda personnel : le participant a-t-il au moins un créneau daté ?
        $hasAgenda = Slot::whereNotNull('starts_at')
            ->where(fn ($q) => $q
                ->whereIn('submission_id', $mySubmissionIds)
                ->orWhereIn('participant_id', $participantIds))
            ->exists();

        $now = Carbon::now();
        $liveSessions = EventSession::query()->live()
            ->whereIn('edition_id', $editionIds)
            ->with(['edition', 'atelier'])
            ->get()
            ->filter(function (EventSession $s) use ($now, $account, $mySessionIds, $myTracks) {
                // En cours maintenant (fenêtre horaire de la séance).
                $inProgress = $s->starts_at && $s->ends_at && $s->starts_at->lte($now) && $s->ends_at->gte($now);
                if (! $inProgress || ! $s->canJoin($account)) {
                    return false;
                }
                // Le participant y présente une communication ?
                if ($mySessionIds->contains($s->id)) {
                    return true;
                }
                // La séance porte-t-elle sur l'un de ses axes thématiques ?
                $sessionTracks = collect($s->atelier?->tracks ?? [])
                    ->map(fn ($t) => mb_strtolower(trim((string) $t)));

                return $sessionTracks->intersect($myTracks)->isNotEmpty();
            })
            ->values();

        $editionDocuments = Edition::whereIn('id', $editionIds)
            ->with(['media' => fn ($q) => $q->orderBy('ordre')->orderBy('id')])
            ->latest('id')
            ->get()
            ->map(fn (Edition $e) => [
                'edition' => $e,
                'media' => $e->media,
                'hasBooklet' => $e->submissions()
                    ->where('revision_of', 0)->where('status', 'accepted')->exists(),
            ])
            ->filter(fn ($d) => $d['media']->isNotEmpty() || $d['hasBooklet'])
            ->values();

        // Édition où une communication est acceptée et les frais non encore réglés.
        $payable = Participant::whereIn('id', $participantIds)
            ->whereHas('submissions', fn ($q) => $q->where('revision_of', 0)->where('status', 'accepted'))
            ->whereDoesntHave('participationPayments', fn ($q) => $q->where('status', 'paid'))
            ->with('edition')
            ->first();

        $openEdition = Edition::where('status', 'open')
            ->where('submission_open', true)
            ->latest('id')
            ->first();

        // On soumet TOUJOURS dans le colloque sélectionné. Repli sur l'appel ouvert
        // uniquement pour un compte sans aucune participation (pas de colloque à sélectionner).
        $submitEdition = $current ?? $openEdition;
        // Contrôle en amont (avant la saisie) : place restante dans ce colloque.
        $submitRemaining = $submitEdition ? Submission::remainingFor($submitEdition, $account) : 0;

        // Indicateurs synthétiques (rangée de cartes KPI du mockup) :
        // communications, profil, paiement, jours restants avant l'échéance.
        $commsCount = Submission::whereIn('participant_id', $participantIds)
            ->where('revision_of', 0)->count();
        // ⚠️ Seule la PARTICIPATION compte : une formation réglée est rattachée au
        // même participant mais ne vaut pas inscription à l'événement.
        $hasPaid = Payment::whereIn('participant_id', $participantIds)
            ->forParticipation()->where('status', 'paid')->exists();
        $deadlineEdition = $payable?->edition ?? $openEdition;
        $deadline = $deadlineEdition?->submission_deadline;
        $daysRemaining = $deadline && $deadline->isFuture()
            ? (int) ceil(now()->diffInDays($deadline, false))
            : null;

        // Édition de contexte (pour le bandeau d'accueil : dates & lieu du colloque)
        // = colloque courant sélectionné, avec replis.
        $contextEdition = $current ?? $payable?->edition ?? $openEdition
            ?? Edition::whereIn('id', $editionIds)->latest('id')->first();

        return view('account.dashboard', [
            'account' => $account,
            'openEdition' => $openEdition,
            'submitEdition' => $submitEdition,
            'submitRemaining' => $submitRemaining,
            'payableEdition' => $payable?->edition,
            'contextEdition' => $contextEdition,
            'kpi' => [
                'communications' => $commsCount,
                // Règle unique (identité + téléphone + affiliation + e-mail vérifié) :
                // c'est aussi ce qui conditionne le paiement d'une formation.
                'profileComplete' => $account->profileComplete(),
                'hasPaid' => $hasPaid,
                'daysRemaining' => $daysRemaining,
                'deadline' => $deadline,
            ],
            'submissions' => Submission::whereIn('participant_id', $participantIds)
                ->where('revision_of', 0)->with('edition')->latest('id')->limit(5)->get(),
            'payments' => Payment::whereIn('participant_id', $participantIds)
                ->forParticipation()->where('status', 'paid')->with('edition')->latest('id')->get(),
            'attestations' => Attestation::whereIn('participant_id', $participantIds)
                ->where('revoked', false)->with('edition')->latest('id')->get(),
            'editionDocuments' => $editionDocuments,
            'liveSessions' => $liveSessions,
            'hasAgenda' => $hasAgenda,
            'roles' => $activity->roles(),
            'moderatedSessions' => $activity->moderatedSessions(),
            'reportedSessions' => $activity->reportedSessions(),
            'committees' => $activity->committees(),
            'isReviewer' => $activity->isReviewer(),
            'pendingReviews' => $activity->isReviewer()
                ? Review::whereIn('reviewer_id', Participant::where('user_id', $account->id)
                    ->where('role', 'reviewer')->pluck('id'))
                    ->where('status', '!=', 'completed')->count()
                : 0,
        ]);
    }
}
