<?php

namespace App\Enums\Concerns;

/**
 * Utilitaires communs aux enums de statut : listes valeur→libellé et
 * valeur→pastille, pour alimenter filtres et affichages.
 */
trait HasLabels
{
    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /** @return array<string, string> valeur => libellé */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    /** @return array<string, string> valeur => variante de pastille (.ukc-pill--*) */
    public static function pills(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->pill();
        }

        return $out;
    }
}
