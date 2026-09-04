<?php

namespace App\AI\Providers;

use App\AI\Concerns\ParsesJsonResponse;
use App\AI\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;

/**
 * Socle des fournisseurs à API compatible OpenAI (chat/completions) : OpenAI et
 * Grok (xAI). Multimodal images uniquement (pas de PDF).
 */
abstract class OpenAiCompatibleProvider implements AiProvider
{
    use ParsesJsonResponse;

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

    protected function model(): string
    {
        return (string) (config("services.{$this->service()}.model") ?: $this->fallbackModel());
    }

    public function extract(string $instruction, array $files = []): array
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

        $res = Http::withToken((string) config("services.{$this->service()}.key"))
            ->acceptJson()->timeout(120)
            ->post($this->endpoint(), [
                'model' => $this->model(),
                // Température basse : réponses factuelles et précises (peu d'invention).
                'temperature' => 0.2,
                'messages' => [['role' => 'user', 'content' => $content]],
                'response_format' => ['type' => 'json_object'],
            ])->throw()->json();

        $text = $res['choices'][0]['message']['content'] ?? '';

        return $this->decodeJson((string) $text);
    }
}
