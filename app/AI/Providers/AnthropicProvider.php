<?php

namespace App\AI\Providers;

use App\AI\Concerns\ParsesJsonResponse;
use App\AI\Concerns\RetriesTransientFailures;
use App\AI\Contracts\AiProvider;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Claude (Anthropic) — Messages API, multimodal (PDF via blocs « document »,
 * images via blocs « image »).
 */
class AnthropicProvider implements AiProvider
{
    use ParsesJsonResponse;
    use RetriesTransientFailures;

    public function key(): string
    {
        return 'claude';
    }

    public function label(): string
    {
        return 'Claude (Anthropic)';
    }

    public function isAvailable(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    public function supportsPdf(): bool
    {
        return true;
    }

    /** Modèle concret pour le palier demandé (économie / qualité), repli sur le défaut. */
    protected function model(?string $tier = null): string
    {
        $default = (string) (config('services.anthropic.model') ?: 'claude-sonnet-5');

        return match ($tier) {
            'fast' => (string) (config('services.anthropic.model_fast') ?: 'claude-haiku-4-5'),
            'deep' => (string) (config('services.anthropic.model_deep') ?: 'claude-opus-4-8'),
            default => $default,
        };
    }

    public function extract(string $instruction, array $files = [], ?string $tier = null): array
    {
        try {
            return $this->send($this->model($tier), $instruction, $files);
        } catch (RequestException $e) {
            // Palier de modèle refusé/indisponible (4xx) → repli transparent sur le modèle par défaut.
            $status = (int) $e->response->status();
            if ($tier !== null && $status >= 400 && $status < 500) {
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
        $content = [['type' => 'text', 'text' => $instruction]];

        foreach ($files as $f) {
            $content[] = str_contains($f['mime'], 'pdf')
                ? ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $f['data']]]
                : ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $f['mime'], 'data' => $f['data']]];
        }

        $res = $this->retryOnOverload(fn () => Http::withHeaders([
            'x-api-key' => (string) config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->acceptJson()->timeout(120)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 4096,
                // Température basse : réponses factuelles et précises (peu d'invention).
                'temperature' => 0.2,
                'messages' => [['role' => 'user', 'content' => $content]],
            ])->throw()->json());

        $text = collect($res['content'] ?? [])->firstWhere('type', 'text')['text'] ?? '';

        return $this->decodeJson((string) $text);
    }
}
