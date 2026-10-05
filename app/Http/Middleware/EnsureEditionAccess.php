<?php

namespace App\Http\Middleware;

use App\Models\Event;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Étanchéité d'accès par événement (modèle « pur ») : un membre du staff n'agit
 * sur une événement que s'il y est affecté avec une fonction couvrant la capacité
 * demandée. L'administrateur (super-capacité `manage_colloques`) passe partout.
 *
 * Résolution de l'événement en contexte : paramètre de route {event}, sinon
 * modèle enfant lié (via event_id / relation edition), sinon `event_id` de
 * la requête. Sur une page de liste (aucune événement en contexte), l'accès est
 * accordé s'il reste au moins une événement sur laquelle l'utilisateur a la
 * capacité ; le contrôleur restreint alors les résultats à ces événements.
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
            $event = $this->editionOf($param);
            if ($event !== null) {
                abort_unless($user->canOnEdition($capability, $event), 403);

                return $next($request);
            }
        }

        // Événement fournie explicitement (pages filtrées par event_id).
        $id = $request->input('event_id');
        if (is_numeric($id) && ($event = Event::find((int) $id)) !== null) {
            abort_unless($user->canOnEdition($capability, $event), 403);

            return $next($request);
        }

        // Une ressource précise était visée mais on n'a pas pu la rattacher à une
        // événement : on refuse par prudence (pas de fuite inter-colloques).
        if ($hasModelParam) {
            abort(403);
        }

        // Création d'un colloque (aucune événement en contexte) : réservée aux
        // administrateurs de colloque (Phase 3).
        if ($capability === 'editions_write' && $user->canCreateColloque()) {
            return $next($request);
        }

        // Page de liste : accès si l'utilisateur a la capacité sur au moins une
        // événement (le contrôleur restreint ensuite aux événements accessibles).
        abort_if($user->accessibleEditionIds($capability) === [], 403);

        return $next($request);
    }

    /** Événement rattachée à un modèle enfant (via event_id ou relation edition). */
    private function editionOf(Model $model): ?Event
    {
        if ($model instanceof Event) {
            return $model;
        }

        $editionId = $model->getAttribute('event_id');
        if (is_numeric($editionId)) {
            return Event::find((int) $editionId);
        }

        if (method_exists($model, 'event')) {
            $related = $model->getAttribute('event');

            return $related instanceof Event ? $related : null;
        }

        return null;
    }
}
