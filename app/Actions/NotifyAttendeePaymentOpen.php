<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Participant;
use App\Support\Fees;
use App\Support\Money;
use App\Support\Notifier;

/**
 * **Prévenir les participants simples que le règlement vient d'ouvrir.**
 *
 * Ils se sont inscrits, et on leur a dit que le paiement ouvrirait « plus tard,
 * en général après la clôture des dépôts ». Sans ce message, « plus tard »
 * voudrait dire : revenir sur la page tous les jours pour voir.
 *
 * ⚠️ **À n'appeler QUE depuis une action déclenchée par un humain**, et
 * seulement sur la BASCULE fermé → ouvert ({@see SaveEvent}) : on
 * ne notifie jamais depuis un évènement de modèle, sinon un seed ou un rejeu
 * idempotent enverrait des centaines de messages. Même règle que
 * {@see NotifySlotRescheduled}.
 *
 * ⚠️ **On ne prévient que ceux que ça concerne** : inscrits comme participants
 * simples, pas encore réglés, et rattachés à un compte. Un communicant n'a rien
 * à y faire - ses frais étaient dus dès l'acceptation, sans attendre ce réglage.
 */
class NotifyAttendeePaymentOpen
{
    /** @return int nombre de personnes prévenues */
    public function handle(Event $event): int
    {
        $montant = $this->montantLisible($event);
        $url = route('account.payment', $event);
        $nom = $event->title ?: (string) $event->year;

        $prevenus = 0;

        Participant::with('user')
            ->where('event_id', $event->id)
            ->whereNotNull('user_id')
            ->get()
            ->each(function (Participant $participant) use (&$prevenus, $montant, $url, $nom): void {
                // ⚠️ `isRegisteredAttendee()` lit une DATE, pas le rôle : celui qui
                // s'est inscrit puis a déposé un résumé reste un inscrit.
                if (! $participant->isRegisteredAttendee() || $participant->hasPaid()) {
                    return;
                }

                // ⚠️ Un communicant accepté a déjà pu régler sans ce réglage : lui
                // annoncer une ouverture ne lui apprendrait rien.
                if ($participant->hasAcceptedSubmission()) {
                    return;
                }

                $corps = 'Le règlement des frais d’inscription à « '.$nom.' » est maintenant ouvert.'
                    .($montant !== null ? ' Votre montant : '.$montant.'.' : '')
                    .' Vous pouvez régler depuis votre espace participant.';

                Notifier::account(
                    $participant->user,
                    kind: 'payment_open',
                    title: 'Vous pouvez régler vos frais d’inscription',
                    body: $corps,
                    url: $url,
                    icon: 'card',
                );

                $prevenus++;
            });

        return $prevenus;
    }

    /**
     * Le montant tel que le participant le lira, ou null.
     *
     * ⚠️ Le tarif se déduit du GRADE, et il diffère donc d'une personne à
     * l'autre : on ne l'annonce que si la grille n'en propose qu'un seul.
     * Annoncer le tarif du voisin serait pire que de n'en annoncer aucun.
     */
    private function montantLisible(Event $event): ?string
    {
        $montants = collect(Fees::grid($event))
            ->map(fn (array $tier) => (float) ($tier['amount'] ?? 0))
            ->unique();

        return $montants->count() === 1
            ? Money::format((float) $montants->first())
            : null;
    }
}
