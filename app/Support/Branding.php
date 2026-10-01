<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Logo de la plateforme, remplaçable depuis **Réglages › Général**.
 *
 * Deux variantes, parce que le logo se pose sur deux fonds :
 *  - **standard** : fonds clairs (en-tête public, menus du back-office) ;
 *  - **blanc** : fonds sombres (page de connexion, pied public, bandeau d'un
 *    colloque). Sans elle, un logo sombre disparaît sur le bleu de la charte.
 *
 * ⚠️ Repli systématique sur les fichiers livrés dans `public/img` : une
 * plateforme fraîchement installée, ou dont le téléversement a échoué, affiche
 * le logo de l'Université de Kara plutôt qu'une image cassée.
 */
class Branding
{
    /** Réglage du logo standard (chemin sur le disque public). */
    public const KEY = 'app_logo';

    /** Réglage de la variante pour fonds sombres. */
    public const KEY_WHITE = 'app_logo_white';

    /** Fichiers livrés avec l'application, utilisés à défaut de téléversement. */
    private const FALLBACK = 'img/logo-uk.png';

    private const FALLBACK_WHITE = 'img/logo-uk-white.png';

    /** URL du logo standard (fonds clairs). */
    public static function logo(): string
    {
        return self::resolve(self::KEY, self::FALLBACK);
    }

    /**
     * URL du logo pour fond sombre. À défaut de variante blanche téléversée, on
     * retombe sur le logo standard s'il a été personnalisé (mieux vaut la marque
     * de l'établissement, même peu contrastée, qu'un logo étranger).
     */
    public static function logoWhite(): string
    {
        $white = Settings::get(self::KEY_WHITE);
        if (filled($white) && Storage::disk('public')->exists((string) $white)) {
            return Storage::disk('public')->url((string) $white);
        }

        $standard = Settings::get(self::KEY);
        if (filled($standard) && Storage::disk('public')->exists((string) $standard)) {
            return Storage::disk('public')->url((string) $standard);
        }

        return asset(self::FALLBACK_WHITE);
    }

    /** Un logo a-t-il été téléversé (par opposition au logo livré) ? */
    public static function isCustom(): bool
    {
        foreach ([self::KEY, self::KEY_WHITE] as $key) {
            $path = Settings::get($key);
            if (filled($path) && Storage::disk('public')->exists((string) $path)) {
                return true;
            }
        }

        return false;
    }

    private static function resolve(string $key, string $fallback): string
    {
        $path = Settings::get($key);

        return filled($path) && Storage::disk('public')->exists((string) $path)
            ? Storage::disk('public')->url((string) $path)
            : asset($fallback);
    }
}
