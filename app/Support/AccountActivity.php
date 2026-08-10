<?php

namespace App\Support;

use App\Models\Account;
use App\Models\CommitteeMember;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Submission;
use Illuminate\Support\Collection;

/**
 * Rôles et activité d'un compte **dérivés** (jamais stockés) à travers toutes
 * les éditions, par rapprochement d'e-mail (insensible à la casse) :
 * communications (auteur/co-auteur), sessions modérées/rapportées, comités.
 * Le rôle affiché n'est donc pas figé par participation mais reflète l'ensemble
 * de l'activité de la personne.
 */
class AccountActivity
{
    public function __construct(public Account $account) {}

    protected function email(): string
    {
        return mb_strtolower(trim((string) $this->account->email));
    }

    /** Identifiants des participations rattachées au compte. */
    protected function participantIds(): Collection
    {
        return Participant::where('account_id', $this->account->id)->pluck('id');
    }

    /**
     * Communications où le compte est auteur correspondant ou co-auteur.
     *
     * @return Collection<int, array{submission:Submission, corresponding:bool}>
     */
    public function communications(): Collection
    {
        $ids = $this->participantIds();
        $email = $this->email();

        return Submission::where('revision_of', 0)
            ->where(function ($q) use ($ids, $email) {
                $q->whereIn('participant_id', $ids)
                    ->orWhere('authors', 'like', '%'.$email.'%');
            })
            ->with('edition')
            ->latest('id')
            ->get()
            ->filter(fn (Submission $s) => $ids->contains($s->participant_id) || $this->emailInAuthors($s, $email))
            ->unique('id')
            ->map(fn (Submission $s) => [
                'submission' => $s,
                'corresponding' => $ids->contains($s->participant_id)
                    || mb_strtolower(trim((string) ($s->authors[0]['email'] ?? ''))) === $email,
            ])
            ->values();
    }

    protected function emailInAuthors(Submission $submission, string $email): bool
    {
        foreach ($submission->authors ?? [] as $author) {
            if (mb_strtolower(trim((string) ($author['email'] ?? ''))) === $email) {
                return true;
            }
        }

        return false;
    }

    /** @return Collection<int, EventSession> */
    public function moderatedSessions(): Collection
    {
        return EventSession::whereRaw('LOWER(moderator_email) = ?', [$this->email()])
            ->with('edition')->latest('id')->get();
    }

    /** @return Collection<int, EventSession> */
    public function reportedSessions(): Collection
    {
        return EventSession::whereRaw('LOWER(rapporteur_email) = ?', [$this->email()])
            ->with('edition')->latest('id')->get();
    }

    /** @return Collection<int, CommitteeMember> */
    public function committees(): Collection
    {
        $email = $this->email();
        $fullName = mb_strtolower(trim($this->account->full_name));

        return CommitteeMember::where(function ($q) use ($email, $fullName) {
            $q->whereRaw('LOWER(email) = ?', [$email]);
            if ($fullName !== '') {
                $q->orWhere(function ($w) use ($fullName) {
                    $w->where(fn ($x) => $x->whereNull('email')->orWhere('email', ''))
                        ->whereRaw('LOWER(person_name) = ?', [$fullName]);
                });
            }
        })->with('edition')->latest('id')->get();
    }

    /** Le compte a-t-il au moins une participation en tant que relecteur ? */
    public function isReviewer(): bool
    {
        return Participant::where('account_id', $this->account->id)
            ->where('role', 'reviewer')->exists();
    }

    /** Le compte a-t-il au moins une participation, quelle qu'elle soit ? */
    public function hasAnyParticipation(): bool
    {
        return Participant::where('account_id', $this->account->id)->exists();
    }

    /**
     * Rôles dérivés (libellés), agrégés sur toutes les éditions.
     *
     * @return list<string>
     */
    public function roles(): array
    {
        $roles = [];

        if ($this->communications()->isNotEmpty()) {
            $roles[] = 'Communicant';
        }
        if ($this->isReviewer()) {
            $roles[] = 'Relecteur';
        }
        if ($this->moderatedSessions()->isNotEmpty()) {
            $roles[] = 'Modérateur';
        }
        if ($this->reportedSessions()->isNotEmpty()) {
            $roles[] = 'Rapporteur';
        }
        if ($this->committees()->isNotEmpty()) {
            $roles[] = 'Membre de comité';
        }

        if ($roles === [] && $this->hasAnyParticipation()) {
            $roles[] = 'Auditeur';
        }

        return $roles;
    }
}
