<?php

namespace App\Support;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Le pays d'une adresse IP.
 *
 * ⚠️⚠️ **LA BASE LOCALE D'ABORD, TOUJOURS.** Résoudre un pays, c'est dire où se
 * trouve quelqu'un : la base DB-IP Country Lite est embarquée dans le serveur
 * précisément pour que l'IP d'un participant ne parte chez personne. Le repli
 * HTTP ({@see self::fallbackNames()}) existe parce qu'une base peut manquer ou
 * ignorer une plage, mais il TRANSMET l'adresse à un tiers : il reste éteint
 * tant qu'on ne l'allume pas, et il ne sert que les adresses que la base locale
 * n'a pas su lire.
 *
 * ⚠️ **L'ÉCHEC ÉTAIT MUET.** Sans fichier de base, cette classe rendait un
 * tableau vide et la colonne « Pays » affichait des tirets : rien ne distinguait
 * « aucun de ces visiteurs n'est localisable » de « la base n'a jamais été
 * installée ». {@see self::databaseInstalled()} donne à l'écran de quoi le dire.
 *
 * ⚠️ **Une IP privée n'a pas de pays, et ce n'est pas une panne.** En
 * développement tout vient de 127.0.0.1 ; derrière un proxy mal déclaré, tout
 * vient de l'adresse du proxy. {@see self::isPublic()} sépare les deux cas pour
 * que l'écran puisse l'écrire.
 */
class IpCountry
{
    /** Un pays trouvé ne change pas ; une absence peut n'être qu'une base en retard. */
    private const TTL_TROUVE = 60 * 60 * 24 * 30;

    private const TTL_INCONNU = 60 * 60 * 24;

    /**
     * Résout les pays, base locale d'abord, repli HTTP ensuite s'il est allumé.
     *
     * @param  iterable<string|null>  $ips
     * @return array<string, string>
     */
    public function namesFor(iterable $ips): array
    {
        $aResoudre = [];
        foreach ($ips as $ip) {
            if (is_string($ip) && self::isPublic($ip)) {
                $aResoudre[$ip] = true;
            }
        }
        $aResoudre = array_keys($aResoudre);
        if ($aResoudre === []) {
            return [];
        }

        $pays = [];
        $restantes = [];
        foreach ($aResoudre as $ip) {
            $cache = Cache::get(self::cacheKey($ip));
            if ($cache === null) {
                $restantes[] = $ip;
            } elseif ($cache !== '') {
                $pays[$ip] = $cache;
            }
            // '' = déjà cherché, introuvable : on ne recommence pas tout de suite.
        }

        if ($restantes !== []) {
            $locales = $this->databaseNames($restantes);
            foreach ($locales as $ip => $nom) {
                $pays[$ip] = $nom;
                Cache::put(self::cacheKey($ip), $nom, self::TTL_TROUVE);
            }
            $restantes = array_values(array_diff($restantes, array_keys($locales)));
        }

        if ($restantes !== [] && self::fallbackEnabled()) {
            foreach ($this->fallbackNames($restantes) as $ip => $nom) {
                if ($nom !== '') {
                    $pays[$ip] = $nom;
                }
                Cache::put(self::cacheKey($ip), $nom, $nom !== '' ? self::TTL_TROUVE : self::TTL_INCONNU);
            }
        }

        return $pays;
    }

    /**
     * Ce que la base embarquée sait, et rien d'autre.
     *
     * @param  array<int, string>  $ips
     * @return array<string, string>
     */
    private function databaseNames(array $ips): array
    {
        $path = (string) config('geoip.database_path');
        if (! is_file($path)) {
            return [];
        }

        try {
            $reader = new Reader($path, ['fr', 'en']);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }

        $countries = [];
        try {
            foreach ($ips as $ip) {
                try {
                    $country = $reader->country($ip)->country;
                    $name = $country->name ?: $country->isoCode;
                    if ($name) {
                        $countries[$ip] = $name;
                    }
                } catch (AddressNotFoundException) {
                    // Certaines plages publiques ne figurent pas dans l'édition Lite.
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        } finally {
            $reader->close();
        }

        return $countries;
    }

    /**
     * Le repli hébergé, à la manière de Bizzcart : un service HTTP sans clé.
     *
     * ⚠️ **Il part chez un tiers.** D'où le plafond par requête : une liste de
     * vingt comptes ne doit pas devenir vingt appels sortants au rendu d'une
     * page. Ce qui dépasse attendra le prochain affichage, une fois le début de
     * la liste en cache.
     *
     * @param  array<int, string>  $ips
     * @return array<string, string> Le nom trouvé, ou '' pour « cherché, introuvable ».
     */
    private function fallbackNames(array $ips): array
    {
        $gabarit = (string) config('geoip.fallback.endpoint');
        if (! str_contains($gabarit, '{ip}')) {
            return [];
        }

        $plafond = max(1, (int) config('geoip.fallback.max_per_request', 10));
        $delai = max(1, (int) config('geoip.fallback.timeout', 3));
        $noms = [];

        foreach (array_slice($ips, 0, $plafond) as $ip) {
            $noms[$ip] = '';
            try {
                $reponse = Http::timeout($delai)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get(str_replace('{ip}', rawurlencode($ip), $gabarit));

                if (! $reponse->successful()) {
                    continue;
                }

                $corps = (array) $reponse->json();
                if (($corps['error'] ?? false) || filled($corps['reason'] ?? null)) {
                    continue;
                }

                // ipapi.co rend `country_name` ; ip-api.com rend `country`.
                $nom = $corps['country_name'] ?? $corps['country'] ?? null;
                if (is_string($nom) && trim($nom) !== '') {
                    $noms[$ip] = mb_substr(trim($nom), 0, 80);
                }
            } catch (\Throwable) {
                // Un service injoignable n'empêche pas d'afficher la liste.
            }
        }

        return $noms;
    }

    /** Une adresse publique, seule à pouvoir désigner un pays. */
    public static function isPublic(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /** La base embarquée est-elle en place ? */
    public static function databaseInstalled(): bool
    {
        return is_file((string) config('geoip.database_path'));
    }

    public static function fallbackEnabled(): bool
    {
        return (bool) config('geoip.fallback.enabled', false);
    }

    /** Y a-t-il seulement de quoi résoudre un pays ? */
    public static function resolvable(): bool
    {
        return self::databaseInstalled() || self::fallbackEnabled();
    }

    private static function cacheKey(string $ip): string
    {
        return 'geoip:pays:'.sha1($ip);
    }
}
