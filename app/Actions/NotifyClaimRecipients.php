<?php

namespace App\Actions;

use App\Models\Claim;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Prévient les bonnes personnes qu'une réclamation (ou un incident) arrive.
 *
 * ⚠️ La règle est le CIBLAGE : une réclamation rattachée à un événement
 * remonte à ses **gestionnaire et co-gestionnaire** (eux seuls) ; une
 * réclamation générale, sans événement, remonte aux **administrateurs**. On
 * n'inonde donc pas tout le back-office. Chaque destinataire reçoit l'in-app
 * ET l'e-mail générique (type `claim_new`), et peut couper l'e-mail dans ses
 * préférences. Appelé à la création et au transfert vers un événement.
 */
class NotifyClaimRecipients
{
    /** Rôles d'équipe qui portent une réclamation d'événement. */
    public const MANAGER_ROLES = ['gerant', 'co_gerant'];

    public function handle(Claim $claim, bool $transferred = false): void
    {
        $recipients = $this->recipients($claim);
        if ($recipients->isEmpty()) {
            return;
        }

        $accord = $claim->type === 'incident' ? 'transféré' : 'transférée';
        $title = $transferred
            ? $claim->typeLabel().' '.$accord.' vers un événement'
            : ($claim->type === 'incident' ? 'Nouvel incident signalé' : 'Nouvelle réclamation');
        $body = trim($claim->subject.' - '.($claim->name ?: $claim->email ?: 'déclarant inconnu'));

        foreach ($recipients as $user) {
            Notifier::user($user, 'claim_new', $title, $body, route('admin.claims.show', $claim), 'inbox');
        }
    }

    /**
     * Destinataires selon le rattachement : gestionnaires de l'événement, ou
     * administrateurs pour une réclamation générale.
     *
     * @return Collection<int, User>
     */
    public function recipients(Claim $claim): Collection
    {
        if ($claim->event_id === null) {
            return User::query()->administrateurs()->get();
        }

        $ids = DB::table('event_user')
            ->where('event_id', $claim->event_id)
            ->whereIn('role', self::MANAGER_ROLES)
            ->pluck('user_id')->unique();

        $managers = $ids->isEmpty()
            ? new Collection
            : User::query()->whereIn('id', $ids)->get();

        // ⚠️ Filet : un événement sans gestionnaire ni co-gestionnaire laisserait
        // la réclamation sans personne pour la voir - on prévient alors les
        // administrateurs plutôt que de la perdre.
        return $managers->isNotEmpty() ? $managers : User::query()->administrateurs()->get();
    }
}
