<?php

namespace App\Meetings;

use App\Meetings\Contracts\MeetingProvider;
use App\Meetings\Providers\JitsiProvider;
use App\Models\EventSession;
use App\Payments\GatewayRegistry;

/**
 * Registre des fournisseurs de visio. Résout le fournisseur par clé et celui par
 * défaut (réglage `meetings.default`), avec repli sur Jitsi. Même esprit que
 * {@see GatewayRegistry}.
 */
class MeetingProviderRegistry
{
    /** @var array<int, MeetingProvider> */
    protected array $providers;

    public function __construct()
    {
        $this->providers = [
            new JitsiProvider,
        ];
    }

    /** @return array<int, MeetingProvider> */
    public function all(): array
    {
        return $this->providers;
    }

    public function get(string $key): ?MeetingProvider
    {
        foreach ($this->providers as $provider) {
            if ($provider->key() === $key) {
                return $provider;
            }
        }

        return null;
    }

    /** Fournisseur par défaut (réglage), repli sur Jitsi. */
    public function default(): MeetingProvider
    {
        $key = (string) config('meetings.default', 'jitsi');

        $provider = $this->get($key);

        return $provider && $provider->isAvailable() ? $provider : new JitsiProvider;
    }

    /** Fournisseur d'une session (son `meeting_provider`, sinon le défaut). */
    public function for(EventSession $session): MeetingProvider
    {
        $key = (string) ($session->meeting_provider ?: config('meetings.default', 'jitsi'));

        return $this->get($key) ?? $this->default();
    }
}
