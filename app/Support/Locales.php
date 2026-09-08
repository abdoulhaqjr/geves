<?php

namespace App\Support;

/**
 * Utilitaires i18n : liste des locales prises en charge et validation d'un code.
 */
class Locales
{
    /**
     * Locales prises en charge : code => libellé natif.
     *
     * @return array<string, string>
     */
    public static function available(): array
    {
        $list = config('app.available_locales', ['fr' => 'Français']);

        return is_array($list) ? $list : ['fr' => 'Français'];
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::available());
    }

    public static function supported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::codes(), true);
    }

    /** Locale par défaut de l'application (repli). */
    public static function default(): string
    {
        return (string) config('app.fallback_locale', 'fr');
    }

    /** Libellé natif d'une locale (ou le code si inconnu). */
    public static function label(string $locale): string
    {
        return self::available()[$locale] ?? $locale;
    }
}
