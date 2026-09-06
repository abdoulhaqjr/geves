<?php

namespace App\Support;

/**
 * Détection de similarité interne (gratuite, sans I/O, sans base externe) :
 * compare un texte à un corpus de textes et renvoie les plus proches. Vise le
 * cas fréquent en colloque — résumés dupliqués, recyclés d'une édition à
 * l'autre, copiés entre soumissions — sans envoyer aucune donnée à l'extérieur.
 *
 * Méthode : sac de mots pondéré (TF), similarité cosinus sur unigrammes +
 * bigrammes, après normalisation (minuscules, sans ponctuation, mots-outils et
 * jetons trop courts retirés). Déterministe et testable.
 */
class SimilarityChecker
{
    /** Mots-outils FR/EN écartés (bruit fréquent, non discriminant). */
    private const STOPWORDS = [
        'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'au', 'aux', 'et', 'ou', 'où', 'à', 'en', 'dans',
        'pour', 'par', 'sur', 'avec', 'sans', 'sous', 'ce', 'cet', 'cette', 'ces', 'son', 'sa', 'ses', 'leur',
        'leurs', 'que', 'qui', 'quoi', 'dont', 'est', 'sont', 'être', 'avoir', 'plus', 'moins', 'aussi', 'entre',
        'the', 'a', 'an', 'of', 'and', 'or', 'to', 'in', 'on', 'for', 'with', 'is', 'are', 'be', 'this', 'that',
        'these', 'those', 'as', 'by', 'at', 'from', 'we', 'our', 'their', 'its',
    ];

    /**
     * Textes du corpus les plus proches de `$text`, triés par score décroissant.
     *
     * @param  list<array{id:int, text:string}>  $corpus
     * @return list<array{id:int, score:float}> score dans [0,1]
     */
    public function against(string $text, array $corpus, float $threshold = 0.30, int $limit = 5): array
    {
        $ref = $this->vector($text);
        if ($ref === []) {
            return [];
        }
        $refNorm = $this->norm($ref);

        $matches = [];
        foreach ($corpus as $item) {
            $vec = $this->vector($item['text']);
            if ($vec === []) {
                continue;
            }
            $score = $this->cosine($ref, $refNorm, $vec);
            if ($score >= $threshold) {
                $matches[] = ['id' => $item['id'], 'score' => round($score, 4)];
            }
        }

        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($matches, 0, $limit);
    }

    /**
     * Score de similarité entre deux textes (0 à 1).
     */
    public function score(string $a, string $b): float
    {
        $va = $this->vector($a);
        $vb = $this->vector($b);
        if ($va === [] || $vb === []) {
            return 0.0;
        }

        return round($this->cosine($va, $this->norm($va), $vb), 4);
    }

    /**
     * Vecteur de fréquences (unigrammes + bigrammes) d'un texte normalisé.
     *
     * @return array<string, int>
     */
    private function vector(string $text): array
    {
        $tokens = $this->tokens($text);
        $vec = [];
        foreach ($tokens as $t) {
            $vec[$t] = ($vec[$t] ?? 0) + 1;
        }
        for ($i = 0, $n = count($tokens) - 1; $i < $n; $i++) {
            $bigram = $tokens[$i].' '.$tokens[$i + 1];
            $vec[$bigram] = ($vec[$bigram] ?? 0) + 1;
        }

        return $vec;
    }

    /**
     * Jetons significatifs d'un texte (minuscule, sans ponctuation, sans
     * mots-outils ni jetons < 3 caractères).
     *
     * @return list<string>
     */
    private function tokens(string $text): array
    {
        $text = mb_strtolower(strip_tags($text));
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '';
        $raw = preg_split('/\s+/u', trim($text)) ?: [];

        $stop = array_flip(self::STOPWORDS);
        $out = [];
        foreach ($raw as $t) {
            if (mb_strlen($t) >= 3 && ! isset($stop[$t])) {
                $out[] = $t;
            }
        }

        return $out;
    }

    /** Norme euclidienne d'un vecteur de fréquences. */
    private function norm(array $vec): float
    {
        $sum = 0.0;
        foreach ($vec as $v) {
            $sum += $v * $v;
        }

        return sqrt($sum);
    }

    /**
     * Cosinus entre le vecteur de référence (+ sa norme pré-calculée) et un autre.
     *
     * @param  array<string, int>  $ref
     * @param  array<string, int>  $vec
     */
    private function cosine(array $ref, float $refNorm, array $vec): float
    {
        $dot = 0.0;
        foreach ($ref as $term => $count) {
            if (isset($vec[$term])) {
                $dot += $count * $vec[$term];
            }
        }
        $den = $refNorm * $this->norm($vec);

        return $den > 0.0 ? $dot / $den : 0.0;
    }
}
