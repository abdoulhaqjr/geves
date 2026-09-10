<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Résout le tri d'une liste à partir de la requête, borné à une liste blanche
 * de colonnes triables (protège contre l'injection dans orderBy).
 */
class ListSort
{
    /**
     * @param  array<int, string>  $allowed  Colonnes triables autorisées.
     * @return array{sort: string, dir: string}
     */
    public static function resolve(Request $request, array $allowed, string $default, string $defaultDir = 'asc'): array
    {
        $sort = (string) $request->query('sort', $default);
        if (! in_array($sort, $allowed, true)) {
            $sort = $default;
        }

        $dir = strtolower((string) $request->query('dir', $defaultDir));
        $dir = $dir === 'desc' ? 'desc' : 'asc';

        return ['sort' => $sort, 'dir' => $dir];
    }
}
