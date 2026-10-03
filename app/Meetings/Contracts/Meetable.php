<?php

namespace App\Meetings\Contracts;

use App\Enums\MeetingRole;
use App\Enums\MeetingStatus;
use App\Models\Edition;
use App\Models\User;

/**
 * Objet pouvant porter une visio interactive (session du programme OU formation).
 * Les fournisseurs n'ont besoin que de la salle/lien/fournisseur ; la page
 * « Rejoindre » du module visio (MeetingController) s'appuie en plus sur l'état
 * et le calcul de rôle. Chaque modèle garde sa propre règle d'accès
 * (`meetingRoleFor`) ; tout le reste vient du trait `JoinsMeetings`.
 */
interface Meetable
{
    /** Nom de salle (fourni ou déterministe). */
    public function meetingRoom(): string;

    /** Lien externe saisi (fournisseur « custom »), sinon null. */
    public function meetingUrl(): ?string;

    /** Clé du fournisseur choisi (`livekit`, `jitsi`, `custom`), sinon null. */
    public function meetingProviderKey(): ?string;

    /** État de la visio (aucune / programmée / en direct / terminée). */
    public function meetingStatusEnum(): MeetingStatus;

    /** La visio est-elle en cours ? */
    public function isLive(): bool;

    /** Rôle calculé du visiteur (règle propre à chaque modèle). */
    public function meetingRoleFor(?User $account, bool $isStaff = false): MeetingRole;

    /** Ce visiteur peut-il rejoindre ? */
    public function canJoin(?User $account, bool $isStaff = false): bool;

    /** Nom affiché dans la salle - et signature des messages du fil. */
    public function meetingDisplayName(?User $account, bool $isStaff, ?string $displayName): string;

    /**
     * Édition à laquelle cette salle appartient.
     *
     * ⚠️ C'est elle qui porte les réglages de visio (serveur, identifiants,
     * qualité) : sans ce point d'accès, le socle commun ne saurait pas de quelle
     * configuration relève la salle qu'il est en train d'ouvrir.
     */
    public function meetingEdition(): ?Edition;

    /** URL de connexion à la salle, ou null si refusé/absente. */
    public function joinUrl(?User $account, bool $isStaff = false, ?string $displayName = null): ?string;

    /** La salle du fournisseur peut-elle être embarquée en iframe ? */
    public function meetingEmbeddable(): bool;

    /**
     * Config de l'IFrame API Jitsi, ou null (autre fournisseur / accès refusé).
     *
     * @return array{domain: string, roomName: string, jwt: ?string, userName: string}|null
     */
    public function meetingEmbedConfig(?User $account, bool $isStaff = false, ?string $displayName = null): ?array;

    /**
     * Config du lecteur LiveKit inline, ou null (autre fournisseur / accès refusé).
     *
     * @return array{url: string, token: string, userName: string, canPublish: bool, role: string, roomName: string}|null
     */
    public function liveKitEmbedConfig(?User $account, bool $isStaff = false, ?string $displayName = null): ?array;
}
