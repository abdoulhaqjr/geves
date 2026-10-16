<?php

namespace App\AI\Providers;

use App\AI\Concerns\ParsesJsonResponse;
use App\AI\Concerns\RetriesTransientFailures;
use App\AI\Contracts\AiProvider;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Gemini (Google) - generateContent, multimodal (PDF + images via inline_data).
 */
class GeminiProvider implements AiProvider
{
    use ParsesJsonResponse;
    use RetriesTransientFailures;

    public function key(): string
    {
        return 'gemini';
    }

    public function label(): string
    {
        return 'Gemini (Google)';
    }

    public function isAvailable(): bool
    {
        return filled(config('services.gemini.key'));
    }

    public function supportsPdf(): bool
    {
        return true;
    }

    /**
     * Modèle concret pour le palier demandé (économie / qualité), repli sur le
     * défaut. On utilise les alias « -latest » de Google : ils pointent toujours
     * vers le modèle GA courant, ce qui évite les 404 quand une version est
     * retirée (les identifiants figés comme gemini-2.5-* finissent par disparaître).
     */
    protected function model(?string $tier = null): string
    {
        $default = (string) (config('services.gemini.model') ?: 'gemini-flash-latest');

        return match ($tier) {
            'fast' => (string) (config('services.gemini.model_fast') ?: 'gemini-flash-lite-latest'),
            'deep' => (string) (config('services.gemini.model_deep') ?: $default),
            default => $default,
        };
    }

    public function extract(string $instruction, array $files = [], ?string $tier = null): array
    {
        try {
            return $this->send($this->model($tier), $instruction, $files);
        } catch (RequestException $e) {
            // Seul un modèle de palier distinct et indisponible justifie ce repli.
            // Un 401 signifie que la clé est invalide : refaire la même requête
            // avec le même compte retarde inutilement le fournisseur suivant.
            $status = (int) $e->response->status();
            if ($tier !== null && in_array($status, [400, 403, 404, 422], true) && $this->model($tier) !== $this->model()) {
                return $this->send($this->model(), $instruction, $files);
            }
            throw $e;
        }
    }

    /**
     * @param  array<int, array{mime: string, data: string}>  $files
     * @return array<string, mixed>
     */
    protected function send(string $model, string $instruction, array $files): array
    {
        $parts = [['text' => $instruction]];

        foreach ($files as $f) {
            $parts[] = ['inline_data' => ['mime_type' => $f['mime'], 'data' => $f['data']]];
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent';

        $res = $this->retryOnOverload(fn () => Http::acceptJson()->timeout(120)
            ->withHeaders(['x-goog-api-key' => (string) config('services.gemini.key')])
            ->post($url, [
                'contents' => [['parts' => $parts]],
                // Température basse : réponses factuelles et précises (peu d'invention).
                'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.2],
            ])->throw()->json());

        $text = $res['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return $this->decodeJson((string) $text);
    }
}
