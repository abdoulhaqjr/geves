<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Client OAuth ORCID (3-legged, scope « /authenticate ») — API publique
 * gratuite. Sert à prouver l'appartenance d'un identifiant ORCID : l'auteur se
 * connecte chez ORCID, qui nous renvoie son iD authentifié. Environnement
 * sandbox ou production, identifiants dans les réglages (jamais en dur).
 */
class OrcidClient
{
    /** Base ORCID selon l'environnement configuré. */
    public function base(): string
    {
        return Settings::get('orcid.environment', 'sandbox') === 'production'
            ? 'https://orcid.org'
            : 'https://sandbox.orcid.org';
    }

    public function isConfigured(): bool
    {
        return trim((string) Settings::get('orcid.client_id', '')) !== ''
            && trim((string) Settings::get('orcid.client_secret', '')) !== '';
    }

    /** URL d'autorisation vers laquelle rediriger l'utilisateur. */
    public function authorizeUrl(string $redirectUri, string $state): string
    {
        return $this->base().'/oauth/authorize?'.http_build_query([
            'client_id' => trim((string) Settings::get('orcid.client_id', '')),
            'response_type' => 'code',
            'scope' => '/authenticate',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);
    }

    /**
     * Échange le code d'autorisation contre l'iD ORCID authentifié.
     *
     * @return array{orcid: string, name: string}
     */
    public function exchange(string $code, string $redirectUri): array
    {
        $response = Http::asForm()->acceptJson()->timeout(30)
            ->post($this->base().'/oauth/token', [
                'client_id' => trim((string) Settings::get('orcid.client_id', '')),
                'client_secret' => trim((string) Settings::get('orcid.client_secret', '')),
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ])->throw();

        $json = (array) $response->json();

        return [
            'orcid' => (string) ($json['orcid'] ?? ''),
            'name' => (string) ($json['name'] ?? ''),
        ];
    }
}
