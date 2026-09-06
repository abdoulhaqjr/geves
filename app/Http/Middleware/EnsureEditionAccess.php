<?php

namespace App\Http\Middleware;

use App\Models\Edition;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Étanchéité d'accès par édition (modèle « pur ») : un membre du staff n'agit
 * sur une édition que s'il y est affecté avec une fonction couvrant la capacité
 * demandée. L'administrateur (super-capacité `manage_colloques`) passe partout.
 *
 * Résolution de l'édition en contexte : paramètre de route {edition}, sinon
 * modèle enfant lié (via edition_id / relation edition), sinon `edition_id` de
 * la requête. Sur une page de liste (aucune édition en contexte), l'accès est
 * accordé s'il reste au moins une édition sur laquelle l'utilisateur a la
 * capacité ; le contrôleur restreint alors les résultats à ces éditions.
 *
 * Usage : ->middleware('edition.cap:payments')
 */
class EnsureEditionAccess
{
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->isColloqueSuperAdmin()) {
            return $next($request);
        }

        $params = $request->route()?->parameters() ?? [];
        $hasModelParam = false;

        foreach ($params as $param) {
            if (! $param instanceof Model) {
                continue;
            }
            $hasModelParam = true;
            $edition = $this->editionOf($param);
            if ($edition !== null) {
                abort_unless($user->canOnEdition($capability, $edition), 403);

                return $next($request);
            }
        }

        // Édition fournie explicitement (pages filtrées par edition_id).
        $id = $request->input('edition_id');
        if (is_numeric($id) && ($edition = Edition::find((int) $id)) !== null) {
            abort_unless($user->canOnEdition($capability, $edition), 403);

            return $next($request);
        }

        // Une ressource précise était visée mais on n'a pas pu la rattacher à une
        // édition : on refuse par prudence (pas de fuite inter-colloques).
        if ($hasModelParam) {
            abort(403);
        }

        // Page de liste : accès si l'utilisateur a la capacité sur au moins une
        // édition (le contrôleur restreint ensuite aux éditions accessibles).
        abort_if($user->accessibleEditionIds($capability) === [], 403);

        return $next($request);
    }

    /** Édition rattachée à un modèle enfant (via edition_id ou relation edition). */
    private function editionOf(Model $model): ?Edition
    {
        if ($model instanceof Edition) {
            return $model;
        }

        $editionId = $model->getAttribute('edition_id');
        if (is_numeric($editionId)) {
            return Edition::find((int) $editionId);
        }

        if (method_exists($model, 'edition')) {
            $related = $model->getAttribute('edition');

            return $related instanceof Edition ? $related : null;
        }

        return null;
    }
}
