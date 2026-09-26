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
 * Participants d'une édition (Blade, fidèle au mockup) : rôle, paiement, invitation.
 * Filtres par query string ; invitations (individuelle ou groupée aux payés non
 * invités) en POST. L'aperçu « Sa page » est une vue en lecture seule.
 */
class ParticipantController extends Controller
{
    use Concerns\ScopesEditionAccess;

    public function index(Request $request): View
    {
        $editions = $this->accessibleEditions($request, 'participants');
        $editionId = (string) $request->query('edition_id', '');
        if ($editionId === '') {
            $editionId = (string) ($editions->firstWhere('status', 'open')?->id ?? $editions->first()?->id ?? '');
        }
        $role = (string) $request->query('role', '');
        $search = trim((string) $request->query('search', ''));

        $base = Participant::query()->where('edition_id', $editionId);
        $sort = ListSort::resolve($request, ['last_name', 'role', 'created_at'], 'last_name', 'asc');

        $participants = (clone $base)
            ->with('payments')
            ->when($role !== '', fn ($q) => $q->where('role', $role))
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

        // Rôles d'organisation cumulés par personne sur CE colloque (edition_user),
        // pour montrer qu'un participant peut aussi porter une fonction d'équipe.
        $editionRoles = $editionId === ''
            ? collect()
            : DB::table('edition_user')
                ->where('edition_id', $editionId)->get()
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
            'editions' => $editions,
            'participants' => $participants,
            'roles' => Participant::ROLES,
            'edition_id' => $editionId,
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
        $participant->load('edition', 'user');
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
        $editionId = (string) $request->input('edition_id', '');
        if ($editionId === '') {
            return back();
        }

        $participants = Participant::where('edition_id', $editionId)
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
            body: 'Vous êtes invité(e) à '.($participant->edition?->title ?: 'l’édition').'.',
            url: route('account.dashboard'),
            icon: 'bell',
        );
    }
}
