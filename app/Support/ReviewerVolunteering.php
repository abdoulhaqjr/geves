<?php

namespace App\Support;

use App\Actions\UpsertParticipantForAccount;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;

/** Conditions d'accès aux candidatures spontanées, communes à l'UI et au POST. */
class ReviewerVolunteering
{
    public static function enabled(?Event $event): bool
    {
        return (bool) data_get($event?->settings, 'reviewer_volunteering.enabled', false);
    }

    /** @return list<string> */
    public static function grades(?Event $event): array
    {
        return array_values((array) data_get($event?->settings, 'reviewer_volunteering.grades', []));
    }

    public static function participant(Event $event, User $account): ?Participant
    {
        return Participant::where('event_id', $event->id)->where('user_id', $account->id)->first();
    }

    public static function grade(Participant $participant, User $account): string
    {
        return trim((string) ($participant->title ?: $account->grade));
    }

    public static function eligible(?Event $event, User $account): bool
    {
        if (! $event || ! self::enabled($event)) {
            return false;
        }

        $participant = self::participant($event, $account);
        if (! $participant || in_array($participant->role, UpsertParticipantForAccount::STRONG_ROLES, true)) {
            return false;
        }

        // Les fonctions événementielles peuvent aussi avoir été rapprochées
        // par e-mail avant que leur fiche ne soit liée au compte utilisateur.
        if (Participant::where('event_id', $event->id)
            ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $account->email)])
            ->whereIn('role', UpsertParticipantForAccount::STRONG_ROLES)
            ->exists()) {
            return false;
        }

        return in_array(self::grade($participant, $account), self::grades($event), true);
    }
}
