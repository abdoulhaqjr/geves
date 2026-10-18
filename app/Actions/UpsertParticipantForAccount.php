<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;

/**
 * Crée ou retrouve le participant d'un compte sur un événement donné.
 *
 * Règles (alignées sur le plugin WordPress) :
 * - **Identité imposée par le compte** : nom/prénom proviennent du compte
 *   (source de vérité), jamais falsifiables via un formulaire.
 * - **Rôle fort non rétrogradé** : un évaluateur / membre de comité / modérateur /
 *   rapporteur ne redevient pas « auteur » lors d'une nouvelle soumission.
 * - `access_token` généré une seule fois (lien de suivi par jeton).
 */
class UpsertParticipantForAccount
{
    /** Rôles à ne jamais rétrograder. */
    public const STRONG_ROLES = ['reviewer', 'committee', 'moderator', 'rapporteur'];

    /**
     * @param  string|null  $grade  grade POUR CETTE ÉDITION (repli : grade du compte).
     *                              Permet à une même personne d'avoir des grades
     *                              différents au fil des colloques.
     * @param  string|null  $affiliation  affiliation pour cet événement (repli : compte).
     */
    public function handle(Event $event, User $account, string $role = 'author', ?string $grade = null, ?string $affiliation = null): Participant
    {
        $participant = Participant::firstOrNew([
            'event_id' => $event->id,
            'user_id' => $account->id,
        ]);

        // access_token généré par ParticipantObserver à la création.
        if (! $participant->exists) {
            $participant->role = $role;
        }

        // Identité + coordonnées depuis le compte (source de vérité). Le grade et
        // l'affiliation sont propres à l'événement (repli sur le profil du compte).
        $participant->first_name = $account->first_name;
        $participant->last_name = $account->last_name;
        $participant->email = $account->email;
        $participant->phone = $account->phone;
        $participant->institution = ($affiliation !== null && $affiliation !== '') ? $affiliation : $account->affiliation;
        $participant->country = $account->country;
        $participant->title = ($grade !== null && $grade !== '') ? $grade : $account->grade;

        // N'écrase pas un rôle fort par un rôle plus faible.
        if (! in_array($participant->role, self::STRONG_ROLES, true)) {
            $participant->role = $role;
        }

        $participant->save();

        return $participant;
    }
}
