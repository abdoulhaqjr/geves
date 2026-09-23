<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Autocomplétion des affiliations via **ROR** (Research Organization Registry,
 * https://ror.org) - API publique, gratuite, ouverte, sans clé. Couvre les
 * universités, laboratoires et instituts de recherche du monde entier.
 *
 * Résilient : cache 1 jour, timeout court, **fail-closed** (liste vide en cas
 * d'erreur/timeout) - la saisie libre reste toujours possible.
 */
class RorClient
{
    private const ENDPOINT = 'https://api.ror.org/organizations';

    /**
     * @return list<string> noms d'organisations (avec pays entre parenthèses)
     */
    public static function suggest(string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 3) {
            return [];
        }

        return Cache::remember('ror:'.mb_strtolower($query), now()->addDay(), function () use ($query): array {
            try {
                $res = Http::timeout(4)->acceptJson()->get(self::ENDPOINT, ['query' => $query]);
                if (! $res->ok()) {
                    return [];
                }

                return collect($res->json('items', []))
                    ->take(8)
                    ->map(function (array $item): string {
                        $name = trim((string) ($item['name'] ?? ''));
                        $country = trim((string) ($item['country']['country_name'] ?? ''));

                        return ($name !== '' && $country !== '') ? $name.' ('.$country.')' : $name;
                    })
                    ->filter()
                    ->values()
                    ->all();
            } catch (\Throwable) {
                return []; // fail-closed : saisie libre préservée
            }
        });
    }
}
