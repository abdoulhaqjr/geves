<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Http\Middleware\EnsureEditionAccess;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Étanchéité par événement côté contrôleur (complément du middleware
 * {@see EnsureEditionAccess}). Restreint les listes et les
 * sélecteurs d'événement aux événements accessibles au membre du staff - sauf
 * administrateur ({@see User::isColloqueAdmin()}), qui voit tout.
 */
trait ScopesEditionAccess
{
    /**
     * Identifiants d'événements à imposer dans les requêtes de liste, ou null quand
     * l'utilisateur est administrateur (aucune restriction).
     *
     * @return list<int>|null
     */
    protected function scopedEditionIds(Request $request, string $capability): ?array
    {
        $user = $request->user();

        if ($user === null || $user->isColloqueAdmin()) {
            return null;
        }

        return $user->accessibleEditionIds($capability);
    }

    /**
     * Événements à proposer dans un sélecteur (tous pour un administrateur, sinon les
     * événements accessibles pour la capacité donnée).
     *
     * @return Collection<int, Event>
     */
    protected function accessibleEditions(Request $request, string $capability): Collection
    {
        $user = $request->user();

        if ($user === null) {
            return collect();
        }

        return $user->isColloqueAdmin()
            ? Event::latest('id')->get()
            : $user->accessibleEditions($capability);
    }

    /**
     * Identifiant de l'événement proposée par défaut dans un sélecteur : l'événement
     * ouverte, sinon la plus récente accessible. Vide si aucune n'est accessible.
     *
     * @param  Collection<int, Event>  $events
     */
    protected function currentEditionId(Collection $events): string
    {
        if ($events->isEmpty()) {
            return '';
        }

        return (string) ($events->firstWhere('status', 'open') ?? $events->first())->id;
    }
}
