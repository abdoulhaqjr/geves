<?php

namespace App\Support;

use App\Enums\EventPhase;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Contexte « colloque courant » de l'espace participant. Un compte peut participer
 * à plusieurs colloques/événements : ce helper résout l'événement active (sélection en
 * session, sinon défaut), liste les colloques du compte pour le sélecteur, et
 * bascule d'un colloque à l'autre. Les pages personnelles (tableau de bord,
 * soumissions, documents, paiement) se scopent sur cette événement.
 */
class CurrentEvent
{
    private const SESSION_KEY = 'account.current_edition_id';

    /**
     * Événements auxquelles le compte participe, la plus récente d'abord, avec leur
     * colloque (pour l'affichage du sélecteur).
     *
     * @return Collection<int, Event>
     */
    public static function events(User $account): Collection
    {
        $ids = Participant::where('user_id', $account->id)
            ->pluck('event_id')->unique()->filter();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Event::whereIn('id', $ids)->with('series')->orderByDesc('id')->get();
    }

    /**
     * Événement dont l'appel à communications est OUVERT (colloque vivant : en cours
     * ou à venir) auxquels le compte ne participe pas encore. On peut les sélectionner
     * pour y soumettre un résumé (la soumission crée la participation).
     *
     * @return Collection<int, Event>
     */
    public static function openCalls(User $account): Collection
    {
        $participatedIds = self::events($account)->pluck('id')->all();

        return Event::query()
            ->where('status', 'open')
            ->where('submission_open', true)
            ->whereNotIn('id', $participatedIds)
            ->with('series')
            ->get()
            ->reject(fn (Event $e) => in_array($e->phase(), [EventPhase::Draft, EventPhase::Closed], true))
            ->sortBy(fn (Event $e) => optional($e->date_start)->timestamp ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * Ensemble des colloques sélectionnables : ceux du compte + les appels ouverts
     * (en cours / à venir) auxquels il peut soumettre.
     *
     * @return Collection<int, Event>
     */
    public static function selectable(User $account): Collection
    {
        return self::events($account)->concat(self::openCalls($account))->values();
    }

    /** Plus d'un colloque sélectionnable → afficher le sélecteur de colloque. */
    public static function hasMultiple(User $account): bool
    {
        return self::selectable($account)->count() > 1;
    }

    /**
     * Événement « courante » : la sélection en session si elle est sélectionnable
     * (colloque du compte OU appel ouvert), sinon l'événement par défaut. Null si
     * aucun colloque sélectionnable.
     */
    public static function current(User $account): ?Event
    {
        $selectable = self::selectable($account);
        if ($selectable->isEmpty()) {
            return null;
        }

        $selected = session(self::SESSION_KEY);
        if ($selected && ($event = $selectable->firstWhere('id', (int) $selected))) {
            return $event;
        }

        // Défaut : parmi les colloques du compte (comportement inchangé) ; pour un
        // compte sans participation, le premier appel ouvert (pour pouvoir soumettre).
        $participated = self::events($account);

        return $participated->isNotEmpty()
            ? self::default($account, $participated)
            : self::openCalls($account)->first();
    }

    /**
     * Bascule le contexte vers une événement donnée. Ne fait rien (et retourne false)
     * si l'événement n'est pas sélectionnable (ni participation, ni appel ouvert).
     */
    public static function switchTo(User $account, int $editionId): bool
    {
        if (self::selectable($account)->firstWhere('id', $editionId)) {
            session([self::SESSION_KEY => $editionId]);

            return true;
        }

        return false;
    }

    /**
     * Défaut du colloque courant, parmi les événements du compte :
     *   1. un colloque EN COURS (l'événement se déroule actuellement) ;
     *   2. sinon le PROCHAIN colloque à venir (date de début future la plus proche) ;
     *   3. sinon le DERNIER colloque auquel l'utilisateur a participé (le plus récent).
     *
     * @param  Collection<int, Event>  $events
     */
    private static function default(User $account, Collection $events): Event
    {
        // Un colloque « vivant » = ni brouillon ni clôturé (appel ouvert, inscriptions,
        // évaluation ou événement en déroulement).
        $isLive = fn (Event $e) => ! in_array($e->phase(), [EventPhase::Draft, EventPhase::Closed], true);

        // 1. Un colloque EN COURS (l'événement se déroule actuellement).
        $ongoing = $events->first(fn (Event $e) => $e->phase() === EventPhase::Ongoing);
        if ($ongoing !== null) {
            return $ongoing;
        }

        // 2. Le PROCHAIN colloque qui arrive : un colloque vivant, au début le plus proche
        //    (ceux sans date passent en dernier).
        $upcoming = $events
            ->filter($isLive)
            ->sortBy(fn (Event $e) => optional($e->date_start)->timestamp ?? PHP_INT_MAX)
            ->first();
        if ($upcoming !== null) {
            return $upcoming;
        }

        // 3. Sinon le DERNIER colloque auquel l'utilisateur a participé (le plus récent).
        return $events
            ->sortByDesc(fn (Event $e) => optional($e->date_start)->timestamp ?? 0)
            ->first()
            ?? $events->first();
    }
}
