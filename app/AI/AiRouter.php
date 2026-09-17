<?php

namespace App\AI;

use Illuminate\Support\Str;

/**
 * Choix « intelligent » du palier de modèle selon la tâche. L'idée : dépenser
 * moins de jetons sur les demandes simples (palier « fast »/économie) et
 * réserver le palier « deep »/qualité aux tâches lourdes ou complexes, sans
 * perte de pertinence. Renvoie un palier ('fast'|'deep') ou null (modèle par
 * défaut du fournisseur).
 *
 * Chaque fournisseur fait correspondre le palier à un modèle concret et se
 * replie sur son modèle par défaut si le palier n'est pas disponible.
 */
class AiRouter
{
    /** Mots-clés révélateurs d'une demande analytique / rédactionnelle exigeante. */
    private const COMPLEX_HINTS = [
        'analyse', 'analyser', 'compare', 'comparer', 'rédige', 'rédiger', 'rédaction',
        'synthèse', 'synthétise', 'résume tout', 'explique en détail', 'pourquoi',
        'stratég', 'recommand', 'plan d', 'planifie', 'évalue', 'justifie', 'argument',
    ];

    /**
     * @param  'chat_public'|'chat_admin'|'report'|'document'|'claim_analysis'|'extraction'|string  $useCase
     * @param  string  $prompt  texte de la demande (pour jauger la complexité)
     * @param  bool  $hasFiles  des pièces jointes accompagnent la demande
     * @param  int  $historyTurns  nombre de tours de conversation déjà échangés
     * @return 'fast'|'deep'|null
     */
    public function tier(string $useCase, string $prompt = '', bool $hasFiles = false, int $historyTurns = 0): ?string
    {
        // Tâches lourdes par nature (documents, rapports, analyses, extraction PDF) :
        // qualité maximale. Toute demande avec pièces jointes suit la même logique.
        if ($hasFiles || in_array($useCase, ['report', 'document', 'claim_analysis', 'extraction'], true)) {
            return 'deep';
        }

        // Conversations : on jauge la complexité pour arbitrer économie / qualité.
        if (in_array($useCase, ['chat_public', 'chat_admin'], true)) {
            $length = Str::length(trim($prompt));

            if ($this->looksComplex($prompt) || $length > 320 || $historyTurns >= 6) {
                return 'deep';
            }

            if ($length <= 160 && $historyTurns <= 2) {
                return 'fast';
            }
        }

        return null;
    }

    private function looksComplex(string $prompt): bool
    {
        $needle = Str::lower($prompt);

        foreach (self::COMPLEX_HINTS as $hint) {
            if (str_contains($needle, $hint)) {
                return true;
            }
        }

        // Plusieurs questions enchaînées ⇒ demande plus riche.
        return substr_count($needle, '?') >= 2;
    }
}
