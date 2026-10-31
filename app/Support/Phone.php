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

    /**
     * Indicatif et pays de la plateforme, pour COMPOSER - jamais pour identifier.
     *
     * ⚠️ `tg` est déjà le pays initial du composant de saisie
     * (`initialCountry` dans `resources/js/app.js`) : les deux doivent rester
     * d'accord, sans quoi l'écran proposerait un pays et le paiement en
     * supposerait un autre.
     */
    public const DEFAULT_DIAL = '228';

    public const DEFAULT_COUNTRY = 'tg';

    /**
     * Forme internationale d'un numéro, pour un service qui doit l'APPELER.
     *
     * ⚠️⚠️ **CE N'EST PAS `normalize()`, ET LA DISTINCTION EST TOUT LE SUJET.**
     * `normalize()` sert à **identifier** (unicité de `users.phone`,
     * rapprochement d'auteurs) et n'invente donc **jamais** d'indicatif :
     * en inventer un rattacherait deux personnes différentes au même numéro.
     * Ici on n'identifie personne - on remet à un prestataire de paiement un
     * numéro qu'il doit pouvoir composer, et **il refuse un numéro sans pays**.
     * Un numéro local reçoit donc l'indicatif de la plateforme.
     *
     * ⚠️ `payments.phone` d'un participant n'est PAS passé par le mutateur de
     * `User::phone` : il vient d'un import, du guichet ou d'une saisie libre, et
     * arrive dans toutes les formes - « 90 01 52 84 », « 0022890015284 ».
     *
     * Rend une chaîne vide quand il n'y a pas assez de chiffres : on ne fabrique
     * pas un numéro à partir de rien.
     */
    public static function toE164(?string $phone, string $dial = self::DEFAULT_DIAL): string
    {
        $value = self::normalize($phone);
        if ($value === '' || ! self::usable($value)) {
            return '';
        }

        if (str_starts_with($value, '+')) {
            return $value;
        }

        // « 00 » est le préfixe international composé : il vaut un « + ».
        if (str_starts_with($value, '00')) {
            return '+'.substr($value, 2);
        }

        // Numéro national : on ne retire le zéro de tête que s'il y en a un
        // (plans où il s'écrit « 06… » ), le Togo n'en ayant pas.
        return '+'.$dial.ltrim($value, '0');
    }
}
