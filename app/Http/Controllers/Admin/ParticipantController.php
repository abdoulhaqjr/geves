<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ParticipantInvitationMail;
use App\Models\Attestation;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use App\Support\AccountActivity;
use App\Support\ListSort;
use App\Support\Notifier;
use App\Support\Trend;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Participants d'une événement (Blade, fidèle au mockup) : rôle, paiement, invitation.
 * Filtres par query string ; invitations (individuelle ou groupée aux payés non
 * invités) en POST. L'aperçu « Sa page » est une vue en lecture seule.
 */
class ParticipantController extends Controller
{
    use Concerns\ScopesEditionAccess;

    public function index(Request $request): View
    {
        $events = $this->accessibleEditions($request, 'participants');
        $editionId = (string) $request->query('event_id', '');
        if ($editionId === '') {
            $editionId = (string) ($events->firstWhere('status', 'open')?->id ?? $events->first()?->id ?? '');
        }
        $role = (string) $request->query('role', '');
        $search = trim((string) $request->query('search', ''));
        $paid = (string) $request->query('paid', '');
        $invited = (string) $request->query('invited', '');

        $base = Participant::query()->where('event_id', $editionId);
        $sort = ListSort::resolve($request, ['last_name', 'role', 'created_at'], 'last_name', 'asc');

        $participants = (clone $base)
            ->with('participationPayments')
            ->when($role !== '', fn ($q) => $q->where('role', $role))
            // ⚠️ Paiement = frais de PARTICIPATION (une formation réglée ne compte pas).
            ->when($paid === '1', fn ($q) => $q->whereHas('participationPayments', fn ($pa) => $pa->where('status', 'paid')))
            ->when($paid === '0', fn ($q) => $q->whereDoesntHave('participationPayments', fn ($pa) => $pa->where('status', 'paid')))
            // « Invité » est un horodatage dans meta (Participant::isInvited).
            ->when($invited === '1', fn ($q) => $q->where('meta', 'like', '%"invited_at"%'))
            ->when($invited === '0', fn ($q) => $q->where(fn ($w) => $w->whereNull('meta')->orWhere('meta', 'not like', '%"invited_at"%')))
            ->when($search !== '', function ($q) use ($search) {
                $term = "%{$search}%";
                $q->where(fn ($p) => $p->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->orderBy($sort['sort'], $sort['dir'])
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $paidCount = (clone $base)->whereHas('participationPayments', fn ($q) => $q->where('status', 'paid'))->count();

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
            (int) (clone $base)->whereBetween('created_at', [now()->subDays(7), now()])->count(),
            (int) (clone $base)->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count(),
        );

        return view('admin.participants.index', [
            'trendTotal' => $trendTotal,
            'editionRoles' => $editionRoles,
            'sort' => $sort['sort'],
            'dir' => $sort['dir'],
            'events' => $events,
            'paid' => $paid,
            'invited' => $invited,
            'participants' => $participants,
            'roles' => Participant::ROLES,
            'event_id' => $editionId,
            'role' => $role,
            'search' => $search,
            'stats' => [
                'total' => (clone $base)->count(),
                'paid' => $paidCount,
                'checkedIn' => (clone $base)->whereNotNull('checked_in_at')->count(),
                'authors' => (clone $base)->where('role', 'author')->count(),
            ],
        ]);
    }

    /** « Sa page » - aperçu en lecture seule de l'espace d'un participant. */
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

    public function invite(Participant $participant): RedirectResponse
    {
        if (blank($participant->email)) {
            return back()->with('ukc_error', 'Ce participant n’a pas d’adresse e-mail.');
        }

        $this->sendInvitation($participant);

        return back()->with('ukc_status', 'Invitation envoyée à '.$participant->full_name.'.');
    }

    /** Invite les participants ayant payé mais pas encore invités. */
    public function inviteBulk(Request $request): RedirectResponse
    {
        $editionId = (string) $request->input('event_id', '');
        if ($editionId === '') {
            return back();
        }

        $participants = Participant::where('event_id', $editionId)
            ->whereHas('participationPayments', fn ($q) => $q->where('status', 'paid'))
            ->get()
            ->filter(fn ($p) => ! $p->isInvited() && filled($p->email));

        foreach ($participants as $participant) {
            $this->sendInvitation($participant);
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
