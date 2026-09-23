<?php

namespace App\Meetings\Contracts;

/**
 * Objet pouvant porter une visio interactive (session du programme OU formation).
 * Les fournisseurs et le registre n'ont besoin que de ces trois informations ;
 * chaque modèle porte sa propre logique de rôle/accès.
 */
interface Meetable
{
    /** Nom de salle (fourni ou déterministe). */
    public function meetingRoom(): string;

    /** Lien externe saisi (fournisseur « custom »), sinon null. */
    public function meetingUrl(): ?string;

    /** Clé du fournisseur choisi (`livekit`, `jitsi`, `custom`), sinon null. */
    public function meetingProviderKey(): ?string;
}
