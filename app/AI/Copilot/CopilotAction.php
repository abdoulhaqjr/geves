<?php

namespace App\AI\Copilot;

use App\Models\Event;
use App\Models\User;

/**
 * Action concrète que le copilote peut exécuter SUR le système - mais jamais de
 * lui-même : le copilote la propose, l'utilisateur l'autorise explicitement, et
 * c'est seulement alors que le serveur l'exécute, sous l'autorité (et la
 * capacité RBAC) de cet utilisateur. Chaque action est cadrée par une capacité
 * et par l'événement courant.
 */
interface CopilotAction
{
    /** Identifiant stable (utilisé par l'IA pour désigner l'action). */
    public function key(): string;

    /** Libellé lisible (bouton d'autorisation). */
    public function label(): string;

    /** Capacité RBAC requise (cf. StaffRoles::PERMISSIONS) - vérifiée par événement. */
    public function capability(): string;

    /** Description destinée à l'IA (quand et pourquoi proposer cette action). */
    public function description(): string;

    /**
     * État actuel du ciblage RÉEL de l'action (ex. « 3 inscrits concernés »),
     * injecté dans le prompt pour que l'IA décide sur les données propres à
     * l'action et non sur une statistique générale du tableau de bord. Null si
     * non pertinent.
     */
    public function context(Event $event): ?string;

    /**
     * Résumé lisible de ce qui sera fait, pour l'écran d'autorisation.
     *
     * @param  array<string, mixed>  $params
     */
    public function summary(array $params, Event $event): string;

    /**
     * Exécute l'action et renvoie un message de résultat (affiché dans le chat).
     *
     * @param  array<string, mixed>  $params
     */
    public function execute(array $params, Event $event, User $user): string;
}
