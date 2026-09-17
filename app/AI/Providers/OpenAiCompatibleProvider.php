<?php

namespace App\AI\Providers;

use App\AI\Concerns\ParsesJsonResponse;
use App\AI\Concerns\RetriesTransientFailures;
use App\AI\Contracts\AiProvider;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Socle des fournisseurs à API compatible OpenAI (chat/completions) : OpenAI et
 * Grok (xAI). Multimodal images uniquement (pas de PDF).
 */
abstract class OpenAiCompatibleProvider implements AiProvider
{
    use ParsesJsonResponse;
    use RetriesTransientFailures;

    abstract public function key(): string;

    abstract public function label(): string;

    /** Nom du service dans config/services.php (openai | grok). */
    abstract protected function service(): string;

    abstract protected function endpoint(): string;

    abstract protected function fallbackModel(): string;

    public function isAvailable(): bool
    {
        return filled(config("services.{$this->service()}.key"));
    }

    public function supportsPdf(): bool
    {
        return false;
    }

    /** Modèle concret pour le palier demandé (économie / qualité), repli sur le défaut. */
    protected function model(?string $tier = null): string
    {
        $default = (string) (config("services.{$this->service()}.model") ?: $this->fallbackModel());

        if ($tier === null) {
            return $default;
        }

        // Un modèle de palier n'est utilisé que s'il est explicitement configuré ;
        // sinon on conserve le modèle par défaut (pas de nom de modèle deviné).
        return (string) (config("services.{$this->service()}.model_{$tier}") ?: $default);
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
            if (str_contains($f['mime'], 'pdf')) {
                continue; // non pris en charge par ce socle
            }
            $content[] = [
                'type' => 'image_url',
                'image_url' => ['url' => 'data:'.$f['mime'].';base64,'.$f['data']],
            ];
        }

        $res = $this->retryOnOverload(fn () => Http::withToken((string) config("services.{$this->service()}.key"))
            ->acceptJson()->timeout(120)
            ->post($this->endpoint(), [
                'model' => $model,
                // Température basse : réponses factuelles et précises (peu d'invention).
                'temperature' => 0.2,
                'messages' => [['role' => 'user', 'content' => $content]],
                'response_format' => ['type' => 'json_object'],
            ])->throw()->json());

        $text = $res['choices'][0]['message']['content'] ?? '';

        return $this->decodeJson((string) $text);
    }
}
