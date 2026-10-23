<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Facades\Storage;
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

    /**
     * ⚠️⚠️ **UN RÉSUMÉ DÉPOSÉ PORTE LE NOM DE SA RÉFÉRENCE**, `EAU26-029.pdf`,
     * pas les quarante caractères aléatoires que rend `store()`. Le participant
     * le voit dans « Fichiers joints », le gestionnaire le télécharge par
     * paquets, et un dossier de `x8HFrERkQmjt9DGaJi3Ln5VzPUb8JNExBzSwr5Z8.pdf`
     * ne se trie ni ne se relie à rien. C'est déjà ainsi que l'import Eau-Togo
     * nomme ses 49 pièces ; le dépôt et la resoumission s'alignent.
     *
     * ⚠️ **La version est suffixée au-delà de la première** (`EAU26-029-v2.pdf`) :
     * la resoumission ARCHIVE l'exemplaire précédent en lui laissant son
     * `file_url`. Réécrire le même nom écraserait sur le disque le fichier que
     * la version archivée désigne encore - on perdrait l'original en croyant
     * déposer une correction.
     *
     * ⚠️ **On ne renomme PAS par-dessus un fichier existant** : on garde alors
     * le nom actuel. Un homonyme inattendu (référence ressaisie à la main,
     * reprise d'import) coûterait la pièce de quelqu'un d'autre, et un nom moins
     * lisible vaut mieux qu'un fichier perdu.
     *
     * @param  string  $chemin  chemin ACTUEL sur le disque `public`, tel que rendu par `store()`
     * @return string|null le nouveau chemin, ou null si rien n'a bougé
     */
    public static function renameSubmissionFile(string $chemin, string $ref, int $version): ?string
    {
        // ⚠️ La référence finit en NOM DE FICHIER : on n'y laisse passer que ce
        // qui en est un. Elle est générée (`AA-NNN`), mais elle se saisit aussi
        // à l'import - un « / » y ouvrirait un chemin.
        $base = preg_replace('/[^A-Za-z0-9._-]/', '', $ref) ?? '';
        if ($base === '') {
            return null;
        }

        $extension = Str::lower(pathinfo($chemin, PATHINFO_EXTENSION));
        $nom = $base.($version > 1 ? '-v'.$version : '').($extension !== '' ? '.'.$extension : '');
        $cible = trim(Str::beforeLast($chemin, '/'), '/').'/'.$nom;

        $disque = Storage::disk('public');
        if ($cible === $chemin || ! $disque->exists($chemin) || $disque->exists($cible)) {
            return null;
        }

        return $disque->move($chemin, $cible) ? $cible : null;
    }
}
