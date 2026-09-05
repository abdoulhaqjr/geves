<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AskAdmin;
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
 * l'édition active. Il propose ; la décision et l'exécution restent humaines —
 * chaque décision (approuver/modifier/refuser) est journalisée. Consomme l'agent
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

    public function page(): View
    {
        // Identité de l'assistant (nom + avatar) paramétrée dans Réglages · Agents IA.
        $agent = AiAgents::find('admin') ?? AiAgents::defaults()[1];
        $avatarUrl = filled($agent['avatar']) ? '/storage/'.ltrim((string) $agent['avatar'], '/') : null;

        return view('admin.ai-assistant', [
            'agentName' => $agent['name'] ?: 'Assistant',
            'agentAvatar' => $avatarUrl,
            'decisionLog' => array_slice((array) Settings::get('ai_decision_log', []), 0, 20),
        ]);
    }

    public function ask(Request $request, AskAdmin $ask): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1500'],
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
            $result = $ask->handle($history, trim($data['question']), $this->activeEdition());

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['answer' => $e->getMessage(), 'proposal' => null]);
        }
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

    private function activeEdition(): ?Edition
    {
        return Edition::with('colloque')->where('status', 'open')->latest('id')->first();
    }
}
