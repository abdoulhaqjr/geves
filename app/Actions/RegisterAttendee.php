<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Support\Fees;

/**
 * **Inscrire un compte à un événement, sans communication.**
 *
 * ⚠️⚠️ **IL N'Y AVAIT AUCUNE PORTE.** On ne devenait participant qu'en déposant
 * un résumé, en étant rapproché comme co-auteur, en s'inscrivant à une
 * formation, ou en étant saisi au guichet par le secrétariat. Celui qui vient
 * seulement écouter n'avait aucun chemin - alors que le rôle « Participant
 * simple » existait déjà ({@see Participant::ROLES}) et que la page publique
 * promettait noir sur blanc de pouvoir « s'inscrire comme participant simple ».
 *
 * ⚠️ **On ne crée pas la fiche soi-même** : {@see UpsertParticipantForAccount}
 * reste le seul endroit qui le fasse. Il impose l'identité du compte et
 * **ne rétrograde pas un rôle fort** - un évaluateur qui s'inscrit reste
 * évaluateur, et prend juste sa date d'inscription.
 *
 * ⚠️ **La date est posée une seule fois.** Se réinscrire ne doit pas repousser
 * la date : c'est elle qui dit depuis quand la personne est attendue, et elle
 * sert de preuve que des frais sont dus ({@see Participant::owesParticipationFees()}).
 */
class RegisterAttendee
{
    /**
     * @param  string|null  $grade  grade POUR CET ÉVÉNEMENT - c'est lui qui
     *                              détermine le tarif ({@see Fees::resolveAmount()}).
     */
    public function handle(
        Event $event,
        User $account,
        ?string $grade = null,
        ?string $affiliation = null,
        ?string $participationMode = null,
    ): Participant {
        $participant = app(UpsertParticipantForAccount::class)
            ->handle($event, $account, 'attendee', $grade, $affiliation);

        $meta = $participant->meta ?? [];
        $meta['registered_at'] ??= now()->toIso8601String();

        $participant->meta = $meta;

        if ($participationMode !== null && $participationMode !== '') {
            $participant->participation_mode = $participationMode;
        }

        $participant->save();

        return $participant;
    }
}
