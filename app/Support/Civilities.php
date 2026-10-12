<?php

namespace App\Support;

/**
 * Civilités proposées sur la plateforme - **source unique** (inscription, profil
 * participant, profil staff, fabrique de test).
 *
 * ⚠️ Ne jamais redéclarer la liste ailleurs : trois contrôleurs la recopiaient
 * et divergeaient déjà dans l'ordre. La civilité n'est **pas contrainte en base**
 * (chaîne libre, `max:20`) : cette liste ne pilote que les choix proposés à
 * l'écran, d'anciennes valeurs (« Dr », « Pr ») restent donc valides si elles
 * existent déjà. Le titre académique se capte par ailleurs (grade, titre du
 * formateur) - la civilité reste un simple honorifique.
 */
class Civilities
{
    /** @var list<string> */
    public const ALL = ['M.', 'Mme', 'Mlle'];

    /** @return list<string> */
    public static function all(): array
    {
        return self::ALL;
    }
}
