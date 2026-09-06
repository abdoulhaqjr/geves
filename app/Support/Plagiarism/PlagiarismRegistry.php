<?php

namespace App\Support\Plagiarism;

use App\Support\Settings;

/**
 * Registre des fournisseurs anti-plagiat web (couche 2). Renvoie le fournisseur
 * actif selon les réglages, ou null si « aucun » ou non configuré (auquel cas
 * seule la similarité interne gratuite s'applique).
 */
class PlagiarismRegistry
{
    /** @return array<string, WebPlagiarismProvider> */
    public function providers(): array
    {
        return [
            'prepostseo' => new PrepostseoProvider,
            'google' => new GoogleSearchProvider,
        ];
    }

    /** Fournisseur actif configuré, ou null. */
    public function active(): ?WebPlagiarismProvider
    {
        $provider = $this->providers()[(string) Settings::get('plagiarism.provider', 'none')] ?? null;

        return $provider && $provider->isConfigured() ? $provider : null;
    }
}
