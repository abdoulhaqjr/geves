<?php

namespace App\Support;

use App\Models\Participant;
use Illuminate\Support\Carbon;

/**
 * Une ligne du registre des participants d'un événement.
 *
 * ⚠️⚠️ **C'EST UN OBJET VALEUR, ET C'EST LE REMÈDE À L'ERREUR PHPSTAN DE
 * `EventParticipantRoster::forEvent()`.** La forme-tableau d'avant portait un
 * champ à type UNION (`participationMode: string|null`…) comme `TValue` d'une
 * `Collection`, qui n'est PAS covariante : PHPStan rendait alors « devrait
 * renvoyer X mais renvoie X », deux types identiques, un faux positif qu'aucun
 * typage du contenu ne levait. Une classe à une seule identité pour `TValue`
 * (`Collection<int, RosterRow>`) écarte le problème à la racine.
 *
 * Les propriétés publiques `readonly` gardent `Collection::where()` /
 * `whereIn()` opérants (ils lisent l'objet via `data_get`) ; le registre naît
 * d'une seule source (`forEvent()`), donc une ligne est immuable.
 */
class RosterRow
{
    public function __construct(
        public readonly Participant $participant,
        public readonly string $role,
        public readonly ?string $participationMode,
        public readonly ?string $participationModeLabel,
        public readonly bool $paid,
        public readonly bool $invited,
        public readonly ?Carbon $createdAt,
        public readonly string $name,
        public readonly string $email,
        public readonly ?int $userId,
    ) {}
}
