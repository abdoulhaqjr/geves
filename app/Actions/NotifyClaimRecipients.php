<?php

namespace App\Actions;

use App\Models\Claim;
use App\Models\User;
use App\Support\EventManagers;
use App\Support\Notifier;
use Illuminate\Support\Collection;

/**
 * Prévient les bonnes personnes qu'une réclamation (ou un incident) arrive.
 *
 * ⚠️ La règle est le CIBLAGE : une réclamation rattachée à un événement
 * remonte à ses **gestionnaire et co-gestionnaire** (eux seuls) ; une
 * réclamation générale, sans événement, remonte aux **administrateurs**. On
 * n'inonde donc pas tout le back-office. Chaque destinataire reçoit l'in-app
 * ET l'e-mail générique (type `claim_new`), et peut couper l'e-mail dans ses
 * préférences. Appelé à la création et au transfert vers un événement. Le
 * ciblage lui-même vit dans {@see EventManagers::forEvent()} (source unique).
 */
class NotifyClaimRecipients
{
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
     * administrateurs pour une réclamation générale (et en repli d'un événement
     * sans gestionnaire, sinon la réclamation n'aurait personne pour la voir).
     *
     * @return Collection<int, User>
     */
    public function recipients(Claim $claim): Collection
    {
        return EventManagers::forEvent($claim->event_id);
    }
}
