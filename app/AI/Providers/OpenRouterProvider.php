<?php

namespace App\AI\Providers;

use App\AI\Exceptions\AiUnavailableException;
use App\AI\OfficeTextExtractor;
use Illuminate\Support\Facades\Http;

/** Gemma via OpenRouter, disponible comme repli pour tous les services IA. */
class OpenRouterProvider extends OpenAiCompatibleProvider
{
    public function key(): string
    {
        return 'openrouter';
    }

    public function label(): string
    {
        return 'OpenRouter (Gemma)';
    }

    protected function service(): string
    {
        return 'openrouter';
    }

    protected function endpoint(): string
    {
        return 'https://openrouter.ai/api/v1/chat/completions';
    }

    protected function fallbackModel(): string
    {
        return 'google/gemma-4-31b-it:free';
    }

    public function supportsPdf(): bool
    {
        return true;
    }

    /** @param array<int, array{mime: string, data: string}> $files */
    protected function send(string $model, string $instruction, array $files): array
    {
        $content = [['type' => 'text', 'text' => $instruction]];
        $hasPdf = false;

        foreach ($files as $index => $file) {
            $mime = (string) $file['mime'];
            if (str_contains($mime, 'pdf')) {
                $hasPdf = true;
                $content[] = [
                    'type' => 'file',
                    'file' => [
                        'filename' => 'document-'.($index + 1).'.pdf',
                        'file_data' => 'data:application/pdf;base64,'.$file['data'],
                    ],
                ];
            } elseif (str_starts_with($mime, 'image/')) {
                $content[] = [
                    'type' => 'image_url',
                    'image_url' => ['url' => 'data:'.$mime.';base64,'.$file['data']],
                ];
            } elseif (str_starts_with($mime, 'text/') || $mime === 'application/json') {
                $text = base64_decode($file['data'], true);
                if ($text === false) {
                    throw new AiUnavailableException('OpenRouter n’a pas pu lire le fichier texte.');
                }
                $content[] = ['type' => 'text', 'text' => $text];
            } elseif (str_contains($mime, 'wordprocessingml') || str_contains($mime, 'spreadsheetml')) {
                $bytes = base64_decode($file['data'], true);
                if ($bytes === false) {
                    throw new AiUnavailableException('OpenRouter n’a pas pu lire le fichier Office.');
                }
                $content[] = ['type' => 'text', 'text' => OfficeTextExtractor::extract($mime, $bytes)];
            } else {
                throw new AiUnavailableException('OpenRouter ne peut pas lire ce format de fichier.');
            }
        }

        $payload = [
            'model' => $model,
            'temperature' => 0.2,
            'messages' => [['role' => 'user', 'content' => $content]],
            'response_format' => ['type' => 'json_object'],
        ];
        if ($hasPdf) {
            // Sans précision, OpenRouter choisirait un moteur OCR payant.
            $payload['plugins'] = [['id' => 'file-parser', 'pdf' => ['engine' => 'cloudflare-ai']]];
        }

        // Les échecs d'un modèle gratuit comptent dans son quota journalier.
        // Un délai court laisse le routeur essayer les autres fournisseurs avant
        // la limite d'exécution de la requête PHP (30 secondes sur le serveur).
        $response = Http::withToken((string) config('services.openrouter.key'))
            ->acceptJson()->connectTimeout(3)->timeout(10)
            ->post($this->endpoint(), $payload)->throw()->json();
        $result = $this->decodeJson((string) ($response['choices'][0]['message']['content'] ?? ''));
        if ($result === []) {
            throw new AiUnavailableException('OpenRouter n’a pas renvoyé de réponse JSON exploitable.');
        }

        return $result;
    }
}
