<?php

namespace App\Support;

use App\Models\Event;

/**
 * Gel d'une événement archivée : côté PARTICIPANT, tout devient lecture seule.
 *
 * ⚠️ Le déclencheur est le statut **`archived`**, pas la date de fin ni le statut
 * `closed`. `closed` signifie « soumissions closes » - c'est précisément la
 * période où les relecteurs travaillent, les geler alors bloquerait le colloque.
 * De même, une événement dont les dates sont passées mais que l'organisation n'a
 * pas archivée peut encore recevoir des relectures ou une version définitive.
 * L'archivage est donc la décision explicite qui fige le dossier.
 *
 * Le back-office n'est PAS concerné : l'admin et les gestionnaires gardent la
 * main (corriger une note, émettre une attestation oubliée, rembourser…). Leurs
 * droits restent portés par le RBAC par événement.
 */
class EventLock
{
    public const MESSAGE = 'Cette événement est archivée : elle est en lecture seule.';

    /** L'événement est-elle figée pour les participants ? */
    public static function frozen(?Event $event): bool
    {
        return $event !== null && $event->status === 'archived';
    }

    /**
     * Refuse une action d'écriture sur une événement archivée (403 explicite).
     * À appeler dans les actions participant qui écrivent quelque chose de
     * rattaché à une événement : dépôt, relecture, rapport, paiement, formation.
     */
    public static function guard(?Event $event): void
    {
        abort_if(self::frozen($event), 403, self::MESSAGE);
    }
}
