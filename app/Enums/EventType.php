<?php

namespace App\Enums;

use App\Models\EventSeries;

/**
 * Type d'une série d'événements scientifiques. Porté par la série ({@see EventSeries}),
 * il est hérité par ses occurrences (événements). La plupart des fonctionnalités sont communes
 * à tous les types ; les différences de comportement se branchent au cas par cas (phase 2).
 */
enum EventType: string
{
    case Colloque = 'colloque';
    case Seminaire = 'seminaire';
    case JourneesScientifiques = 'journees_scientifiques';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Colloque => 'Colloque',
            self::Seminaire => 'Séminaire',
            self::JourneesScientifiques => 'Journées scientifiques',
            self::Autre => 'Autre',
        };
    }

    /**
     * Libellés indexés par valeur, pour un <select> ou l'affichage.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
