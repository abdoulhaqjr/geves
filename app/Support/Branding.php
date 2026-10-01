<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Logos de la plateforme, remplaçables depuis **Réglages › Général**.
 *
 * Deux FORMES, parce qu'un logo ne tient pas dans toutes les largeurs :
 *  - **large** (lockup complet, marque + nom de l'établissement) : pied de page,
 *    pages de connexion et de création de compte, bandeau des pages publiques
 *    d'événement - partout où l'on dispose de la largeur ;
 *  - **compacte** (monogramme carré) : en-tête public et menus latéraux, où le
 *    logo côtoie un texte ou se réduit à une colonne d'icônes.
 *
 * Deux FONDS pour chaque forme : un logo sombre disparaît sur le bleu de la
 * charte, d'où une variante claire pour les surfaces sombres.
 *
 * ⚠️ Repli en cascade, pour ne jamais afficher d'image cassée : variante
 * demandée → variante de même forme sur l'autre fond → fichier livré dans
 * `public/img`. Une plateforme fraîchement installée affiche donc le logo de
 * l'Université de Kara.
 *
 * ⚠️ Les clés stockées gardent leurs noms d'origine (`app_logo`,
 * `app_logo_white`) bien qu'elles désignent désormais la forme LARGE : les
 * renommer invaliderait les logos déjà téléversés.
 */
class Branding
{
    /** Logo large, fonds clairs. */
    public const WIDE = 'app_logo';

    /** Logo large, fonds sombres. */
    public const WIDE_WHITE = 'app_logo_white';

    /** Monogramme, fonds clairs. */
    public const MARK = 'app_logo_mark';

    /** Monogramme, fonds sombres. */
    public const MARK_WHITE = 'app_logo_mark_white';

    /** Fichiers livrés avec l'application. */
    private const SHIPPED_WIDE = 'img/logo-uk.png';

    private const SHIPPED_WIDE_WHITE = 'img/logo-uk-white.png';

    private const SHIPPED_MARK = 'img/apple-touch-icon.png';

    /** Toutes les variantes, pour l'écran de réglages. */
    public const KEYS = [self::WIDE, self::WIDE_WHITE, self::MARK, self::MARK_WHITE];

    /** Logo large sur fond clair. */
    public static function wide(): string
    {
        return self::first([self::WIDE, self::WIDE_WHITE], self::SHIPPED_WIDE);
    }

    /** Logo large sur fond sombre. */
    public static function wideWhite(): string
    {
        return self::first([self::WIDE_WHITE, self::WIDE], self::SHIPPED_WIDE_WHITE);
    }

    /** Monogramme sur fond clair. */
    public static function mark(): string
    {
        return self::first([self::MARK, self::MARK_WHITE], self::SHIPPED_MARK);
    }

    /** Monogramme sur fond sombre. */
    public static function markWhite(): string
    {
        return self::first([self::MARK_WHITE, self::MARK], self::SHIPPED_MARK);
    }

    /** Un logo a-t-il été téléversé, quelle que soit la variante ? */
    public static function isCustom(): bool
    {
        foreach (self::KEYS as $key) {
            if (self::stored($key) !== null) {
                return true;
            }
        }

        return false;
    }

    /** URL d'une variante précise, ou null si elle n'a pas été téléversée. */
    public static function url(string $key): ?string
    {
        $path = self::stored($key);

        return $path === null ? null : Storage::disk('public')->url($path);
    }

    /**
     * Première variante disponible parmi `$keys`, sinon le fichier livré.
     *
     * @param  list<string>  $keys
     */
    private static function first(array $keys, string $shipped): string
    {
        foreach ($keys as $key) {
            $path = self::stored($key);
            if ($path !== null) {
                return Storage::disk('public')->url($path);
            }
        }

        return asset($shipped);
    }

    /**
     * Chemin stocké d'une variante, seulement si le fichier existe encore :
     * un réglage pointant vers un fichier supprimé ne doit pas casser l'affichage.
     */
    private static function stored(string $key): ?string
    {
        $path = Settings::get($key);

        return filled($path) && Storage::disk('public')->exists((string) $path)
            ? (string) $path
            : null;
    }
}
