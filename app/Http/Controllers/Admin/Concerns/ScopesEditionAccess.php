<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Http\Middleware\EnsureEditionAccess;
use App\Models\Edition;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Étanchéité par édition côté contrôleur (complément du middleware
 * {@see EnsureEditionAccess}). Restreint les listes et les
 * sélecteurs d'édition aux éditions accessibles au membre du staff - sauf
 * super-administrateur, qui voit tout.
 */
trait ScopesEditionAccess
{
    /**
     * Identifiants d'éditions à imposer dans les requêtes de liste, ou null quand
     * l'utilisateur est super-admin (aucune restriction).
     *
     * @return list<int>|null
     */
    protected function scopedEditionIds(Request $request, string $capability): ?array
    {
        $user = $request->user();

        if ($user === null || $user->isColloqueSuperAdmin()) {
            return null;
        }

        return $user->accessibleEditionIds($capability);
    }

    /**
     * Éditions à proposer dans un sélecteur (toutes pour un super-admin, sinon les
     * éditions accessibles pour la capacité donnée).
     *
     * @return Collection<int, Edition>
     */
    protected function accessibleEditions(Request $request, string $capability): Collection
    {
        $user = $request->user();

        if ($user === null) {
            return collect();
        }

        return $user->isColloqueSuperAdmin()
            ? Edition::latest('id')->get()
            : $user->accessibleEditions($capability);
    }

    /**
     * Identifiant de l'édition proposée par défaut dans un sélecteur : l'édition
     * ouverte, sinon la plus récente accessible. Vide si aucune n'est accessible.
     *
     * @param  Collection<int, Edition>  $editions
     */
    protected function currentEditionId(Collection $editions): string
    {
        if ($editions->isEmpty()) {
            return '';
        }

        return (string) ($editions->firstWhere('status', 'open') ?? $editions->first())->id;
    }
}
