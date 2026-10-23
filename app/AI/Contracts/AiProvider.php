<?php

namespace App\AI\Contracts;

/**
 * Fournisseur d'IA multimodal. Extrait des données structurées (JSON) à partir
 * d'une instruction et de pièces jointes (PDF / images).
 */
interface AiProvider
{
    /** Jeton du fournisseur (aligné sur services.ai.default : claude|openai|gemini|grok|openrouter). */
    public function key(): string;

    public function label(): string;

    /** Vrai si une clé API est configurée. */
    public function isAvailable(): bool;

    /** Le fournisseur sait-il lire des PDF ? */
    public function supportsPdf(): bool;

    /**
     * Envoie l'instruction + les fichiers et renvoie la réponse JSON décodée.
     *
     * @param  array<int, array{mime: string, data: string}>  $files  data = contenu base64
     * @param  'fast'|'deep'|null  $tier  palier de modèle souhaité (économie / qualité), null = défaut
     * @return array<string, mixed>
     */
    public function extract(string $instruction, array $files = [], ?string $tier = null): array;
}
