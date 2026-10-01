<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Logos de la plateforme, remplaçables depuis **Réglages › Général**.
 *
 * **Deux formes**, parce qu'un logo ne tient pas dans toutes les largeurs :
 *  - **large** (lockup complet, marque + nom de l'établissement) : pied de page,
 *    pages de connexion et de création de compte, bandeau des pages publiques
 *    d'événement - partout où l'on dispose de la largeur ;
 *  - **compacte** (monogramme carré) : en-tête public et menus latéraux, où le
 *    logo côtoie un texte ou se réduit à une colonne d'icônes.
 *
 * Chaque forme accepte **une version pour fonds sombres, facultative**.
 *
 * ⚠️ **Sans elle, on blanchit en CSS** (`.logo-auto-white`,
 * `brightness(0) invert(1)`) : `whitensOnDark()` dit quand poser la classe. Ce
 * secours rend le logo lisible sur le bleu, mais il met **toute** la matière
 * opaque en blanc - les évidements (lettres détourées dans un écusson) sont
 * perdus. Une vraie version claire est un **dessin**, aucun filtre ne la déduit
 * du logo couleur : c'est pourquoi la case existe toujours.
 *
 * ⚠️ **Repli** sur les fichiers livrés dans `public/img` si rien n'est téléversé
 * ou si le fichier réglé a disparu - jamais d'image cassée. Le repli sombre du
 * logo large est `logo-uk-white.png`, **déjà dessiné en blanc** : on ne le
 * blanchit pas (et blanchir le monogramme livré en ferait un aplat).
 *
 * ⚠️ Les clés stockées gardent leurs noms d'origine : les renommer invaliderait
 * les logos déjà téléversés.
 */
class Branding
{
    /** Logo large (lockup complet), fonds clairs. */
    public const WIDE = 'app_logo';

    /** Logo large, fonds sombres - facultatif. */
    public const WIDE_WHITE = 'app_logo_white';

    /** Logo compact (monogramme carré), fonds clairs. */
    public const MARK = 'app_logo_mark';

    /** Logo compact, fonds sombres - facultatif. */
    public const MARK_WHITE = 'app_logo_mark_white';

    /** Fichiers livrés avec l'application. */
    private const SHIPPED_WIDE = 'img/logo-uk.png';

    private const SHIPPED_WIDE_WHITE = 'img/logo-uk-white.png';

    private const SHIPPED_MARK = 'img/apple-touch-icon.png';

    /** Les deux formes, clé claire => clé sombre. Ordre de l'écran de réglages. */
    public const FORMS = [
        self::WIDE => self::WIDE_WHITE,
        self::MARK => self::MARK_WHITE,
    ];

    /** Toutes les clés téléversables (la clé sert aussi de nom de champ). */
    public const KEYS = [self::WIDE, self::WIDE_WHITE, self::MARK, self::MARK_WHITE];

    /** Logo large sur fond clair. */
    public static function wide(): string
    {
        return self::first([self::WIDE], self::SHIPPED_WIDE);
    }

    /**
     * Logo large sur fond sombre.
     *
     * ⚠️ À accompagner de `.logo-auto-white` quand `whitensOnDark(WIDE)` : sans
     * elle, le logo à texte sombre servi en secours disparaît sur le bleu.
     */
    public static function wideWhite(): string
    {
        return self::first([self::WIDE_WHITE, self::WIDE], self::SHIPPED_WIDE_WHITE);
    }

    /** Monogramme sur fond clair. */
    public static function mark(): string
    {
        return self::first([self::MARK], self::SHIPPED_MARK);
    }

    /** Monogramme sur fond sombre (voir `whitensOnDark(MARK)`). */
    public static function markWhite(): string
    {
        return self::first([self::MARK_WHITE, self::MARK], self::SHIPPED_MARK);
    }

    /**
     * Faut-il **blanchir en CSS** cette forme posée sur un fond sombre ?
     *
     * Vrai dans le seul cas du secours : pas de version claire téléversée, mais
     * un fichier clair qui, lui, l'a été. Faux quand une version claire existe
     * (on respecte le dessin de l'admin) et faux sur les fichiers livrés, dont
     * la version sombre est déjà dessinée (logo large) ou deviendrait un aplat
     * (monogramme).
     *
     * @param  string  $key  la clé **claire** de la forme (WIDE ou MARK)
     */
    public static function whitensOnDark(string $key): bool
    {
        $dark = self::FORMS[$key] ?? null;

        return $dark !== null
            && self::stored($dark) === null
            && self::stored($key) !== null;
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

    /**
     * Fichier **livré** correspondant à une variante.
     *
     * Sert les aperçus de « Supprimer » dans les réglages : c'est ce que le
     * bouton rétablit, le logo livré reprenant la main.
     */
    public static function shipped(string $key): string
    {
        return asset(match ($key) {
            self::WIDE => self::SHIPPED_WIDE,
            self::WIDE_WHITE => self::SHIPPED_WIDE_WHITE,
            default => self::SHIPPED_MARK,
        });
    }

    /** URL du fichier téléversé pour cette variante, ou null. */
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
     * Chemin stocké d'une variante, seulement si le fichier existe encore : un
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
