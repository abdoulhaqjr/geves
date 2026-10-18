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
    public function handle(Event $event, string $name, string $email, ?string $institution = null, array $tracks = []): Participant
    {
        $email = mb_strtolower(trim($email));
        $matches = Participant::where('event_id', $event->id)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->get();
        $participant = $matches->firstWhere('role', 'reviewer') ?? $matches->first();

        if ($participant?->role === 'reviewer') {
            throw ValidationException::withMessages(['reviewerEmail' => 'Cet évaluateur est déjà enregistré pour cet événement.']);
        }

        if ($participant && in_array($participant->role, UpsertParticipantForAccount::STRONG_ROLES, true)) {
            throw ValidationException::withMessages(['reviewerEmail' => 'Cette personne possède déjà un autre rôle de gestion sur cet événement.']);
        }

        $account = User::whereRaw('LOWER(email) = ?', [$email])->first();
        $participant ??= new Participant(['event_id' => $event->id, 'email' => $email]);

        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $last = count($parts) > 1 ? (string) array_pop($parts) : '';
        $participant->first_name = implode(' ', $parts);
        $participant->last_name = $last;
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
