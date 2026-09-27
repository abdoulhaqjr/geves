<?php

namespace App\Support;

/**
 * Forme canonique d'un numéro de téléphone sur la plateforme.
 *
 * ⚠️ Le téléphone est un **identifiant fort** : il rapproche un auteur ou un
 * formateur d'un compte existant (cf. {@see AuthorMatcher}), et le serveur
 * impose alors l'identité de ce compte. Deux écritures différentes du même
 * numéro (« +228 90 01 52 84 » et « +22890015284 ») créeraient deux identités
 * pour une seule personne, et rendraient l'unicité contournable par la simple
 * mise en forme. Tout numéro est donc normalisé **à l'écriture** (mutateur sur
 * `User::phone`) et avant toute vérification d'unicité.
 *
 * On se limite à la mise en forme : séparateurs retirés, `+` initial conservé.
 * ⚠️ On n'ajoute **jamais** d'indicatif pays deviné - un numéro local resterait
 * ambigu, et inventer un préfixe rattacherait des personnes au mauvais pays.
 * C'est le composant `<x-ukc.phone>` qui produit l'E.164 à la saisie.
 */
class Phone
{
    /** Nombre minimal de chiffres pour qu'un numéro soit exploitable. */
    public const MIN_DIGITS = 8;

    /** Numéro nettoyé (espaces, points, tirets, parenthèses retirés). */
    public static function normalize(?string $phone): string
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return '';
        }

        // Le « + » compte dès qu'il précède le premier chiffre : « (+228) 90… »
        // est international au même titre que « +228 90… ».
        $plus = (bool) preg_match('/^\D*\+/', $raw);
        $digits = preg_replace('/\D/', '', $raw) ?? '';

        return $digits === '' ? '' : ($plus ? '+' : '').$digits;
    }

    /** Numéro exploitable comme identifiant (assez de chiffres) ? */
    public static function usable(?string $phone): bool
    {
        return strlen(preg_replace('/\D/', '', (string) $phone) ?? '') >= self::MIN_DIGITS;
    }
}
