<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AskAdmin;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Copilote d'administration (chat), contrôleur + Blade + endpoint JSON. Assiste
 * le staff par des synthèses et brouillons à partir des chiffres réels de
 * l'édition active. Il propose ; la décision et l'exécution restent humaines.
 * Consomme l'agent « admin » via {@see AskAdmin}.
 */
class AiAssistantController extends Controller
{
    public function page(): View
    {
        return view('admin.ai-assistant');
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
            $answer = $ask->handle($history, trim($data['question']), $this->activeEdition());
        } catch (\Throwable $e) {
            $answer = $e->getMessage();
        }

        return response()->json(['answer' => $answer]);
    }

    private function activeEdition(): ?Edition
    {
        return Edition::with('colloque')->where('status', 'open')->latest('id')->first();
    }
}
