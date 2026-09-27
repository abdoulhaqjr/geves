<?php

namespace App\Support;

/**
 * Liste de référence des types/grades de participant (profil, auteur
 * correspondant, co-auteurs). **Source unique** des sélecteurs de grade, pour
 * éviter les valeurs libres divergentes : un grade absent de cette liste ne peut
 * être rattaché à aucune catégorie tarifaire, et le montant reste « à préciser »
 * au moment de payer.
 *
 * La liste est **éditable** dans Réglages (clé `participant_types`, un grade par
 * ligne) : chaque institution a ses propres intitulés. `DEFAULTS` ne sert que de
 * point de départ.
 */
class ParticipantTypes
{
    /**
     * Grades proposés par défaut, du plus fréquent au plus général.
     *
     * @var list<string>
     */
    public const DEFAULTS = [
        'Professeur',
        'Maître de conférences',
        'Maître-assistant',
        'Enseignant / Chercheur',
        'Enseignant',
        'Doctorant',
        'Étudiant en master',
        'Autre participant',
    ];

    /**
     * Liste effective : celle des réglages si elle est renseignée, sinon les
     * valeurs par défaut. Toujours non vide (un sélecteur vide bloquerait
     * l'inscription).
     *
     * @return list<string>
     */
    public static function all(): array
    {
        $raw = Settings::get('participant_types');

        $list = collect(is_array($raw) ? $raw : preg_split('/\r\n|\r|\n/', (string) $raw))
            ->map(fn ($g) => trim((string) $g))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $list !== [] ? $list : self::DEFAULTS;
    }
}
