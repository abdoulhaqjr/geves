<?php

namespace App\Meetings;

use App\Meetings\Contracts\Meetable;
use App\Meetings\Contracts\MeetingProvider;
use App\Meetings\Providers\CustomProvider;
use App\Meetings\Providers\JitsiProvider;
use App\Meetings\Providers\LiveKitProvider;
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
            new LiveKitProvider,
            new JitsiProvider,
            new CustomProvider,
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

    /** Fournisseur par défaut (réglage), repli sur Jitsi (toujours disponible). */
    public function default(): MeetingProvider
    {
        $key = (string) config('meetings.default', 'livekit');

        $provider = $this->get($key);

        return $provider && $provider->isAvailable() ? $provider : new JitsiProvider;
    }

    /**
     * Fournisseur d'une session (son `meeting_provider`, sinon le défaut). Si le
     * fournisseur choisi n'est pas configuré (ex. LiveKit sans identifiants), on
     * retombe sur le défaut disponible - pas de salle cassée.
     */
    public function for(Meetable $session): MeetingProvider
    {
        $key = (string) ($session->meetingProviderKey() ?: config('meetings.default', 'livekit'));

        $provider = $this->get($key);

        return $provider && $provider->isAvailable() ? $provider : $this->default();
    }
}
