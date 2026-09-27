<?php

namespace App\Support;

use App\Models\Edition;

/**
 * Gel d'une édition archivée : côté PARTICIPANT, tout devient lecture seule.
 *
 * ⚠️ Le déclencheur est le statut **`archived`**, pas la date de fin ni le statut
 * `closed`. `closed` signifie « soumissions closes » - c'est précisément la
 * période où les relecteurs travaillent, les geler alors bloquerait le colloque.
 * De même, une édition dont les dates sont passées mais que l'organisation n'a
 * pas archivée peut encore recevoir des relectures ou une version définitive.
 * L'archivage est donc la décision explicite qui fige le dossier.
 *
 * Le back-office n'est PAS concerné : l'admin et les gestionnaires gardent la
 * main (corriger une note, émettre une attestation oubliée, rembourser…). Leurs
 * droits restent portés par le RBAC par édition.
 */
class EditionLock
{
    public const MESSAGE = 'Cette édition est archivée : elle est en lecture seule.';

    /** L'édition est-elle figée pour les participants ? */
    public static function frozen(?Edition $edition): bool
    {
        return $edition !== null && $edition->status === 'archived';
    }

    /**
     * Refuse une action d'écriture sur une édition archivée (403 explicite).
     * À appeler dans les actions participant qui écrivent quelque chose de
     * rattaché à une édition : dépôt, relecture, rapport, paiement, formation.
     */
    public static function guard(?Edition $edition): void
    {
        abort_if(self::frozen($edition), 403, self::MESSAGE);
    }
}
