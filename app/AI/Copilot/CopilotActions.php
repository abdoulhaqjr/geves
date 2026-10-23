<?php

namespace App\AI\Copilot;

use App\AI\Copilot\Actions\SendDueEmails;
use App\AI\Copilot\Actions\DeleteSubmission;
use App\AI\Copilot\Actions\SendPaymentReminders;
use App\Models\Event;
use App\Models\User;

/**
 * Registre des actions exécutables par le copilote. Fournit la liste des actions
 * qu'un utilisateur donné peut réellement déclencher sur un événement (selon ses
 * capacités), pour cadrer aussi bien le prompt de l'IA que l'exécution serveur.
 */
class CopilotActions
{
    /** @var array<int, CopilotAction> */
    protected array $actions;

    public function __construct()
    {
        $this->actions = [
            new SendPaymentReminders,
            new SendDueEmails,
            new DeleteSubmission,
        ];
    }

    /** @return array<int, CopilotAction> */
    public function all(): array
    {
        return $this->actions;
    }

    public function get(string $key): ?CopilotAction
    {
        foreach ($this->actions as $action) {
            if ($action->key() === $key) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Actions que l'utilisateur peut exécuter sur cet événement (capacité RBAC).
     *
     * @return array<int, CopilotAction>
     */
    public function availableFor(User $user, ?Event $event): array
    {
        if ($event === null) {
            return [];
        }

        return array_values(array_filter(
            $this->actions,
            fn (CopilotAction $a) => $user->canOnEdition($a->capability(), $event),
        ));
    }

    /**
     * Résout une action autorisée pour cet utilisateur sur cet événement, ou null
     * si elle n'existe pas / n'est pas permise.
     */
    public function resolveFor(User $user, ?Event $event, string $key): ?CopilotAction
    {
        $action = $this->get($key);
        if ($action === null || $event === null) {
            return null;
        }

        return $user->canOnEdition($action->capability(), $event) ? $action : null;
    }
}
