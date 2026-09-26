<?php

namespace App\Meetings\Concerns;

use App\Enums\MeetingRole;
use App\Meetings\MeetingProviderRegistry;
use App\Meetings\Providers\JitsiProvider;
use App\Meetings\Providers\LiveKitProvider;
use App\Models\User;

/**
 * Socle d'accès/embarquement commun à tout objet `Meetable` (session du programme,
 * formation…). Seul `meetingRoleFor()` reste propre à chaque modèle : l'URL de
 * connexion, l'embarquement Jitsi et le lecteur LiveKit sont strictement
 * identiques. Tout nouveau module qui porte une visio réutilise ce trait plutôt
 * que de redupliquer la logique du module visio.
 */
trait JoinsMeetings
{
    /** Rôle du visiteur dans cette visio (logique propre à chaque modèle). */
    abstract public function meetingRoleFor(?User $account, bool $isStaff = false): MeetingRole;

    /** Cet utilisateur peut-il rejoindre la visio ? */
    public function canJoin(?User $account, bool $isStaff = false): bool
    {
        return $this->meetingRoleFor($account, $isStaff)->canJoin();
    }

    /** URL de connexion à la salle pour cet utilisateur (null si refusé/absente). */
    public function joinUrl(?User $account, bool $isStaff = false, ?string $displayName = null): ?string
    {
        $role = $this->meetingRoleFor($account, $isStaff);
        if (! $role->canJoin()) {
            return null;
        }

        $name = $this->meetingDisplayName($account, $isStaff, $displayName);

        return app(MeetingProviderRegistry::class)->for($this)->joinUrl($this, $role, $name) ?: null;
    }

    /** La salle du fournisseur peut-elle être embarquée en iframe ? */
    public function meetingEmbeddable(): bool
    {
        return app(MeetingProviderRegistry::class)->for($this)->embeddable();
    }

    /**
     * Config pour l'IFrame API Jitsi (JitsiMeetExternalAPI), ou null si le
     * fournisseur n'est pas Jitsi / accès refusé.
     *
     * @return array{domain: string, roomName: string, jwt: ?string, userName: string}|null
     */
    public function meetingEmbedConfig(?User $account, bool $isStaff = false, ?string $displayName = null): ?array
    {
        $role = $this->meetingRoleFor($account, $isStaff);
        if (! $role->canJoin()) {
            return null;
        }

        $provider = app(MeetingProviderRegistry::class)->for($this);
        if (! $provider instanceof JitsiProvider) {
            return null;
        }

        return $provider->embedConfig($this, $role, $this->meetingDisplayName($account, $isStaff, $displayName));
    }

    /**
     * Config pour le lecteur LiveKit inline (SDK livekit-client), ou null si le
     * fournisseur n'est pas LiveKit / accès refusé. Le token porte les droits
     * (publier/s'abonner/admin) dérivés du rôle.
     *
     * @return array{url: string, token: string, userName: string, canPublish: bool, role: string, roomName: string}|null
     */
    public function liveKitEmbedConfig(?User $account, bool $isStaff = false, ?string $displayName = null): ?array
    {
        $role = $this->meetingRoleFor($account, $isStaff);
        if (! $role->canJoin()) {
            return null;
        }

        $provider = app(MeetingProviderRegistry::class)->for($this);
        if (! $provider instanceof LiveKitProvider) {
            return null;
        }

        $name = $this->meetingDisplayName($account, $isStaff, $displayName);

        return [
            'url' => (string) config('meetings.livekit.url'),
            'token' => $provider->token($this, $role, $name),
            'userName' => $name,
            'canPublish' => in_array($role, [MeetingRole::Moderator, MeetingRole::Presenter], true),
            'role' => match ($role) {
                MeetingRole::Moderator => 'moderator',
                MeetingRole::Presenter => 'presenter',
                default => 'audience',
            },
            'roomName' => $this->meetingRoom(),
        ];
    }

    /** Nom affiché dans la salle (compte unifié `users.name`). */
    private function meetingDisplayName(?User $account, bool $isStaff, ?string $displayName): string
    {
        return $displayName ?: ($isStaff ? 'Organisation' : (string) ($account?->name ?: 'Invité'));
    }
}
