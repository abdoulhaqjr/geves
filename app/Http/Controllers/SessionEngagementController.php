<?php

namespace App\Http\Controllers;

use App\Enums\MeetingRole;
use App\Models\Account;
use App\Models\EventSession;
use App\Models\SessionQuestion;
use App\Models\SessionQuestionVote;
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

    /** Liste des questions (JSON) — triées par votes puis ancienneté. */
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
                ->where('account_id', $account->id)->pluck('question_id')->flip()
            : collect();

        $questions = $query->orderByDesc('votes_count')->orderBy('id')->get()
            ->map(fn (SessionQuestion $q) => [
                'id' => (int) $q->id,
                'author' => $q->author_name ?: 'Anonyme',
                'body' => $q->body,
                'status' => $q->status,
                'votes' => (int) $q->votes_count,
                'mine' => $account && (int) $q->account_id === (int) $account->id,
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
            'account_id' => $account->id,
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
            ->where('account_id', $account->id)->first();

        if ($existing) {
            $existing->delete();
            $voted = false;
        } else {
            SessionQuestionVote::create(['question_id' => $question->id, 'account_id' => $account->id]);
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

    /**
     * Résout le visiteur : [account|null, isStaff, MeetingRole].
     *
     * @return array{0: Account|null, 1: bool, 2: MeetingRole}
     */
    private function viewer(EventSession $session): array
    {
        $account = Auth::guard('account')->user();
        $isStaff = Auth::guard('web')->check();

        return [$account, $isStaff, $session->meetingRoleFor($account, $isStaff)];
    }
}
