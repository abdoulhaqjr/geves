<?php

namespace App\Support;

/**
 * Liste de référence des types/grades de participant (auteur correspondant et
 * co-auteurs). Source unique pour les sélecteurs de grade (profil, co-auteurs de
 * soumission) afin d'éviter les valeurs libres divergentes.
 */
class ParticipantTypes
{
    /** @var list<string> */
    public const LIST = ['Enseignant / Chercheur', 'Doctorant', 'Autre participant'];

    /** @return list<string> */
    public static function all(): array
    {
        return self::LIST;
    }
}
