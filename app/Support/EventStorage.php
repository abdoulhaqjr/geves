<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Str;

/**
 * **Où vivent les fichiers d'un événement.** Source unique du nom de dossier,
 * pour que ce qu'on stocke se lise sans ouvrir la base.
 *
 * Les pièces jointes étaient rangées sous l'**identifiant** de l'événement -
 * `submissions/2/…`, `events/2/…` - et l'habillage à plat dans `events/`. Un
 * dossier nommé `2` ne dit rien : pour savoir de quel colloque il s'agit, il
 * fallait interroger la base, et deux événements mélangeaient leurs visuels
 * dans le même répertoire. Les fichiers vivent désormais sous
 * **`submissions/nom-de-l-evenement-annee/`** et
 * **`events/nom-de-l-evenement-annee/`**.
 *
 * ⚠️ **Le nom vient du SLUG, pas du titre.** C'est déjà l'identifiant public de
 * l'événement (`/colloque/{slug}`), il est **unique en base** - donc deux
 * événements ne peuvent pas partager un dossier - et il est déjà normalisé pour
 * une URL, ce qu'un titre n'est pas (« VIIIe Colloque - Innover pour un avenir
 * durable » ferait un répertoire de 48 caractères).
 *
 * ⚠️ **L'année n'est ajoutée que si le nom ne la porte pas déjà.** Les deux
 * colloques de la base la portent (`premier-colloque-eau-togo-2026`) : ajouter
 * `year` sans regarder donnait `avenir-durable-2025-2026`, deux millésimes
 * contradictoires dans le même nom de dossier.
 *
 * ⚠️ **Un chemin déjà enregistré ne se recalcule JAMAIS.** La base garde l'URL
 * du fichier (`submissions.file_url`, `media.url`, `settings.hero_image`…), et
 * c'est elle qu'on relit. Le slug étant **modifiable**, un dossier déduit à la
 * lecture pointerait dans le vide le jour où l'admin renomme l'événement : les
 * anciens fichiers restent où ils sont, les nouveaux vont dans le nouveau
 * dossier, et rien ne casse. (La migration
 * `2026_10_17_000100_classer_les_fichiers_par_evenement` a déplacé l'existant,
 * en réécrivant les URL enregistrées dans le même mouvement.)
 */
final class EventStorage
{
    /** Dossier racine des pièces jointes des communications. */
    public const SUBMISSIONS = 'submissions';

    /** Dossier racine de l'habillage d'un événement (bandeau, bannière, logos). */
    public const ARTWORK = 'events';

    /** Dossier racine des visuels de formation (flyer). */
    public const FORMATIONS = 'formations';

    /** Dossier racine du livre des résumés (logo de couverture). */
    public const LIVRET = 'livret';

    /** Dossier racine des gabarits .docx importés pour le livre. */
    public const LIVRET_TEMPLATES = 'livret-templates';

    /**
     * Les cinq racines rangées PAR ÉVÉNEMENT. ⚠️ Tout ce qui n'y est pas est
     * volontairement à plat : `branding/` et `ai-agents/` appartiennent à la
     * plateforme, `account-photos/`, `staff-photos/`, `formation-trainers/` et
     * `formation-signatures/` à une PERSONNE - la photo de quelqu'un ne change
     * pas d'un colloque à l'autre, et la ranger par événement la dupliquerait.
     *
     * @var list<string>
     */
    public const ROOTS = [
        self::SUBMISSIONS,
        self::ARTWORK,
        self::FORMATIONS,
        self::LIVRET,
        self::LIVRET_TEMPLATES,
    ];

    /**
     * Nom de dossier d'un événement : `nom-de-l-evenement-annee`.
     *
     * @param  Event|string  $event  l'événement, ou directement son slug - la
     *                               création d'un événement téléverse son
     *                               habillage AVANT que la ligne n'existe, et il
     *                               n'y a alors que le slug résolu.
     */
    public static function folder(Event|string $event, int|string|null $year = null): string
    {
        if ($event instanceof Event) {
            $year ??= $event->year;
            // ⚠️ Jamais de segment VIDE : il écrirait dans le dossier parent, et
            // tous les événements mêleraient leurs fichiers. Repli sur le titre,
            // puis sur l'identifiant - qui, lui, existe toujours.
            $nom = Str::slug((string) $event->slug)
                ?: (Str::slug((string) $event->title) ?: 'evenement-'.$event->id);
        } else {
            $nom = Str::slug($event) ?: 'evenement';
        }

        $annee = (int) $year;

        // ⚠️ On regarde si le nom finit par UNE année, pas par CETTE année :
        // `avenir-durable-2025` porte 2025 quand la colonne dit 2026.
        if ($annee > 0 && preg_match('/\d{4}$/', $nom) !== 1) {
            $nom .= '-'.$annee;
        }

        return $nom;
    }

    /**
     * Dossier d'un type de document pour cet événement :
     * `<racine>/<nom-de-l-evenement-annee>`.
     */
    public static function in(string $root, Event|string $event, int|string|null $year = null): string
    {
        return $root.'/'.self::folder($event, $year);
    }

    /** Dossier des pièces jointes des communications de cet événement. */
    public static function submissions(Event|string $event, int|string|null $year = null): string
    {
        return self::in(self::SUBMISSIONS, $event, $year);
    }

    /** Dossier de l'habillage de cet événement. */
    public static function artwork(Event|string $event, int|string|null $year = null): string
    {
        return self::in(self::ARTWORK, $event, $year);
    }
}
