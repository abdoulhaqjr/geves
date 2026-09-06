<?php

namespace App\Support\Plagiarism;

use App\Support\SimilarityChecker;

/**
 * Contrat d'un fournisseur de vérification anti-plagiat web (couche 2, externe).
 * Complète la similarité interne gratuite ({@see SimilarityChecker}).
 * Implémentations branchables (Prepostseo aujourd'hui ; Copyleaks… plus tard).
 */
interface WebPlagiarismProvider
{
    /** Clé technique (ex. « prepostseo »). */
    public function key(): string;

    /** Libellé lisible. */
    public function label(): string;

    /** Vrai si le fournisseur dispose des identifiants nécessaires. */
    public function isConfigured(): bool;

    /**
     * Vérifie un texte et renvoie un résultat normalisé (lève une exception en cas
     * d'échec réseau/API).
     *
     * @return array{provider:string, plagPercent:float, uniquePercent:float, paraphrasePercent:float, sources:list<array{link:string, percent:float}>, checked_at:string}
     */
    public function check(string $text): array;
}
