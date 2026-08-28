<?php

namespace App\Http\Controllers\Public;

use App\Actions\AskGesco;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Assistante Gesco (page dédiée + endpoint JSON), sans Livewire. La page rend
 * une coquille Blade ; la conversation est pilotée côté client (Alpine) et
 * chaque tour appelle {@see ask()} en JSON. Gesco informe et oriente : elle ne
 * réalise aucune action à la place de l'utilisateur.
 */
class GescoController extends Controller
{
    /** Page dédiée /assistante. */
    public function page(): View
    {
        return view('public.gesco');
    }

    /** Endpoint conversationnel : reçoit l'historique + la question, renvoie la réponse. */
    public function ask(Request $request, AskGesco $ask): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'history' => ['array'],
            'history.*.role' => ['string'],
            'history.*.text' => ['string'],
        ]);

        $key = 'gesco:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json([
                'answer' => 'Vous avez envoyé beaucoup de messages. Merci de réessayer un peu plus tard.',
            ]);
        }
        RateLimiter::hit($key, 3600);

        // Normalise l'historique reçu au format attendu par l'action.
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
