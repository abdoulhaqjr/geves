<?php

namespace App\Meetings\Concerns;

use App\Enums\MeetingRole;
use App\Meetings\MeetingProviderRegistry;
use App\Meetings\Providers\JitsiProvider;
use App\Meetings\Providers\LiveKitProvider;
use App\Models\User;
use App\Support\EditionMeetingConfig;

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

    /**
     * Exécute $fn avec les réglages de visio de l'ÉDITION posés par-dessus ceux de
     * la plateforme, puis les retire.
     *
     * ⚠️ Toutes les portes d'entrée du socle passent par ici. Sans cela, une
     * édition pourvue de son propre serveur LiveKit ouvrirait quand même ses salles
     * sur celui de la plateforme - et la configuration saisie dans sa fiche ne
     * servirait à rien.
     *
     * @template T
     *
     * @param  callable(): T  $fn
     * @return T
     */
    protected function avecReglagesVisio(callable $fn): mixed
    {
        return EditionMeetingConfig::using($this->meetingEdition(), $fn);
    }

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

        return $this->avecReglagesVisio(
            fn () => app(MeetingProviderRegistry::class)->for($this)->joinUrl($this, $role, $name) ?: null
        );
    }

    /** La salle du fournisseur peut-elle être embarquée en iframe ? */
    public function meetingEmbeddable(): bool
    {
        return $this->avecReglagesVisio(fn () => app(MeetingProviderRegistry::class)->for($this)->embeddable());
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

        return $this->avecReglagesVisio(function () use ($role, $account, $isStaff, $displayName) {
            $provider = app(MeetingProviderRegistry::class)->for($this);
            if (! $provider instanceof JitsiProvider) {
                return null;
            }

            return $provider->embedConfig($this, $role, $this->meetingDisplayName($account, $isStaff, $displayName));
        });
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

        return $this->avecReglagesVisio(function () use ($role, $account, $isStaff, $displayName) {
            $provider = app(MeetingProviderRegistry::class)->for($this);
            if (! $provider instanceof LiveKitProvider) {
                return null;
            }

            $name = $this->meetingDisplayName($account, $isStaff, $displayName);

            return [
                'url' => (string) config('meetings.livekit.url'),
                // ⚠️ L'inscription voyage dans l'IDENTITÉ LiveKit, pas dans le nom
                // affiché : celui-ci se change depuis la salle, et la présence ne peut
                // pas dépendre de ce que quelqu'un a tapé dans un champ.
                'token' => $provider->token($this, $role, $name, $this->meetingParticipantId($account), $account?->id),
                'userName' => $name,
                'canPublish' => in_array($role, [MeetingRole::Moderator, MeetingRole::Presenter], true),
                // Même vocabulaire que la métadonnée du jeton (cf. MeetingRole::roomRole).
                'role' => $role->roomRole(),
                'roomName' => $this->meetingRoom(),
                // Qualité de capture décidée par l'édition (cf. EditionMeetingConfig).
                'quality' => EditionMeetingConfig::quality($this->meetingEdition()),
            ];
        });
    }

    /**
     * Nom affiché dans la salle (compte unifié `users.name`).
     *
     * ⚠️ Public : le fil de discussion signe les messages AVEC CETTE RÈGLE,
     * côté serveur. Deux règles de nommage, et le même message n'aurait pas le
     * même auteur dans la salle et dans l'archive.
     */
    public function meetingDisplayName(?User $account, bool $isStaff, ?string $displayName): string
    {
        return $displayName ?: ($isStaff ? 'Organisation' : (string) ($account?->name ?: 'Invité'));
    }

    /**
     * Inscription (`participants.id`) de ce compte sur l'objet, quand la notion a
     * un sens. Sert à reconnaître un connecté dans la salle sans se fier à son nom
     * d'affichage. Null par défaut : un objet `Meetable` n'a pas forcément de
     * liste d'inscrits (une formation a la sienne, et ne pointe pas de présence).
     */
    protected function meetingParticipantId(?User $account): ?int
    {
        return null;
    }
}
