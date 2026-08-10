<?php

namespace App\Actions;

use App\Models\Account;
use App\Models\Edition;
use App\Models\Participant;

/**
 * Crée ou retrouve le participant d'un compte sur une édition donnée.
 *
 * Règles (alignées sur le plugin WordPress) :
 * - **Identité imposée par le compte** : nom/prénom proviennent du compte
 *   (source de vérité), jamais falsifiables via un formulaire.
 * - **Rôle fort non rétrogradé** : un relecteur / membre de comité / modérateur /
 *   rapporteur ne redevient pas « auteur » lors d'une nouvelle soumission.
 * - `access_token` généré une seule fois (lien de suivi par jeton).
 */
class UpsertParticipantForAccount
{
    /** Rôles à ne jamais rétrograder. */
    public const STRONG_ROLES = ['reviewer', 'committee', 'moderator', 'rapporteur'];

    public function handle(Edition $edition, Account $account, string $role = 'author'): Participant
    {
        $participant = Participant::firstOrNew([
            'edition_id' => $edition->id,
            'account_id' => $account->id,
        ]);

        // access_token généré par ParticipantObserver à la création.
        if (! $participant->exists) {
            $participant->role = $role;
        }

        // Identité + coordonnées depuis le compte (source de vérité).
        $participant->first_name = $account->first_name;
        $participant->last_name = $account->last_name;
        $participant->email = $account->email;
        $participant->phone = $account->phone;
        $participant->institution = $account->affiliation;
        $participant->country = $account->country;
        $participant->title = $account->grade;

        // N'écrase pas un rôle fort par un rôle plus faible.
        if (! in_array($participant->role, self::STRONG_ROLES, true)) {
            $participant->role = $role;
        }

        $participant->save();

        return $participant;
    }
}
