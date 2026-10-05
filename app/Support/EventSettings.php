<?php

namespace App\Support;

use App\Models\Event;

/**
 * Réglages de documents SCOPÉS PAR ÉDITION (templates & options d'attestations,
 * de badges et du livre des résumés). Chaque événement porte ses propres valeurs
 * dans `edition.settings['doc']`, conformément à la règle « à chaque colloque
 * ses templates de documents ».
 *
 * À défaut de valeur propre à l'événement, on retombe sur le réglage GLOBAL
 * (App\Support\Settings) qui joue le rôle de valeur d'usine / défaut de la
 * plateforme : migration douce et non destructive (les événements non encore
 * personnalisées conservent l'apparence par défaut jusqu'à leur premier
 * enregistrement).
 *
 * Les layouts visuels (badge/attestation) restent gérés par le modèle
 * DocumentTemplate, déjà rattaché à l'événement.
 */
class EventSettings
{
    /** Sous-clé de `edition.settings` où vivent les réglages documents. */
    private const BAG = 'doc';

    public static function get(?Event $event, string $key, mixed $default = null): mixed
    {
        if ($event !== null) {
            $scoped = $event->settings[self::BAG][$key] ?? null;
            if ($scoped !== null && $scoped !== '') {
                return $scoped;
            }
        }

        // Repli sur le réglage global (valeur d'usine / défaut de plateforme).
        return Settings::get($key, $default);
    }

    public static function set(Event $event, string $key, mixed $value): void
    {
        self::setMany($event, [$key => $value]);
    }

    /** @param array<string, mixed> $values */
    public static function setMany(Event $event, array $values): void
    {
        $settings = $event->settings ?? [];
        $bag = $settings[self::BAG] ?? [];

        foreach ($values as $key => $value) {
            $bag[$key] = $value;
        }

        $settings[self::BAG] = $bag;
        $event->settings = $settings;
        $event->save();
    }
}
