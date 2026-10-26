<?php

namespace App\Actions;

use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\User;
use App\Support\AuthorMatcher;
use App\Support\EventNameFormat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Corriger la liste des auteurs d'une communication, après le dépôt.
 *
 * Les auteurs ne sont **pas rééditables par le participant** : la page
 * « Corriger & renvoyer » ne reprend que titre, résumé, mots-clés et fichier.
 * Or une omission de co-auteur est la correction la plus demandée - et c'est au
 * gestionnaire qu'on l'adresse. C'est ce que cette action lui donne.
 *
 * ⚠️⚠️ **`author_index` EST UN POINTEUR, ET IL DOIT SUIVRE.** Chaque ligne de
 * `submission_authors` désigne sa place dans le JSON `authors` par ce nombre, et
 * {@see EventNameFormat::submissionAuthor()} s'en sert pour
 * retomber sur le nom du COMPTE quand le JSON n'a pas de prénom/nom. Laisser un
 * index en arrière après un retrait ou un déplacement n'écrit aucune erreur :
 * ça affiche le nom de quelqu'un d'autre en face d'un auteur, dans le livret
 * comme dans le programme. Toute la mécanique ci-dessous existe pour ça.
 *
 * ⚠️ **L'auteur correspondant n'est ni retiré ni déplacé ici.** Il est l'ancre
 * de la communication - {@see Submission::authorName()} et `authorEmail()` lisent
 * l'index 0, et la propriété du dépôt s'y rattache. Corriger son orthographe,
 * oui ; changer qui porte la communication est un autre geste, qui touche au
 * participant et aux droits d'écriture.
 */
class UpdateSubmissionAuthors
{
    /** Un historique qui ne grossit pas indéfiniment dans la ligne. */
    private const JOURNAL_MAX = 20;

    public function __construct(protected LinkSubmissionAuthors $liaison) {}

    /**
     * @param  array<int, array<string, mixed>>  $saisis  Liste ORDONNÉE ; `origine` = ancien index, absent = nouvel auteur.
     * @return array{ajoutes: int, retires: int, gardes: int}
     */
    public function handle(Submission $submission, array $saisis, User $parQui): array
    {
        $submission->loadMissing('authorLinks');
        $anciens = $submission->authors ?? [];
        $liens = $submission->authorLinks->keyBy('author_index');

        $authors = [];
        $aRapprocher = [];
        $gardes = [];

        foreach (array_values($saisis) as $place => $ligne) {
            $origine = $ligne['origine'] ?? null;
            $origine = is_numeric($origine) ? (int) $origine : null;
            $ancien = $origine !== null ? ($anciens[$origine] ?? null) : null;

            $auteur = $this->normaliser($ligne, $ancien);

            // ⚠️ Le rôle ne se saisit PAS : il se déduit de la place, et la
            // place 0 garde ce que le dépôt avait posé (`corresponding`,
            // `is_submitter`). Confié au formulaire, on pourrait se retrouver
            // avec deux correspondants, ou aucun.
            if ($place === 0) {
                $auteur['corresponding'] = true;
                $auteur['is_submitter'] = (bool) ($ancien['is_submitter'] ?? false);
            }

            $authors[] = $auteur;

            if ($origine !== null && $liens->has($origine)) {
                $gardes[$origine] = ['lien' => $liens->get($origine), 'place' => $place, 'auteur' => $auteur];
            } elseif ($place > 0) {
                $aRapprocher[] = ['place' => $place, 'auteur' => $auteur];
            }
        }

        $retires = $liens->keys()->reject(fn ($i) => array_key_exists((int) $i, $gardes))->all();

        DB::transaction(function () use ($submission, $authors, $gardes, $aRapprocher, $retires, $parQui) {
            $submission->authors = $authors;
            $submission->meta = $this->journaliser($submission, $parQui, count($aRapprocher), count($retires));
            $submission->save();

            // ⚠️ **On RETIRE avant de replacer.** Les index sont uniques de fait
            // dans la table ; replacer d'abord ferait cohabiter deux lignes sur
            // la même place, et `keyBy` n'en garderait qu'une à la relecture.
            foreach ($retires as $index) {
                $lien = $submission->authorLinks->firstWhere('author_index', (int) $index);
                // Le déposant n'est jamais retiré : sa place 0 est protégée en amont.
                if ($lien && $lien->match_type !== 'self') {
                    $lien->delete();
                }
            }

            foreach ($gardes as $garde) {
                $garde['lien']->update([
                    'author_index' => $garde['place'],
                    'role' => $garde['place'] === 0 ? 'corresponding' : 'coauthor',
                    'name' => (string) $garde['auteur']['name'],
                    'email' => AuthorMatcher::normEmail($garde['auteur']['email'] ?? null) ?: null,
                    'phone' => $garde['auteur']['phone'] ?: null,
                    'affiliation' => $garde['auteur']['affiliation'] ?: null,
                ]);
            }

            foreach ($aRapprocher as $neuf) {
                $lien = new SubmissionAuthor([
                    'submission_id' => $submission->id,
                    'event_id' => $submission->event_id,
                    'role' => 'coauthor',
                    'author_index' => $neuf['place'],
                    'name' => (string) $neuf['auteur']['name'],
                    'email' => AuthorMatcher::normEmail($neuf['auteur']['email'] ?? null) ?: null,
                    'phone' => $neuf['auteur']['phone'] ?: null,
                    'affiliation' => $neuf['auteur']['affiliation'] ?: null,
                    'token' => Str::random(48),
                    'status' => 'unmatched',
                ]);

                // ⚠️ Rapprochement et notifications passent par le chemin du
                // dépôt : un co-auteur ajouté après coup reçoit exactement ce
                // qu'il aurait reçu le premier jour.
                $this->liaison->rapprocher($submission, $lien, $neuf['auteur']);
            }
        });

        $submission->unsetRelation('authorLinks');

        return ['ajoutes' => count($aRapprocher), 'retires' => count($retires), 'gardes' => count($gardes)];
    }

    /**
     * La forme EXACTE de ce que le dépôt écrit - nom de famille en capitales
     * compris ({@see CreateSubmission}). Une seconde forme se verrait dans le
     * livret, à côté des auteurs saisis le premier jour.
     *
     * ⚠️⚠️ **UN NOM QUE PERSONNE N'A TOUCHÉ N'EST PAS RÉÉCRIT.** Le formulaire
     * PROPOSE un découpage prénom/nom pour les auteurs qui n'ont que `name`
     * ({@see EventNameFormat::decouper()}) - une proposition, qui peut se
     * tromper sur un nom composé. Sans cette comparaison, ouvrir le panneau et
     * enregistrer suffirait à passer en capitales, et parfois à réordonner, le
     * nom de TOUS les auteurs d'une communication - alors qu'on venait en
     * ajouter un seul. On ne réécrit que ce qui a vraiment changé.
     *
     * @param  array<string, mixed>  $ligne
     * @param  array<string, mixed>|null  $ancien
     * @return array<string, mixed>
     */
    private function normaliser(array $ligne, ?array $ancien = null): array
    {
        $first = trim((string) ($ligne['first_name'] ?? ''));
        $last = mb_strtoupper(trim((string) ($ligne['last_name'] ?? '')), 'UTF-8');
        $name = trim($first.' '.$last);

        if ($ancien !== null) {
            [$prenomPropose, $nomPropose] = EventNameFormat::decouper($ancien);
            if ($first === trim($prenomPropose) && $last === mb_strtoupper(trim($nomPropose), 'UTF-8')) {
                // Intact : on reprend les champs de nom TELS QU'ILS ÉTAIENT.
                $name = (string) ($ancien['name'] ?? $name);
                $first = (string) ($ancien['first_name'] ?? '');
                $last = (string) ($ancien['last_name'] ?? '');
            }
        }

        return [
            'name' => $name,
            'first_name' => $first,
            'last_name' => $last,
            'email' => ($ligne['email'] ?? null) ?: null,
            'phone' => ($ligne['phone'] ?? null) ?: null,
            'affiliation' => ($ligne['affiliation'] ?? null) ?: null,
            'grade' => ($ligne['grade'] ?? null) ?: null,
        ];
    }

    /**
     * Qui a touché à la liste, et quand.
     *
     * ⚠️ La liste des auteurs d'une communication est une donnée scientifique :
     * elle sort dans le livret et dans les attestations. Une correction sans
     * trace est une correction que personne ne peut expliquer six mois plus tard.
     *
     * @return array<string, mixed>
     */
    private function journaliser(Submission $submission, User $parQui, int $ajoutes, int $retires): array
    {
        $meta = $submission->meta ?? [];
        $journal = $meta['authors_log'] ?? [];
        $journal[] = [
            'at' => now()->toIso8601String(),
            'by' => $parQui->getKey(),
            'by_name' => $parQui->salutationName() ?: $parQui->name,
            'ajoutes' => $ajoutes,
            'retires' => $retires,
        ];
        $meta['authors_log'] = array_slice($journal, -self::JOURNAL_MAX);

        return $meta;
    }
}
