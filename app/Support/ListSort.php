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
     * @param  string  $sortParam  Nom du paramètre de tri (par défaut `sort`) ;
     *                             personnalisable quand plusieurs tableaux triables
     *                             cohabitent sur une même page (ex. `s_sort`).
     * @return array{sort: string, dir: string}
     */
    public static function resolve(Request $request, array $allowed, string $default, string $defaultDir = 'asc', string $sortParam = 'sort', string $dirParam = 'dir'): array
    {
        $sort = (string) $request->query($sortParam, $default);
        if (! in_array($sort, $allowed, true)) {
            $sort = $default;
        }

        $dir = strtolower((string) $request->query($dirParam, $defaultDir));
        $dir = $dir === 'desc' ? 'desc' : 'asc';

        return ['sort' => $sort, 'dir' => $dir];
    }
}
