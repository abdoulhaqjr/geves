<?php

namespace App\Http\Controllers;

use App\Enums\MeetingRole;
use App\Models\EventSession;
use App\Models\SessionPoll;
use App\Models\SessionPollVote;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionVote;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Engagement live d'une séance (contrôleur + Blade + endpoints JSON, sans
 * Livewire) : espace d'échange « à côté » de la visio pour poser des questions
 * (Q&A voté par le public, modéré) et répondre aux sondages. Rafraîchissement
 * par fetch périodique (pas de WebSocket requis). L'accès reprend les règles de
 * la visio (participant inscrit / séance publique / staff / modérateur).
 */
class SessionEngagementController extends Controller
{
    /** Page d'échange d'une séance. */
    public function page(EventSession $session): View
    {
        [$account, $isStaff, $role] = $this->viewer($session);
        abort_unless($role->canJoin(), 403);

        return view('session.engagement', [
            'session' => $session->load('edition'),
            'canModerate' => $role->isModerator() || $isStaff,
            'canPost' => $account !== null,
            'isGuest' => ! $account && ! $isStaff,
        ]);
    }

    /** Liste des questions (JSON) - triées par votes puis ancienneté. */
    public function questions(EventSession $session): JsonResponse
    {
        [$account, $isStaff, $role] = $this->viewer($session);
        abort_unless($role->canJoin(), 403);

        $canModerate = $role->isModerator() || $isStaff;

        $query = SessionQuestion::where('session_id', $session->id)->withCount('votes');
        if (! $canModerate) {
            $query->where('status', '!=', 'hidden');
        }

        $myVotes = $account
            ? SessionQuestionVote::whereHas('question', fn ($q) => $q->where('session_id', $session->id))
                ->where('user_id', $account->id)->pluck('question_id')->flip()
            : collect();

        $questions = $query->orderByDesc('votes_count')->orderBy('id')->get()
            ->map(fn (SessionQuestion $q) => [
                'id' => (int) $q->id,
                'author' => $q->author_name ?: 'Anonyme',
                'body' => $q->body,
                'status' => $q->status,
                'votes' => (int) $q->votes_count,
                'mine' => $account && (int) $q->user_id === (int) $account->id,
                'voted' => $myVotes->has($q->id),
            ])->values();

        return response()->json([
            'questions' => $questions,
            'canModerate' => $canModerate,
            'canPost' => $account !== null,
        ]);
    }

    /** Le public pose une question. */
    public function ask(Request $request, EventSession $session): JsonResponse
    {
        [$account, , $role] = $this->viewer($session);
        abort_unless($role->canJoin(), 403);
        abort_if($account === null, 403, 'Connectez-vous pour poser une question.');

        $data = $request->validate(['body' => ['required', 'string', 'max:600']]);

        SessionQuestion::create([
            'session_id' => $session->id,
            'edition_id' => $session->edition_id,
            'user_id' => $account->id,
            'author_name' => $account->full_name,
            'body' => trim($data['body']),
            'status' => 'pending',
        ]);

        return response()->json(['ok' => true]);
    }

    /** Bascule le vote (« +1 ») du public sur une question. */
    public function vote(EventSession $session, SessionQuestion $question): JsonResponse
    {
        [$account, , $role] = $this->viewer($session);
        abort_unless($role->canJoin(), 403);
        abort_if($account === null, 403);
        abort_unless((int) $question->session_id === (int) $session->id, 404);

        $existing = SessionQuestionVote::where('question_id', $question->id)
            ->where('user_id', $account->id)->first();

        if ($existing) {
            $existing->delete();
            $voted = false;
        } else {
            SessionQuestionVote::create(['question_id' => $question->id, 'user_id' => $account->id]);
            $voted = true;
        }

        return response()->json([
            'voted' => $voted,
            'votes' => SessionQuestionVote::where('question_id', $question->id)->count(),
        ]);
    }

    /** Modération : marque une question répondue / masquée / en attente. */
    public function moderate(Request $request, EventSession $session, SessionQuestion $question): JsonResponse
    {
        [, $isStaff, $role] = $this->viewer($session);
        abort_unless($role->isModerator() || $isStaff, 403);
        abort_unless((int) $question->session_id === (int) $session->id, 404);

        $data = $request->validate(['status' => ['required', 'in:'.implode(',', SessionQuestion::STATUSES)]]);

        $question->update([
            'status' => $data['status'],
            'answered_at' => $data['status'] === 'answered' ? now() : null,
        ]);

        return response()->json(['ok' => true, 'status' => $question->status]);
    }

    // ---- Sondages ------------------------------------------------------------

    /** Liste des sondages (JSON) avec résultats agrégés et le choix du visiteur. */
    public function polls(EventSession $session): JsonResponse
    {
        [$account, $isStaff, $role] = $this->viewer($session);
        abort_unless($role->canJoin(), 403);

        $canModerate = $role->isModerator() || $isStaff;

        $polls = SessionPoll::where('session_id', $session->id)
            ->with('votes')
            ->when(! $canModerate, fn ($q) => $q->where('status', 'open'))
            ->orderByDesc('id')->get()
            ->map(function (SessionPoll $poll) use ($account) {
                $options = $poll->options ?? [];
                $counts = array_fill(0, count($options), 0);
                $mine = null;
                foreach ($poll->votes as $vote) {
                    if (isset($counts[$vote->choice])) {
                        $counts[$vote->choice]++;
                    }
                    if ($account && (int) $vote->user_id === (int) $account->id) {
                        $mine = (int) $vote->choice;
                    }
                }

                return [
                    'id' => (int) $poll->id,
                    'question' => $poll->question,
                    'options' => $options,
                    'counts' => $counts,
                    'total' => array_sum($counts),
                    'status' => $poll->status,
                    'mine' => $mine,
                ];
            })->values();

        return response()->json(['polls' => $polls, 'canModerate' => $canModerate]);
    }

    /** Le modérateur crée un sondage. */
    public function createPoll(Request $request, EventSession $session): JsonResponse
    {
        [, $isStaff, $role] = $this->viewer($session);
        abort_unless($role->isModerator() || $isStaff, 403);

        $data = $request->validate([
            'question' => ['required', 'string', 'max:300'],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*' => ['nullable', 'string', 'max:150'],
        ]);

        $options = array_values(array_filter(array_map(
            fn ($o) => trim((string) $o), $data['options']
        ), fn ($o) => $o !== ''));

        if (count($options) < 2) {
            return response()->json(['error' => 'Renseignez au moins deux options.'], 422);
        }

        SessionPoll::create([
            'session_id' => $session->id,
            'edition_id' => $session->edition_id,
            'question' => trim($data['question']),
            'options' => $options,
            'status' => 'open',
        ]);

        return response()->json(['ok' => true]);
    }

    /** Le public vote (un choix par compte ; modifiable tant que le sondage est ouvert). */
    public function votePoll(Request $request, EventSession $session, SessionPoll $poll): JsonResponse
    {
        [$account, , $role] = $this->viewer($session);
        abort_unless($role->canJoin(), 403);
        abort_if($account === null, 403);
        abort_unless((int) $poll->session_id === (int) $session->id, 404);
        abort_unless($poll->isOpen(), 422, 'Ce sondage est clos.');

        $max = count($poll->options ?? []) - 1;
        $data = $request->validate(['choice' => ['required', 'integer', 'min:0', 'max:'.max(0, $max)]]);

        SessionPollVote::updateOrCreate(
            ['poll_id' => $poll->id, 'user_id' => $account->id],
            ['choice' => (int) $data['choice']],
        );

        return response()->json(['ok' => true]);
    }

    /** Le modérateur ouvre / ferme un sondage. */
    public function togglePoll(EventSession $session, SessionPoll $poll): JsonResponse
    {
        [, $isStaff, $role] = $this->viewer($session);
        abort_unless($role->isModerator() || $isStaff, 403);
        abort_unless((int) $poll->session_id === (int) $session->id, 404);

        $poll->update(['status' => $poll->isOpen() ? 'closed' : 'open']);

        return response()->json(['ok' => true, 'status' => $poll->status]);
    }

    /**
     * Résout le visiteur : [account|null, isStaff, MeetingRole].
     *
     * @return array{0: User|null, 1: bool, 2: MeetingRole}
     */
    private function viewer(EventSession $session): array
    {
        $account = Auth::guard('account')->user();
        $isStaff = Auth::guard('web')->check();

        return [$account, $isStaff, $session->meetingRoleFor($account, $isStaff)];
    }
}
