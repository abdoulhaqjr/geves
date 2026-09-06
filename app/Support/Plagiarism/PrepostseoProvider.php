<?php

namespace App\Support\Plagiarism;

use App\Support\Settings;
use Illuminate\Support\Facades\Http;

/**
 * Fournisseur anti-plagiat Prepostseo (freemium). Contrat de leur API :
 *   POST https://www.prepostseo.com/apis/checkPlag  (form) : key, data (≤ 2 500 mots)
 *   Réponse JSON : plagPercent, uniquePercent, paraphrasePercent,
 *                  sources[] { link, count, percent }, totalQueries, isQueriesFinished
 * Le parsing est défensif (champs tolérés absents) ; la clé vit dans les
 * réglages (DB), jamais en dur ni dans .env.
 */
class PrepostseoProvider implements WebPlagiarismProvider
{
    private const ENDPOINT = 'https://www.prepostseo.com/apis/checkPlag';

    private const MAX_WORDS = 2500;

    public function key(): string
    {
        return 'prepostseo';
    }

    public function label(): string
    {
        return 'Prepostseo';
    }

    public function isConfigured(): bool
    {
        return trim((string) Settings::get('plagiarism.prepostseo_key', '')) !== '';
    }

    public function check(string $text): array
    {
        $apiKey = trim((string) Settings::get('plagiarism.prepostseo_key', ''));

        // Respecte la limite de 2 500 mots de l'API.
        $words = preg_split('/\s+/u', trim(strip_tags($text))) ?: [];
        $data = implode(' ', array_slice($words, 0, self::MAX_WORDS));

        $response = Http::asForm()->timeout(60)->post(self::ENDPOINT, [
            'key' => $apiKey,
            'data' => $data,
        ])->throw();

        $json = (array) $response->json();

        $sources = collect($json['sources'] ?? [])
            ->map(fn ($s) => [
                'link' => (string) (is_array($s) ? ($s['link'] ?? '') : ''),
                'percent' => (float) (is_array($s) ? ($s['percent'] ?? 0) : 0),
            ])
            ->filter(fn ($s) => $s['link'] !== '')
            ->sortByDesc('percent')
            ->take(8)
            ->values()
            ->all();

        return [
            'provider' => $this->key(),
            'plagPercent' => (float) ($json['plagPercent'] ?? 0),
            'uniquePercent' => (float) ($json['uniquePercent'] ?? 0),
            'paraphrasePercent' => (float) ($json['paraphrasePercent'] ?? 0),
            'sources' => $sources,
            'checked_at' => now()->toIso8601String(),
        ];
    }
}
