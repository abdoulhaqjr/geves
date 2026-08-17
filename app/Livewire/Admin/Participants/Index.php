<?php

namespace App\Livewire\Admin\Participants;

use App\Mail\ParticipantInvitationMail;
use App\Models\Edition;
use App\Models\Participant;
use App\Support\Notifier;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Participants d'une édition : rôle, paiement, invitation. Filtres et envoi
 * d'invitations (individuel ou groupé aux payés non invités).
 */
#[Title('Participants')]
class Index extends Component
{
    use WithPagination;

    public string $edition_id = '';

    public string $role = '';

    public string $search = '';

    public function mount(): void
    {
        $this->edition_id = (string) (Edition::where('status', 'open')->latest('id')->value('id')
            ?? Edition::latest('id')->value('id') ?? '');
    }

    public function updating($name): void
    {
        if (in_array($name, ['edition_id', 'role', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function invite(int $id): void
    {
        $participant = Participant::find($id);
        if (! $participant || blank($participant->email)) {
            return;
        }

        if (Notifier::emailAllowed($participant->account, 'invitation')) {
            Mail::to($participant->email)->send(new ParticipantInvitationMail($participant));
        }
        $participant->markInvited();
        $this->notifyInvitation($participant);

        session()->flash('ukc_status', 'Invitation envoyée à '.$participant->full_name.'.');
    }

    /** Notifie le compte lié au participant (le cas échéant) de son invitation. */
    private function notifyInvitation(Participant $participant): void
    {
        Notifier::account(
            $participant->account,
            kind: 'invitation',
            title: 'Invitation au colloque',
            body: 'Vous êtes invité(e) à '.($participant->edition?->title ?: 'l’édition').'.',
            url: route('account.dashboard'),
            icon: 'bell',
        );
    }

    /** Invite les participants ayant payé mais pas encore invités. */
    public function invitePaidUninvited(): void
    {
        if ($this->edition_id === '') {
            return;
        }

        $participants = Participant::where('edition_id', $this->edition_id)
            ->whereHas('payments', fn ($q) => $q->where('status', 'paid'))
            ->get()
            ->filter(fn ($p) => ! $p->isInvited() && filled($p->email));

        foreach ($participants as $participant) {
            if (Notifier::emailAllowed($participant->account, 'invitation')) {
                Mail::to($participant->email)->send(new ParticipantInvitationMail($participant));
            }
            $participant->markInvited();
            $this->notifyInvitation($participant);
        }

        session()->flash('ukc_status', $participants->count().' invitation(s) envoyée(s).');
    }

    public function render()
    {
        $base = Participant::query()->where('edition_id', $this->edition_id);

        $participants = (clone $base)
            ->with('payments')
            ->when($this->role !== '', fn ($q) => $q->where('role', $this->role))
            ->when($this->search !== '', function ($q) {
                $term = "%{$this->search}%";
                $q->where(fn ($p) => $p->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->orderBy('last_name')
            ->paginate(20);

        $paidCount = (clone $base)->whereHas('payments', fn ($q) => $q->where('status', 'paid'))->count();

        return view('livewire.admin.participants.index', [
            'editions' => Edition::latest('id')->get(),
            'participants' => $participants,
            'roles' => Participant::ROLES,
            'totalCount' => (clone $base)->count(),
            'paidCount' => $paidCount,
            'stats' => [
                'total' => (clone $base)->count(),
                'paid' => $paidCount,
                'checkedIn' => (clone $base)->whereNotNull('checked_in_at')->count(),
                'authors' => (clone $base)->where('role', 'author')->count(),
            ],
        ])->layout('components.layouts.admin', ['title' => 'Participants']);
    }
}
