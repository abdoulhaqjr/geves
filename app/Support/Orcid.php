<?php

namespace App\Support;

/**
 * Identifiant ORCID : normalisation, validation (format + chiffre de contrôle
 * ISO 7064 MOD 11-2) et lien vérifiable. Aucune dépendance externe — la
 * vérification d'appartenance (OAuth « connexion ORCID ») viendra en surcouche.
 */
class Orcid
{
    /**
     * Forme canonique « 0000-0002-1825-0097 », ou null si invalide.
     * Accepte une URL orcid.org, avec ou sans tirets.
     */
    public static function normalize(string $raw): ?string
    {
        $raw = strtoupper(trim($raw));
        $raw = (string) preg_replace('#^HTTPS?://ORCID\.ORG/#', '', $raw);
        $compact = (string) preg_replace('/[^0-9X]/', '', $raw);

        if (strlen($compact) !== 16) {
            return null;
        }
        // « X » n'est autorisé qu'en dernière position (chiffre de contrôle = 10).
        if (str_contains(substr($compact, 0, 15), 'X')) {
            return null;
        }
        if (! self::checksumMatches($compact)) {
            return null;
        }

        return implode('-', str_split($compact, 4));
    }

    /** Vrai si `$raw` est un ORCID valide (format + checksum). */
    public static function isValid(string $raw): bool
    {
        return self::normalize($raw) !== null;
    }

    /** Lien vérifiable https://orcid.org/…, ou null si invalide. */
    public static function url(string $raw): ?string
    {
        $id = self::normalize($raw);

        return $id !== null ? 'https://orcid.org/'.$id : null;
    }

    /** Vérifie le chiffre de contrôle (16e caractère) selon MOD 11-2. */
    private static function checksumMatches(string $compact): bool
    {
        $total = 0;
        for ($i = 0; $i < 15; $i++) {
            $total = ($total + (int) $compact[$i]) * 2;
        }
        $result = (12 - ($total % 11)) % 11;
        $check = $result === 10 ? 'X' : (string) $result;

        return $check === $compact[15];
    }
}
