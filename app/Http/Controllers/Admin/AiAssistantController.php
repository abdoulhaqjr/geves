<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AskAdmin;
use App\AI\Copilot\CopilotActions;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Support\AiAgents;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Copilote d'administration (chat), contrôleur + Blade + endpoints JSON. Assiste
 * le staff par des synthèses et brouillons à partir des chiffres réels de
 * l'édition active, et peut EXÉCUTER une action ({@see CopilotActions}) une fois
 * celle-ci explicitement autorisée par l'utilisateur (endpoint execute, capacité
 * RBAC revérifiée). Chaque décision/exécution est journalisée. Consomme l'agent
 * « admin » via {@see AskAdmin}.
 */
class AiAssistantController extends Controller
{
    /** Libellé + pastille par décision (fidèle au mockup admin-ai-assistant). */
    private const DECISIONS = [
        'approve' => ['Approuvé par admin', 'ok'],
        'edit' => ['À modifier', 'info'],
        'reject' => ['Refusé par admin', 'warn'],
    ];

    public function page(Request $request): View
    {
        // Identité de l'assistant (nom + avatar) paramétrée dans Réglages · Agents IA.
        $agent = AiAgents::find('admin') ?? AiAgents::defaults()[1];
        $avatarUrl = filled($agent['avatar']) ? '/storage/'.ltrim((string) $agent['avatar'], '/') : null;

        // Colloques accessibles à l'utilisateur : l'assistant répond selon sa/ses
        // fonction(s) DANS le colloque sélectionné.
        $editionIds = $request->user()->accessibleEditionIds('view_dashboard');
        $editions = Edition::with('colloque')->whereIn('id', $editionIds)->latest('id')->get();
        $current = $editions->firstWhere('status', 'open') ?? $editions->first();

        return view('admin.ai-assistant', [
            'agentName' => $agent['name'] ?: 'Assistant',
            'agentAvatar' => $avatarUrl,
            'decisionLog' => array_slice((array) Settings::get('ai_decision_log', []), 0, 20),
            'editions' => $editions,
            'currentEditionId' => $current?->id,
        ]);
    }

    public function ask(Request $request, AskAdmin $ask): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1500'],
            'edition_id' => ['nullable', 'integer'],
            'history' => ['array'],
            'history.*.role' => ['string'],
            'history.*.text' => ['string'],
        ]);

        $history = collect($data['history'] ?? [])
            ->map(fn ($t) => ['role' => (string) ($t['role'] ?? 'bot'), 'text' => (string) ($t['text'] ?? '')])
            ->filter(fn ($t) => $t['text'] !== '')
            ->values()
            ->all();

        try {
            $result = $ask->handle($history, trim($data['question']), $this->activeEdition($request, $data['edition_id'] ?? null), $request->user());

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['answer' => $e->getMessage(), 'proposal' => null]);
        }
    }

    /**
     * Exécute une action du copilote APRÈS autorisation explicite de l'utilisateur.
     * C'est le serveur qui exécute, sous l'autorité de l'utilisateur : la capacité
     * RBAC sur l'édition est revérifiée ici (garde-fou indépendant de l'IA), puis
     * l'exécution est journalisée.
     */
    public function execute(Request $request, CopilotActions $registry): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:64'],
            'edition_id' => ['nullable', 'integer'],
            'params' => ['array'],
        ]);

        $edition = $this->activeEdition($request, $data['edition_id'] ?? null);
        $user = $request->user();

        $action = $registry->resolveFor($user, $edition, $data['key']);
        if ($action === null || $edition === null) {
            return response()->json(['message' => 'Action non autorisée ou indisponible pour votre fonction.'], 403);
        }

        try {
            $message = $action->execute($data['params'] ?? [], $edition, $user);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'L’action a échoué : '.$e->getMessage()], 500);
        }

        $entry = [
            'id' => (string) Str::uuid(),
            'when' => now()->format('d/m/Y · H:i'),
            'what' => Str::limit($action->label(), 120, ''),
            'label' => 'Exécutée',
            'pill' => 'ok',
        ];
        $log = (array) Settings::get('ai_decision_log', []);
        array_unshift($log, $entry);
        Settings::set('ai_decision_log', array_slice($log, 0, 50));

        return response()->json(['message' => $message, 'entry' => $entry]);
    }

    /** Journalise une décision humaine sur une proposition (approuver/modifier/refuser). */
    public function decide(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'decision' => ['required', 'in:approve,edit,reject'],
        ]);

        [$label, $pill] = self::DECISIONS[$data['decision']];

        $entry = [
            'id' => (string) Str::uuid(),
            'when' => now()->format('d/m/Y · H:i'),
            'what' => Str::limit(trim($data['title']), 120, ''),
            'label' => $label,
            'pill' => $pill,
        ];

        $log = (array) Settings::get('ai_decision_log', []);
        array_unshift($log, $entry);
        Settings::set('ai_decision_log', array_slice($log, 0, 50));

        return response()->json(['entry' => $entry]);
    }

    /**
     * Édition de contexte = le colloque SÉLECTIONNÉ (s'il est accessible à
     * l'utilisateur), sinon le colloque ouvert le plus récent parmi ses éditions
     * accessibles. Garantit que l'assistant raisonne (et cadre la fonction) sur
     * le bon colloque.
     */
    private function activeEdition(Request $request, ?int $editionId = null): ?Edition
    {
        $accessible = $request->user()->accessibleEditions('view_dashboard');

        if ($editionId !== null) {
            $picked = $accessible->firstWhere('id', $editionId);
            if ($picked !== null) {
                return $picked->loadMissing('colloque');
            }
        }

        $current = $accessible->firstWhere('status', 'open') ?? $accessible->first();

        return $current?->loadMissing('colloque');
    }
}
