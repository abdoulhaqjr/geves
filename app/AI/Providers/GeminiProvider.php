<?php

namespace App\AI\Providers;

use App\AI\Concerns\ParsesJsonResponse;
use App\AI\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;

/**
 * Gemini (Google) — generateContent, multimodal (PDF + images via inline_data).
 */
class GeminiProvider implements AiProvider
{
    use ParsesJsonResponse;

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

    protected function model(): string
    {
        return (string) (config('services.gemini.model') ?: 'gemini-2.5-flash');
    }

    public function extract(string $instruction, array $files = []): array
    {
        $parts = [['text' => $instruction]];

        foreach ($files as $f) {
            $parts[] = ['inline_data' => ['mime_type' => $f['mime'], 'data' => $f['data']]];
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . $this->model() . ':generateContent';

        $res = Http::acceptJson()->timeout(120)
            ->withHeaders(['x-goog-api-key' => (string) config('services.gemini.key')])
            ->post($url, [
                'contents' => [['parts' => $parts]],
                'generationConfig' => ['responseMimeType' => 'application/json'],
            ])->throw()->json();

        $text = $res['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return $this->decodeJson((string) $text);
    }
}
