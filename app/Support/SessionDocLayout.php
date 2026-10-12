<?php

namespace App\Support;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\Slot;

/**
 * Mise en page (entêtes + colonnes) des documents de séance - fiches de session
 * et PV (procès-verbal / rapport de séance) - configurable PAR ÉDITION via
 * {@see EventSettings}. Catalogue de champs/colonnes disponibles + résolution
 * de la sélection (avec libellés personnalisables et repli sur les valeurs par
 * défaut). Éditable par l'admin ou le secrétariat.
 */
class SessionDocLayout
{
    /** Colonnes disponibles pour le tableau des communications. */
    public const COLUMNS = [
        'horaire' => 'Horaire',
        'titre' => 'Titre',
        'auteurs' => 'Auteur(s)',
        'reference' => 'Référence',
        'type' => 'Type',
        'duree' => 'Durée',
        'emargement' => 'Émargement',
    ];

    /** Champs disponibles pour l'entête d'une séance. */
    public const HEADER_FIELDS = [
        'date' => 'Date',
        'horaire' => 'Horaire',
        'salle' => 'Salle',
        'atelier' => 'Atelier',
        'moderator' => 'Modérateur',
        'rapporteur' => 'Rapporteur',
    ];

    /** Sections disponibles pour le PV (issues du rapport du rapporteur). */
    public const PV_SECTIONS = [
        'synthese' => 'Synthèse',
        'points_saillants' => 'Points saillants & recommandations',
        'presents' => 'Affluence estimée',
    ];

    /** @var list<string> */
    public const SHEET_DEFAULT_COLUMNS = ['horaire', 'titre', 'auteurs', 'reference'];

    /** @var list<string> */
    public const SHEET_DEFAULT_HEADER = ['date', 'horaire', 'salle', 'moderator', 'rapporteur'];

    /** @var list<string> */
    public const PV_DEFAULT_COLUMNS = ['titre', 'auteurs'];

    /** @var list<string> */
    public const PV_DEFAULT_HEADER = ['date', 'salle', 'moderator', 'rapporteur'];

    /** @var list<string> */
    public const PV_DEFAULT_SECTIONS = ['synthese', 'points_saillants', 'presents'];

    /** @return list<array{key: string, label: string}> */
    public static function sheetColumns(?Event $event): array
    {
        return self::resolve($event, 'session_sheets.columns', self::COLUMNS, self::SHEET_DEFAULT_COLUMNS);
    }

    /** @return list<array{key: string, label: string}> */
    public static function sheetHeader(?Event $event): array
    {
        return self::resolve($event, 'session_sheets.header', self::HEADER_FIELDS, self::SHEET_DEFAULT_HEADER);
    }

    /** @return list<array{key: string, label: string}> */
    public static function pvColumns(?Event $event): array
    {
        return self::resolve($event, 'session_pv.columns', self::COLUMNS, self::PV_DEFAULT_COLUMNS);
    }

    /** @return list<array{key: string, label: string}> */
    public static function pvHeader(?Event $event): array
    {
        return self::resolve($event, 'session_pv.header', self::HEADER_FIELDS, self::PV_DEFAULT_HEADER);
    }

    /** @return list<array{key: string, label: string}> */
    public static function pvSections(?Event $event): array
    {
        return self::resolve($event, 'session_pv.sections', self::PV_SECTIONS, self::PV_DEFAULT_SECTIONS);
    }

    /**
     * Résout une sélection ordonnée de clés (+ libellés personnalisés) depuis les
     * réglages de l'événement, avec repli sur le catalogue et la sélection par défaut.
     *
     * @param  array<string, string>  $catalog
     * @param  list<string>  $default
     * @return list<array{key: string, label: string}>
     */
    private static function resolve(?Event $event, string $key, array $catalog, array $default): array
    {
        $selected = EventSettings::get($event, $key, null);
        $selected = is_array($selected) && $selected !== [] ? $selected : $default;

        $labels = (array) EventSettings::get($event, $key.'_labels', []);

        $out = [];
        foreach ($selected as $k) {
            $k = (string) $k;
            if (! isset($catalog[$k])) {
                continue;
            }
            $custom = trim((string) ($labels[$k] ?? ''));
            $out[] = ['key' => $k, 'label' => $custom !== '' ? $custom : $catalog[$k]];
        }

        return $out;
    }

    /** Valeur (texte) d'une colonne pour un créneau/communication donné. */
    public static function cell(string $key, Slot $slot): string
    {
        $sub = $slot->submission;

        return match ($key) {
            'horaire' => $slot->starts_at?->format('H:i') ?? '-',
            'titre' => (string) $sub->title,
            'auteurs' => collect($sub->authors ?? [])->pluck('name')->filter()->join(', '),
            'reference' => (string) $sub->ref_code,
            'type' => $sub->type === 'poster' ? 'Poster' : 'Orale',
            'duree' => ($slot->starts_at && $slot->ends_at)
                ? (string) $slot->starts_at->diffInMinutes($slot->ends_at).' min'
                : '',
            'emargement' => '', // colonne de signature laissée vide
            default => '',
        };
    }

    /** Valeur (texte) d'un champ d'entête pour une séance donnée. */
    public static function headerValue(string $key, EventSession $session): string
    {
        return match ($key) {
            'date' => $session->session_day?->translatedFormat('l d F Y') ?? '-',
            'horaire' => $session->starts_at
                ? $session->starts_at->format('H:i').($session->ends_at ? '-'.$session->ends_at->format('H:i') : '')
                : '-',
            'salle' => $session->room ?: '-',
            'atelier' => optional($session->atelier)->title ?? '-',
            'moderator' => $session->moderator_name ?: '-',
            'rapporteur' => $session->rapporteur_name ?: '-',
            default => '',
        };
    }
}
