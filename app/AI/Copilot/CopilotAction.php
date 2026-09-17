<?php

namespace App\AI\Copilot;

use App\Models\Edition;
use App\Models\User;

/**
 * Action concrète que le copilote peut exécuter SUR le système — mais jamais de
 * lui-même : le copilote la propose, l'utilisateur l'autorise explicitement, et
 * c'est seulement alors que le serveur l'exécute, sous l'autorité (et la
 * capacité RBAC) de cet utilisateur. Chaque action est cadrée par une capacité
 * et par l'édition courante.
 */
interface CopilotAction
{
    /** Identifiant stable (utilisé par l'IA pour désigner l'action). */
    public function key(): string;

    /** Libellé lisible (bouton d'autorisation). */
    public function label(): string;

    /** Capacité RBAC requise (cf. StaffRoles::PERMISSIONS) — vérifiée par édition. */
    public function capability(): string;

    /** Description destinée à l'IA (quand et pourquoi proposer cette action). */
    public function description(): string;

    /**
     * Résumé lisible de ce qui sera fait, pour l'écran d'autorisation.
     *
     * @param  array<string, mixed>  $params
     */
    public function summary(array $params, Edition $edition): string;

    /**
     * Exécute l'action et renvoie un message de résultat (affiché dans le chat).
     *
     * @param  array<string, mixed>  $params
     */
    public function execute(array $params, Edition $edition, User $user): string;
}
