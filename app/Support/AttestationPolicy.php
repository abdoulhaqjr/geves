<?php

namespace App\Support;

use App\Models\Event;

/**
 * **Comment les attestations sont délivrées** sur un événement : en ligne (envoi
 * et téléchargement) ou sur papier (le gestionnaire imprime et remet).
 *
 * ⚠️ **Un seul endroit décide.** Trois écrans lisent ce choix - l'émission en
 * masse, la validation du passage par le modérateur, et l'envoi au participant -
 * et chacun en tirerait sa propre interprétation si le réglage était relu brut.
 *
 * ⚠️ Le mode papier **n'empêche pas** l'attestation d'exister ni le participant
 * de la retrouver dans son espace : il coupe l'**envoi automatique**, parce que
 * l'exemplaire qui fait foi est celui qu'on lui remet en main propre. Lui
 * fermer aussi l'accès en ligne ne ferait que renvoyer ses questions au
 * secrétariat.
 */
class AttestationPolicy
{
    public const DELIVERY_ONLINE = 'online';

    public const DELIVERY_PAPER = 'paper';

    /** Modes de délivrance et leurs libellés. */
    public const DELIVERIES = [
        self::DELIVERY_ONLINE => 'En ligne (envoi et téléchargement)',
        self::DELIVERY_PAPER => 'Physique (imprimées et remises)',
    ];

    /** Qui reçoit une attestation de communication. */
    public const SCOPE_ALL = 'all';

    public const SCOPE_PRESENT = 'present';

    public const SCOPES = [
        self::SCOPE_ALL => 'Tous les auteurs concernés par la communication',
        self::SCOPE_PRESENT => 'Seulement les auteurs pointés présents à la séance',
    ];

    public static function delivery(?Event $event): string
    {
        $mode = (string) EventSettings::get($event, 'attestations.delivery', self::DELIVERY_ONLINE);

        return isset(self::DELIVERIES[$mode]) ? $mode : self::DELIVERY_ONLINE;
    }

    public static function isPaper(?Event $event): bool
    {
        return self::delivery($event) === self::DELIVERY_PAPER;
    }

    /**
     * Le passage d'une communication émet-il les attestations de ses auteurs ?
     *
     * ⚠️ Non en mode papier : là, c'est le gestionnaire qui émet le lot, le
     * relit et l'imprime. Une émission au fil des passages lui ferait réimprimer
     * à chaque communication.
     */
    public static function issuesOnPassage(?Event $event): bool
    {
        return ! self::isPaper($event);
    }

    public static function communicationScope(?Event $event): string
    {
        $scope = (string) EventSettings::get($event, 'attestations.communication_scope', self::SCOPE_ALL);

        return isset(self::SCOPES[$scope]) ? $scope : self::SCOPE_ALL;
    }

    /** L'attestation de communication est-elle réservée aux présents de la séance ? */
    public static function requiresPresence(?Event $event): bool
    {
        return self::communicationScope($event) === self::SCOPE_PRESENT;
    }

    /**
     * Prévient-on le bénéficiaire à l'émission ?
     *
     * ⚠️ Jamais en mode papier - annoncer un document qu'on ne peut pas encore
     * retirer ne fait qu'amener la personne au secrétariat.
     */
    public static function notifies(?Event $event): bool
    {
        return ! self::isPaper($event)
            && (bool) EventSettings::get($event, 'attestations.notify', true);
    }

    /**
     * SMS à l'émission ?
     *
     * ⚠️ **Fermé par défaut, et volontairement.** Chaque SMS est facturé, et une
     * événement de deux cents communications en enverrait autant sans que personne
     * ne l'ait décidé.
     */
    public static function sendsSms(?Event $event): bool
    {
        return self::notifies($event) && (bool) EventSettings::get($event, 'attestations.sms', false);
    }
}
