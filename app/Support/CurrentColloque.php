<?php

namespace App\Support;

use App\Models\Edition;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Contexte « colloque courant » de l'espace participant. Un compte peut participer
 * à plusieurs colloques/éditions : ce helper résout l'édition active (sélection en
 * session, sinon défaut), liste les colloques du compte pour le sélecteur, et
 * bascule d'un colloque à l'autre. Les pages personnelles (tableau de bord,
 * soumissions, documents, paiement) se scopent sur cette édition.
 */
class CurrentColloque
{
    private const SESSION_KEY = 'account.current_edition_id';

    /**
     * Éditions auxquelles le compte participe, la plus récente d'abord, avec leur
     * colloque (pour l'affichage du sélecteur).
     *
     * @return Collection<int, Edition>
     */
    public static function editions(User $account): Collection
    {
        $ids = Participant::where('user_id', $account->id)
            ->pluck('edition_id')->unique()->filter();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Edition::whereIn('id', $ids)->with('colloque')->orderByDesc('id')->get();
    }

    /** Le compte participe à plus d'une édition → afficher le sélecteur de colloque. */
    public static function hasMultiple(User $account): bool
    {
        return self::editions($account)->count() > 1;
    }

    /**
     * Édition « courante » : la sélection en session si elle est valide (le compte
     * y participe), sinon l'édition par défaut. Null si aucune participation.
     */
    public static function current(User $account): ?Edition
    {
        $editions = self::editions($account);
        if ($editions->isEmpty()) {
            return null;
        }

        $selected = session(self::SESSION_KEY);
        if ($selected && ($edition = $editions->firstWhere('id', (int) $selected))) {
            return $edition;
        }

        return self::default($account, $editions);
    }

    /**
     * Bascule le contexte vers une édition donnée. Ne fait rien (et retourne false)
     * si le compte ne participe pas à cette édition.
     */
    public static function switchTo(User $account, int $editionId): bool
    {
        if (self::editions($account)->firstWhere('id', $editionId)) {
            session([self::SESSION_KEY => $editionId]);

            return true;
        }

        return false;
    }

    /**
     * Défaut intelligent : l'édition à régler en priorité (communication acceptée,
     * frais non réglés), sinon la plus récente.
     *
     * @param  Collection<int, Edition>  $editions
     */
    private static function default(User $account, Collection $editions): Edition
    {
        $payableId = Participant::where('user_id', $account->id)
            ->whereHas('submissions', fn ($q) => $q->where('revision_of', 0)->where('status', 'accepted'))
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
            ->latest('id')->value('edition_id');

        return ($payableId ? $editions->firstWhere('id', $payableId) : null)
            ?? $editions->firstWhere('status', 'open')   // sinon un colloque en cours
            ?? $editions->first();                        // sinon le plus récent
    }
}
