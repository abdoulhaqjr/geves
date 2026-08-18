<?php

namespace App\Support;

/**
 * Description lisible d'un User-Agent (navigateur + système) pour l'affichage des
 * sessions actives d'un compte. Heuristique simple et déterministe, sans dépendance.
 */
class UserAgent
{
    public static function describe(?string $ua): string
    {
        $ua = trim((string) $ua);
        if ($ua === '') {
            return 'Appareil inconnu';
        }

        $browser = self::browser($ua);
        $os = self::os($ua);

        return trim($browser.($os ? ' · '.$os : '')) ?: 'Appareil inconnu';
    }

    protected static function browser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Navigateur',
        };
    }

    protected static function os(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Linux') => 'Linux',
            default => '',
        };
    }
}
