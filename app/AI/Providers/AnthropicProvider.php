<?php

namespace App\AI\Providers;

use App\AI\Concerns\ParsesJsonResponse;
use App\AI\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;

/**
 * Claude (Anthropic) — Messages API, multimodal (PDF via blocs « document »,
 * images via blocs « image »).
 */
class AnthropicProvider implements AiProvider
{
    use ParsesJsonResponse;

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

    protected function model(): string
    {
        return (string) (config('services.anthropic.model') ?: 'claude-sonnet-5');
    }

    public function extract(string $instruction, array $files = []): array
    {
        $content = [['type' => 'text', 'text' => $instruction]];

        foreach ($files as $f) {
            $content[] = str_contains($f['mime'], 'pdf')
                ? ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $f['data']]]
                : ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $f['mime'], 'data' => $f['data']]];
        }

        $res = Http::withHeaders([
            'x-api-key' => (string) config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->acceptJson()->timeout(120)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model(),
                'max_tokens' => 4096,
                'messages' => [['role' => 'user', 'content' => $content]],
            ])->throw()->json();

        $text = collect($res['content'] ?? [])->firstWhere('type', 'text')['text'] ?? '';

        return $this->decodeJson((string) $text);
    }
}
