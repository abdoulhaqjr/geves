<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Logos de la plateforme, remplaçables depuis **Réglages › Général**.
 *
 * **Deux logos à téléverser**, parce qu'un logo ne tient pas dans toutes les
 * largeurs :
 *  - **large** (lockup complet, marque + nom de l'établissement) : pied de page,
 *    pages de connexion et de création de compte, bandeau des pages publiques
 *    d'événement - partout où l'on dispose de la largeur ;
 *  - **compact** (monogramme carré) : en-tête public et menus latéraux, où le
 *    logo côtoie un texte ou se réduit à une colonne d'icônes.
 *
 * ⚠️ **La version pour fonds sombres n'est pas téléversée, elle est calculée.**
 * Demander deux fichiers par forme revenait à demander à l'admin de fabriquer
 * un logo blanc - en pratique il redéposait le même fichier sombre, illisible
 * sur le bleu de la charte. Les surfaces sombres servent donc le MÊME fichier
 * avec la classe CSS **`.logo-auto-white`** (`brightness(0) invert(1)` : toute
 * la matière opaque passe en blanc, la découpe et la transparence sont
 * conservées). `whitensOnDark()` dit quand la poser.
 *
 * ⚠️ **Repli** sur les fichiers livrés dans `public/img` si rien n'est téléversé
 * ou si le fichier réglé a disparu - jamais d'image cassée. Le repli du logo
 * large sur fond sombre est `logo-uk-white.png`, **déjà blanc** : on ne le
 * blanchit pas (et blanchir le monogramme livré en ferait un carré plein).
 *
 * ⚠️ Les clés stockées gardent leurs noms d'origine (`app_logo`,
 * `app_logo_mark`) : les renommer invaliderait les logos déjà téléversés. Les
 * anciennes clés `app_logo_white` / `app_logo_mark_white` ne sont plus lues
 * (migration `2026_10_01_000100_drop_dark_logo_settings`).
 */
class Branding
{
    /** Logo large (lockup complet). */
    public const WIDE = 'app_logo';

    /** Logo compact (monogramme carré). */
    public const MARK = 'app_logo_mark';

    /** Fichiers livrés avec l'application. */
    private const SHIPPED_WIDE = 'img/logo-uk.png';

    private const SHIPPED_WIDE_WHITE = 'img/logo-uk-white.png';

    private const SHIPPED_MARK = 'img/apple-touch-icon.png';

    /** Les deux logos, pour l'écran de réglages. */
    public const KEYS = [self::WIDE, self::MARK];

    /** Logo large sur fond clair. */
    public static function wide(): string
    {
        return self::stored(self::WIDE) !== null
            ? self::url(self::WIDE) ?? asset(self::SHIPPED_WIDE)
            : asset(self::SHIPPED_WIDE);
    }

    /**
     * Logo large sur fond sombre.
     *
     * ⚠️ À accompagner de `.logo-auto-white` quand `whitensOnDark(WIDE)` : sans
     * elle, un logo téléversé à texte sombre disparaît sur le bleu.
     */
    public static function wideWhite(): string
    {
        return self::stored(self::WIDE) !== null
            ? self::url(self::WIDE) ?? asset(self::SHIPPED_WIDE_WHITE)
            : asset(self::SHIPPED_WIDE_WHITE);
    }

    /** Monogramme sur fond clair. */
    public static function mark(): string
    {
        return self::stored(self::MARK) !== null
            ? self::url(self::MARK) ?? asset(self::SHIPPED_MARK)
            : asset(self::SHIPPED_MARK);
    }

    /** Monogramme sur fond sombre (voir `whitensOnDark(MARK)`). */
    public static function markWhite(): string
    {
        return self::mark();
    }

    /**
     * Faut-il **blanchir en CSS** ce logo quand il est posé sur un fond sombre ?
     *
     * Vrai dès qu'un fichier a été téléversé : on ne connaît pas ses couleurs,
     * et c'est le cas où l'on n'a pas de version claire sous la main. Faux pour
     * les fichiers livrés, dont la version sombre existe déjà (logo large) ou
     * deviendrait un aplat (monogramme).
     */
    public static function whitensOnDark(string $key): bool
    {
        return in_array($key, self::KEYS, true) && self::stored($key) !== null;
    }

    /** Un logo a-t-il été téléversé ? */
    public static function isCustom(): bool
    {
        foreach (self::KEYS as $key) {
            if (self::stored($key) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fichier **livré** correspondant à un logo, sur le fond demandé.
     *
     * Sert les aperçus de « Revenir au logo livré » dans les réglages : c'est ce
     * que le bouton rétablit littéralement.
     */
    public static function shipped(string $key, bool $dark = false): string
    {
        return asset(match (true) {
            $key === self::WIDE && $dark => self::SHIPPED_WIDE_WHITE,
            $key === self::WIDE => self::SHIPPED_WIDE,
            default => self::SHIPPED_MARK,
        });
    }

    /** URL du fichier téléversé, ou null si rien n'a été téléversé. */
    public static function url(string $key): ?string
    {
        $path = self::stored($key);

        return $path === null ? null : Storage::disk('public')->url($path);
    }

    /**
     * Chemin stocké d'un logo, seulement si le fichier existe encore : un
     * réglage pointant vers un fichier supprimé ne doit pas casser l'affichage.
     */
    private static function stored(string $key): ?string
    {
        $path = Settings::get($key);

        return filled($path) && Storage::disk('public')->exists((string) $path)
            ? (string) $path
            : null;
    }
}
