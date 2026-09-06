<?php

namespace App\Support\Plagiarism;

use App\Support\Settings;
use Illuminate\Support\Facades\Http;

/**
 * Détecteur de présence web gratuit basé sur Google Programmable Search (Custom
 * Search JSON API) : 100 requêtes/jour offertes, sans carte bancaire. Ce n'est
 * pas un « % de plagiat » exhaustif — on extrait quelques phrases distinctives
 * du résumé et on cherche chacune en correspondance EXACTE ("…") sur le web ;
 * le score reflète la part de phrases retrouvées verbatim, avec les URL sources.
 *
 * Contrat Google (stable) : GET https://www.googleapis.com/customsearch/v1
 *   ?key=API_KEY&cx=SEARCH_ENGINE_ID&q="phrase"&num=3
 *   Réponse : { items: [ { link, title, snippet } ], searchInformation: {…} }
 */
class GoogleSearchProvider implements WebPlagiarismProvider
{
    private const ENDPOINT = 'https://www.googleapis.com/customsearch/v1';

    /** Nb de phrases interrogées par vérification (préserve le quota 100/jour). */
    private const MAX_SENTENCES = 5;

    /** Longueur max d'une phrase citée (mots) pour la recherche exacte. */
    private const MAX_WORDS_PER_QUERY = 32;

    public function key(): string
    {
        return 'google';
    }

    public function label(): string
    {
        return 'Google Recherche';
    }

    public function isConfigured(): bool
    {
        return trim((string) Settings::get('plagiarism.google_key', '')) !== ''
            && trim((string) Settings::get('plagiarism.google_cx', '')) !== '';
    }

    public function check(string $text): array
    {
        $apiKey = trim((string) Settings::get('plagiarism.google_key', ''));
        $cx = trim((string) Settings::get('plagiarism.google_cx', ''));

        $sentences = $this->distinctiveSentences($text);
        $checked = count($sentences);

        $counts = [];
        $hits = 0;
        foreach ($sentences as $sentence) {
            $response = Http::timeout(30)->get(self::ENDPOINT, [
                'key' => $apiKey,
                'cx' => $cx,
                'q' => '"'.$sentence.'"',
                'num' => 3,
            ])->throw();

            $items = (array) ($response->json('items') ?? []);
            if ($items !== []) {
                $hits++;
                foreach (array_slice($items, 0, 2) as $item) {
                    $link = (string) (is_array($item) ? ($item['link'] ?? '') : '');
                    if ($link !== '') {
                        $counts[$link] = ($counts[$link] ?? 0) + 1;
                    }
                }
            }
        }

        $plag = $checked > 0 ? round($hits / $checked * 100, 1) : 0.0;

        $sources = collect($counts)
            ->sortDesc()
            ->take(8)
            ->map(fn (int $count, string $link) => [
                'link' => $link,
                'percent' => $checked > 0 ? round($count / $checked * 100, 1) : 0.0,
            ])
            ->values()
            ->all();

        return [
            'provider' => $this->key(),
            'plagPercent' => $plag,
            'uniquePercent' => round(100 - $plag, 1),
            'paraphrasePercent' => 0.0,
            'sources' => $sources,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Phrases les plus distinctives du résumé (les plus longues d'abord),
     * plafonnées en nombre et en longueur pour préserver le quota.
     *
     * @return list<string>
     */
    private function distinctiveSentences(string $text): array
    {
        $text = trim(strip_tags($text));
        $parts = preg_split('/(?<=[.!?])\s+/u', $text) ?: [];

        $byLength = [];
        foreach ($parts as $part) {
            $words = array_values(array_filter(preg_split('/\s+/u', trim($part)) ?: []));
            if (count($words) >= 8) {
                $phrase = implode(' ', array_slice($words, 0, self::MAX_WORDS_PER_QUERY));
                $byLength[$phrase] = count($words);
            }
        }

        arsort($byLength);

        return array_slice(array_keys($byLength), 0, self::MAX_SENTENCES);
    }
}
