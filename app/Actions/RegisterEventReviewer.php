<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** Enregistre un évaluateur sur un événement avant toute attribution. */
class RegisterEventReviewer
{
    /** @param list<string> $tracks */
    public function handle(Event $event, string $firstName, string $lastName, string $email, ?string $institution = null, array $tracks = [], ?User $selectedAccount = null): Participant
    {
        $email = mb_strtolower(trim($email));
        $account = $selectedAccount ?? User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($account) {
            $firstName = (string) ($account->first_name ?: $firstName);
            $lastName = (string) ($account->last_name ?: $lastName);
            $institution = $institution ?: (string) ($account->affiliation ?? '');
        }

        $matches = Participant::where('event_id', $event->id)
            ->where(function ($query) use ($email, $account) {
                $query->whereRaw('LOWER(email) = ?', [$email]);
                if ($account) {
                    $query->orWhere('user_id', $account->id);
                }
            })
            ->get();
        if ($matches->contains('role', 'reviewer')) {
            throw ValidationException::withMessages(['reviewerEmail' => 'Cet évaluateur est déjà enregistré pour cet événement.']);
        }

        // Une personne peut aussi modérer une séance ou appartenir au comité.
        // Ces rôles restent sur leur fiche ; l'évaluation reçoit sa propre fiche.
        $participant = $matches->firstWhere('role', 'former_reviewer')
            ?? $matches->first(fn (Participant $match) => ! in_array($match->role, UpsertParticipantForAccount::STRONG_ROLES, true))
            ?? new Participant(['event_id' => $event->id, 'email' => $email]);

        $meta = $participant->meta ?? [];
        if ($participant->exists && $participant->role !== 'reviewer' && $participant->role !== 'former_reviewer') {
            $meta['reviewer_previous_role'] = $participant->role;
        }

        $participant->first_name = trim($firstName);
        $participant->last_name = trim($lastName);
        $participant->role = 'reviewer';
        $participant->email = $email;
        $participant->institution = $institution ?: $participant->institution;
        if ($account && ! $participant->user_id) {
            $participant->user_id = $account->id;
        }

        $meta['review_tracks'] = $tracks;
        $participant->meta = $meta;
        $participant->save();

        return $participant;
    }
}
