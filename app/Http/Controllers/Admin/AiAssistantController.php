<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AskAdmin;
use App\AI\Copilot\CopilotActions;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\AiAgents;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Copilote d'administration (chat), contrôleur + Blade + endpoints JSON. Assiste
 * le staff par des synthèses et brouillons à partir des chiffres réels de
 * l'événement actif, et peut EXÉCUTER une action ({@see CopilotActions}) une fois
 * celle-ci explicitement autorisée par l'utilisateur (endpoint execute, capacité
 * RBAC revérifiée). Chaque exécution est journalisée. Consomme l'agent « admin »
 * via {@see AskAdmin}.
 */
class AiAssistantController extends Controller
{
    public function page(Request $request): View
    {
        // Identité de l'assistant (nom + avatar) paramétrée dans Réglages - Agents IA.
        $agent = AiAgents::find('admin') ?? AiAgents::defaults()[1];
        $avatarUrl = AiAgents::avatarUrl($agent['avatar']);

        // Événements accessibles à l'utilisateur : l'assistant répond selon sa/ses
        // fonction(s) DANS le colloque sélectionné.
        $editionIds = $request->user()->accessibleEditionIds('view_dashboard');
        $events = Event::with('series')->whereIn('id', $editionIds)->latest('id')->get();
        $current = $events->firstWhere('status', 'open') ?? $events->first();

        return view('admin.ai-assistant', [
            'agentName' => $agent['name'] ?: 'Assistant',
            'agentAvatar' => $avatarUrl,
            'decisionLog' => array_slice((array) Settings::get('ai_decision_log', []), 0, 20),
            'events' => $events,
            'currentEditionId' => $current?->id,
        ]);
    }

    public function ask(Request $request, AskAdmin $ask): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1500'],
            'event_id' => ['nullable', 'integer'],
            'history' => ['array'],
            'history.*.role' => ['string'],
            'history.*.text' => ['string'],
        ]);

        $history = collect($data['history'] ?? [])
            ->map(fn ($t) => ['role' => (string) ($t['role'] ?? 'bot'), 'text' => (string) ($t['text'] ?? '')])
            ->filter(fn ($t) => $t['text'] !== '')
            ->values()
            ->all();

        $event = $this->activeEdition($request, $data['event_id'] ?? null);
        if (isset($data['event_id']) && $event?->id !== (int) $data['event_id']) {
            return response()->json(['answer' => 'Colloque sélectionné indisponible.', 'action' => null], 403);
        }

        try {
            $result = $ask->handle($history, trim($data['question']), $event, $request->user());

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['answer' => $e->getMessage(), 'proposal' => null]);
        }
    }

    /**
     * Exécute une action du copilote APRÈS autorisation explicite de l'utilisateur.
     * C'est le serveur qui exécute, sous l'autorité de l'utilisateur : la capacité
     * RBAC sur l'événement est revérifiée ici (garde-fou indépendant de l'IA), puis
     * l'exécution est journalisée.
     */
    public function execute(Request $request, CopilotActions $registry): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:64'],
            'event_id' => ['nullable', 'integer'],
            'params' => ['array'],
        ]);

        $event = $this->activeEdition($request, $data['event_id'] ?? null);
        $user = $request->user();

        // Ne jamais remplacer silencieusement un événement demandé mais
        // inaccessible par un autre lors de l'exécution d'une action.
        if (isset($data['event_id']) && $event?->id !== (int) $data['event_id']) {
            return response()->json(['message' => 'Colloque sélectionné indisponible.'], 403);
        }

        $action = $registry->resolveFor($user, $event, $data['key']);
        if ($action === null || $event === null) {
            return response()->json(['message' => 'Action non autorisée ou indisponible pour votre fonction.'], 403);
        }

        try {
            $message = $action->execute($data['params'] ?? [], $event, $user);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'L’action a échoué : '.$e->getMessage()], 500);
        }

        $entry = [
            'id' => (string) Str::uuid(),
            'when' => now()->format('d/m/Y - H:i'),
            'what' => Str::limit($action->label(), 120, ''),
            'label' => 'Exécutée',
            'pill' => 'ok',
        ];
        $log = (array) Settings::get('ai_decision_log', []);
        array_unshift($log, $entry);
        Settings::set('ai_decision_log', array_slice($log, 0, 50));

        return response()->json(['message' => $message, 'entry' => $entry]);
    }

    /**
     * Événement de contexte = le colloque SÉLECTIONNÉ (s'il est accessible à
     * l'utilisateur), sinon le colloque ouvert le plus récent parmi ses événements
     * accessibles. Garantit que l'assistant raisonne (et cadre la fonction) sur
     * le bon colloque.
     */
    private function activeEdition(Request $request, ?int $editionId = null): ?Event
    {
        $accessible = $request->user()->accessibleEditions('view_dashboard');

        if ($editionId !== null) {
            $picked = $accessible->firstWhere('id', $editionId);
            if ($picked !== null) {
                return $picked->loadMissing('series');
            }
        }

        $current = $accessible->firstWhere('status', 'open') ?? $accessible->first();

        return $current?->loadMissing('series');
    }
}
