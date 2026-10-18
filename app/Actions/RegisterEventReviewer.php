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
            ->whereRaw('LOWER(email) = ?', [$email])
            ->get();
        $participant = $matches->firstWhere('role', 'reviewer')
            ?? $matches->first()
            ?? ($account ? Participant::where('event_id', $event->id)->where('user_id', $account->id)->first() : null);

        if ($participant?->role === 'reviewer') {
            throw ValidationException::withMessages(['reviewerEmail' => 'Cet évaluateur est déjà enregistré pour cet événement.']);
        }

        if ($participant && in_array($participant->role, UpsertParticipantForAccount::STRONG_ROLES, true)) {
            throw ValidationException::withMessages(['reviewerEmail' => 'Cette personne possède déjà un autre rôle de gestion sur cet événement.']);
        }

        $participant ??= new Participant(['event_id' => $event->id, 'email' => $email]);

        $participant->first_name = trim($firstName);
        $participant->last_name = trim($lastName);
        $participant->role = 'reviewer';
        $participant->email = $email;
        $participant->institution = $institution ?: $participant->institution;
        if ($account && ! $participant->user_id) {
            $participant->user_id = $account->id;
        }

        $meta = $participant->meta ?? [];
        $meta['review_tracks'] = $tracks;
        $participant->meta = $meta;
        $participant->save();

        return $participant;
    }
}
