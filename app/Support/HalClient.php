<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Client du référentiel des structures HAL (AuréHAL). Sert l'autocomplétion des
 * affiliations depuis l'API publique des archives ouvertes.
 *
 * - Activable via le réglage `services.hal.enabled` (OFF par défaut).
 * - Résultats mis en cache 1 jour (référentiel stable).
 * - Fail-closed : toute erreur/temps dépassé renvoie une liste vide (jamais
 *   d'exception remontée à l'UI ; la saisie libre reste possible).
 */
class HalClient
{
    public static function enabled(): bool
    {
        return (bool) config('services.hal.enabled', false);
    }

    /**
     * Recherche de structures par nom/sigle.
     *
     * @return array<int, array{docid:string, label:string, acronym:string, country:string}>
     */
    public static function searchStructures(string $query, int $limit = 8): array
    {
        $query = trim($query);

        if (! self::enabled() || mb_strlen($query) < 3) {
            return [];
        }

        $cacheKey = 'hal.structures.'.md5(mb_strtolower($query)).".{$limit}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($query, $limit) {
            try {
                $response = Http::timeout(4)
                    ->acceptJson()
                    ->get((string) config('services.hal.endpoint'), [
                        'q' => $query,
                        'fl' => 'docid,label_s,name_s,acronym_s,country_s',
                        'wt' => 'json',
                        'rows' => $limit,
                    ]);

                if (! $response->successful()) {
                    return [];
                }

                $docs = $response->json('response.docs') ?? [];

                return collect($docs)
                    ->map(fn ($d) => [
                        'docid' => (string) ($d['docid'] ?? ''),
                        'label' => (string) ($d['label_s'] ?? $d['name_s'] ?? ''),
                        'acronym' => (string) ($d['acronym_s'] ?? ''),
                        'country' => (string) ($d['country_s'] ?? ''),
                    ])
                    ->filter(fn ($d) => $d['label'] !== '')
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                report($e);

                return [];
            }
        });
    }
}
