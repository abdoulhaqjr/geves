<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ParticipantInvitationMail;
use App\Models\Attestation;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use App\Support\AccountActivity;
use App\Support\EventParticipantRoster;
use App\Support\ListSort;
use App\Support\Notifier;
use App\Support\RosterRow;
use App\Support\Trend;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Participants d'un événement (Blade, fidèle au mockup) : rôle, paiement, invitation.
 * Filtres par query string ; invitations (individuelle ou groupée aux payés non
 * invités) en POST. L'aperçu du dossier est une vue en lecture seule.
 */
class ParticipantController extends Controller
{
    use Concerns\ScopesEditionAccess;

    public function index(Request $request, EventParticipantRoster $roster): View
    {
        $events = $this->accessibleEditions($request, 'participants');
        $editionId = (string) $request->query('event_id', '');
        if ($editionId === '') {
            $editionId = (string) ($events->firstWhere('status', 'open')?->id ?? $events->first()?->id ?? '');
        }
        $role = (string) $request->query('role', '');
        $participationMode = (string) $request->query('participation_mode', '');
        if (! in_array($participationMode, ['online', 'onsite'], true)) {
            $participationMode = '';
        }
        $search = trim((string) $request->query('search', ''));
        $paid = (string) $request->query('paid', '');
        $invited = (string) $request->query('invited', '');

        $all = $roster->forEvent($editionId);
        $sort = ListSort::resolve($request, ['last_name', 'role', 'created_at'], 'last_name', 'asc');
        $filtered = $all
            ->filter(fn (RosterRow $person) => $role === '' || ($role === 'communicant'
                ? $person->role !== 'attendee' : $person->role === $role))
            ->filter(fn (RosterRow $person) => $participationMode === '' || $person->participationMode === $participationMode)
            ->filter(fn (RosterRow $person) => $paid === '' || $person->paid === ($paid === '1'))
            ->filter(fn (RosterRow $person) => $invited === '' || $person->invited === ($invited === '1'))
            ->filter(fn (RosterRow $person) => $search === '' || str_contains(
                mb_strtolower($person->name.' '.$person->email), mb_strtolower($search)
            ));
        $filtered = $filtered->sort(function (RosterRow $a, RosterRow $b) use ($sort) {
            $comparison = match ($sort['sort']) {
                'role' => strcmp($a->role, $b->role),
                'created_at' => $a->createdAt <=> $b->createdAt,
                default => strcasecmp($a->name, $b->name),
            };

            return ($sort['dir'] === 'desc' ? -1 : 1) * ($comparison ?: ($b->participant->id <=> $a->participant->id));
        })->values();
        $page = max(1, LengthAwarePaginator::resolveCurrentPage());
        $participants = new LengthAwarePaginator(
            $filtered->forPage($page, 20)->values(), $filtered->count(), 20, $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        // Rôles d'organisation cumulés par personne sur CE colloque (event_user),
        // pour montrer qu'un participant peut aussi porter une fonction d'équipe.
        $editionRoles = $editionId === ''
            ? collect()
            : DB::table('event_user')
                ->where('event_id', $editionId)->get()
                ->groupBy('user_id')
                ->map(fn ($rows) => $rows->pluck('role')->unique()->all());

        // Tendance des inscriptions : 7 derniers jours vs les 7 précédents.
        $trendTotal = Trend::of(
            $all->filter(fn (RosterRow $p) => $p->createdAt?->between(now()->subDays(7), now()))->count(),
            $all->filter(fn (RosterRow $p) => $p->createdAt?->between(now()->subDays(14), now()->subDays(7)))->count(),
        );

        return view('admin.participants.index', [
            'trendTotal' => $trendTotal,
            'editionRoles' => $editionRoles,
            'sort' => $sort['sort'],
            'dir' => $sort['dir'],
            'events' => $events,
            'paid' => $paid,
            'invited' => $invited,
            'participationMode' => $participationMode,
            'participants' => $participants,
            'roles' => [
                'communicant' => 'Communicants',
                'author' => 'Auteurs correspondants',
                'coauthor' => 'Co-auteurs uniquement',
                'attendee' => 'Participants simples',
            ],
            'event_id' => $editionId,
            'role' => $role,
            'search' => $search,
            'stats' => [
                'total' => $all->count(),
                'paid' => $all->where('paid', true)->count(),
                'communicants' => $all->whereIn('role', ['author', 'coauthor'])->count(),
                'attendees' => $all->where('role', 'attendee')->count(),
            ],
        ]);
    }

    /** Aperçu en lecture seule du dossier d'un participant. */
    public function preview(Participant $participant): View
    {
        $participant->load('event', 'user');
        $id = $participant->id;

        return view('admin.participants.preview', [
            'participant' => $participant,
            'submissions' => Submission::where('participant_id', $id)->where('revision_of', 0)->latest('id')->get(),
            'payments' => Payment::where('participant_id', $id)->latest('id')->get(),
            'attestations' => Attestation::where('participant_id', $id)->where('revoked', false)->latest('id')->get(),
            'derivedRoles' => $participant->user ? (new AccountActivity($participant->user))->roles() : [],
        ]);
    }

    public function invite(Participant $participant, EventParticipantRoster $roster): RedirectResponse
    {
        abort_unless($roster->forEvent((string) $participant->event_id)
            ->contains(fn (RosterRow $person) => $person->participant->id === $participant->id), 404);

        if (blank($participant->email)) {
            return back()->with('ukc_error', 'Ce participant n’a pas d’adresse e-mail.');
        }

        $this->sendInvitation($participant);

        return back()->with('ukc_status', 'Invitation envoyée à '.$participant->full_name.'.');
    }

    /** Invite les participants ayant payé mais pas encore invités. */
    public function inviteBulk(Request $request, EventParticipantRoster $roster): RedirectResponse
    {
        $editionId = (string) $request->input('event_id', '');
        if ($editionId === '') {
            return back();
        }

        $participants = $roster->forEvent($editionId)
            ->filter(fn (RosterRow $p) => $p->paid && ! $p->invited && filled($p->email));

        foreach ($participants as $person) {
            $this->sendInvitation($person->participant);
        }

        return back()->with('ukc_status', $participants->count().' invitation(s) envoyée(s).');
    }

    /** Envoie l'e-mail d'invitation (si permis), marque invité et notifie le compte. */
    private function sendInvitation(Participant $participant): void
    {
        if (Notifier::emailAllowed($participant->user, 'invitation')) {
            Mail::to($participant->email)->send(new ParticipantInvitationMail($participant));
        }
        $participant->markInvited();

        Notifier::account(
            $participant->user,
            kind: 'invitation',
            title: 'Invitation au colloque',
            body: 'Vous êtes invité(e) à '.($participant->event?->title ?: 'l’événement').'.',
            url: route('account.dashboard'),
            icon: 'bell',
        );
    }
}
