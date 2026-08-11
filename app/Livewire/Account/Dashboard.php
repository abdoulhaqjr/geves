<?php

namespace App\Livewire\Account;

use App\Models\Attestation;
use App\Models\Edition;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use App\Support\AccountActivity;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tableau de bord du participant : parcours (stepper), prochaine étape, et blocs
 * personnels — communications, paiements & reçus, attestations.
 */
#[Title('Tableau de bord')]
class Dashboard extends Component
{
    public function render()
    {
        $account = Auth::guard('account')->user();

        $participantIds = Participant::where('account_id', $account->id)->pluck('id');

        // Rôles et activité dérivés (inter-éditions, par rapprochement d'e-mail).
        $activity = new AccountActivity($account);

        // Éditions auxquelles le compte participe → leurs documents (médias + livret).
        $editionIds = Participant::where('account_id', $account->id)
            ->pluck('edition_id')->unique()->filter();

        // Sessions en direct des éditions du compte qu'il peut rejoindre.
        $liveSessions = EventSession::query()->live()
            ->whereIn('edition_id', $editionIds)
            ->with('edition')
            ->get()
            ->filter(fn (EventSession $s) => $s->canJoin($account))
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
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
            ->with('edition')
            ->first();

        return view('livewire.account.dashboard', [
            'account' => $account,
            'openEdition' => Edition::where('status', 'open')
                ->where('submission_open', true)
                ->latest('id')
                ->first(),
            'payableEdition' => $payable?->edition,
            'submissions' => Submission::whereIn('participant_id', $participantIds)
                ->where('revision_of', 0)->with('edition')->latest('id')->limit(5)->get(),
            'payments' => Payment::whereIn('participant_id', $participantIds)
                ->where('status', 'paid')->with('edition')->latest('id')->get(),
            'attestations' => Attestation::whereIn('participant_id', $participantIds)
                ->where('revoked', false)->with('edition')->latest('id')->get(),
            'editionDocuments' => $editionDocuments,
            'liveSessions' => $liveSessions,
            'roles' => $activity->roles(),
            'moderatedSessions' => $activity->moderatedSessions(),
            'reportedSessions' => $activity->reportedSessions(),
            'committees' => $activity->committees(),
        ])->layout('components.layouts.participant', ['title' => 'Tableau de bord']);
    }
}
