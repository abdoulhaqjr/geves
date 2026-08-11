<?php

namespace App\Meetings\Contracts;

use App\Enums\MeetingRole;
use App\Models\EventSession;

/**
 * Contrat commun aux fournisseurs de visio interactive. Un fournisseur s'annonce
 * (clé, libellé) et sait produire l'URL de connexion à la salle d'une session
 * pour un rôle et un nom d'affichage donnés.
 */
interface MeetingProvider
{
    public function key(): string;

    public function label(): string;

    /** Vrai si le fournisseur est utilisable (config présente). */
    public function isAvailable(): bool;

    /**
     * URL de connexion à la salle de la session, pour le rôle et le nom donnés.
     * Le rôle peut être matérialisé côté serveur (JWT) si le fournisseur le
     * supporte, sinon il n'est qu'indicatif (nom d'affichage).
     */
    public function joinUrl(EventSession $session, MeetingRole $role, ?string $displayName = null): string;
}
