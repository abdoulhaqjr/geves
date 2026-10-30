<?php

namespace App\Documents;

use App\Models\Event;
use App\Support\TaskDocSpace;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Assemble le livre des résumés : couverture, pages ajoutées, corps du livre.
 *
 * ⚠️⚠️ **DEUX LECTEURS, UN SEUL ASSEMBLAGE** - la règle qui gouverne déjà
 * {@see LivretView::data()}. Le gestionnaire relit le livre depuis le
 * back-office, le participant le télécharge depuis son espace, et c'est le
 * **même** fichier : les deux contrôleurs passent par ici. Deux chemins
 * d'assemblage se décalent toujours, et celui-là l'avait déjà fait.
 *
 * ⚠️⚠️ **L'ORDRE DES TROIS TEMPS NE SE PERMUTE PAS**, et c'est tout l'art de
 * cette classe :
 *
 *   1. **Convertir** chaque page ajoutée en PDF et **compter** ses pages.
 *   2. **Rendre le livre** en lui faisant RÉSERVER ce nombre de pages blanches
 *      à l'endroit voulu, chacune portant un repère.
 *   3. **Remplacer** les pages réservées par les vraies.
 *
 * Le comptage vient donc AVANT le rendu, et il ne peut pas en être autrement :
 * personne ne sait combien de pages fait un document Word avant de l'avoir
 * converti, et c'est ce nombre qui dit combien de place réserver.
 *
 * ⚠️⚠️ **POURQUOI RÉSERVER PLUTÔT QUE GLISSER.** Le livre NUMÉROTE ses pages
 * ({@see LivretPdf::make()}). Une page glissée au milieu décale tout ce qui
 * suit : le numéro imprimé sur la page ne correspond plus à sa place, et le
 * sommaire comme les renvois deviennent faux - **sans que rien ne le signale**.
 * En réservant, le livre est rendu avec le bon nombre de pages dès le départ,
 * donc la numérotation est juste.
 *
 * ⚠️ **Les pages placées PAR NUMÉRO font exception** : elles ne peuvent pas
 * réserver leur place, puisque personne ne sait avant le rendu quelle page
 * portera le numéro demandé. Elles décalent donc ce qui suit, et c'est la
 * rançon de ce mode - que l'écran annonce au gestionnaire avant qu'il le
 * choisisse ({@see LivretPages}).
 */
final class LivretAssembly
{
    /** Le PDF complet, tel que le gestionnaire et le participant le reçoivent. */
    public static function pdf(Event $event, Collection $groups, TaskDocSpace $docspace): string
    {
        $pages = LivretPages::active($event);

        // Rien d'ajouté : on garde le chemin court (couverture seule, ou le
        // livre tel quel). Un sous-processus Node pour ne rien assembler serait
        // une seconde d'attente offerte à chaque téléchargement.
        if ($pages === []) {
            $pdf = LivretPdf::make($event, $groups)->output();

            return LivretCover::fileId($event) === '' ? $pdf : LivretCover::pdf($event, $pdf, $docspace);
        }

        $fichiers = [];
        try {
            $ajouts = self::convertir($event, $pages, $docspace, $fichiers);
            $livre = LivretPdf::make($event, $groups, self::reserves($ajouts))->output();

            return self::assembler($event, $livre, $ajouts, $docspace, $fichiers);
        } finally {
            foreach ($fichiers as $chemin) {
                @unlink($chemin);
            }
        }
    }

    /**
     * Les pages ajoutées pour le gabarit Word : repère => binaire .docx.
     *
     * ⚠️ On télécharge le .docx TEL QUEL, sans conversion : Word reflue son
     * contenu, donc le nombre de pages n'a pas à être connu - c'est tout ce qui
     * sépare ce chemin de celui du PDF.
     *
     * @return array<string, string>
     */
    public static function wordPages(Event $event, TaskDocSpace $docspace): array
    {
        $pages = [];
        foreach (LivretPages::active($event) as $page) {
            $pages[LivretDocx::PAGE_MARKER.$page['id'].']]'] = $docspace->downloadFile($page['file_id']);
        }

        return $pages;
    }

    /**
     * Les emplacements à réserver dans le flux du livre, par point d'insertion.
     *
     * @param  list<array<string, mixed>>  $ajouts
     * @return array<string, list<array{uri: string, pages: int}>>
     */
    public static function reserves(array $ajouts): array
    {
        $reserves = [];
        foreach ($ajouts as $ajout) {
            if ($ajout['slot'] === '') {
                continue;
            }
            $reserves[$ajout['slot']][] = ['uri' => $ajout['uri'], 'pages' => $ajout['pages']];
        }

        return $reserves;
    }

    /**
     * Convertit chaque page ajoutée en PDF et compte ses pages.
     *
     * @param  list<array<string, mixed>>  $pages
     * @param  list<string>  $fichiers  fichiers temporaires, vidés par l’appelant
     * @return list<array<string, mixed>>
     */
    private static function convertir(Event $event, array $pages, TaskDocSpace $docspace, array &$fichiers): array
    {
        $ajouts = [];
        foreach ($pages as $page) {
            $chemin = self::fichier('page', $fichiers);
            file_put_contents($chemin, self::enPdf($event, $page['file_id'], $docspace));
            $ajouts[] = [
                'id' => $page['id'],
                'title' => $page['title'],
                'slot' => LivretPages::slot($page),
                'page' => $page['page'],
                'uri' => LivretPages::URI.$page['id'],
                'file' => $chemin,
                'pages' => 0,
            ];
        }

        $comptes = self::node([
            'action' => 'count',
            'files' => array_column($ajouts, 'file'),
        ], $fichiers)['pages'] ?? [];

        foreach ($ajouts as $i => $ajout) {
            $nombre = (int) ($comptes[$i] ?? 0);
            if ($nombre < 1) {
                throw new RuntimeException('La page ajoutée « '.$ajout['title'].' » ne contient aucune page.');
            }
            $ajouts[$i]['pages'] = $nombre;
        }

        return $ajouts;
    }

    /**
     * @param  list<array<string, mixed>>  $ajouts
     * @param  list<string>  $fichiers
     */
    private static function assembler(Event $event, string $livre, array $ajouts, TaskDocSpace $docspace, array &$fichiers): string
    {
        $cheminLivre = self::fichier('livre', $fichiers);
        $sortie = self::fichier('assemble', $fichiers);
        file_put_contents($cheminLivre, $livre);

        $spec = [
            'action' => 'assemble',
            'book' => $cheminLivre,
            'output' => $sortie,
            'title' => 'Livre des résumés - '.$event->title,
            'author' => (string) config('app.name', 'GEVES').' - Université de Kara',
            'cover' => null,
            'replace' => [],
            'insert' => [],
        ];

        $couverture = LivretCover::fileId($event);
        if ($couverture !== '') {
            $chemin = self::fichier('couverture', $fichiers);
            file_put_contents($chemin, self::enPdf($event, $couverture, $docspace));
            $spec['cover'] = $chemin;
        }

        foreach ($ajouts as $ajout) {
            if ($ajout['slot'] !== '') {
                $spec['replace'][] = [
                    'uri' => $ajout['uri'],
                    'pages' => $ajout['pages'],
                    'file' => $ajout['file'],
                    'title' => $ajout['title'],
                ];

                continue;
            }
            $spec['insert'][] = ['page' => $ajout['page'], 'file' => $ajout['file']];
        }

        self::node($spec, $fichiers);

        $binaire = file_get_contents($sortie);
        if ($binaire === false || ! str_starts_with($binaire, '%PDF-')) {
            throw new RuntimeException('Le livre assemblé est invalide.');
        }

        return $binaire;
    }

    /** Un document DocSpace, converti en PDF puis retiré de la salle. */
    private static function enPdf(Event $event, string $fileId, TaskDocSpace $docspace): string
    {
        $converti = $docspace->saveAsPdf($fileId, $docspace->roomId($event));
        try {
            return $docspace->downloadFile($converti);
        } finally {
            // ⚠️ Un refus de suppression du fichier temporaire ne doit pas
            // transformer un export réussi en erreur pour le gestionnaire.
            try {
                $docspace->deleteFile($converti);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Appelle le script d'assemblage.
     *
     * ⚠️⚠️ **LA SPÉCIFICATION PART DANS UN FICHIER, JAMAIS EN ARGUMENTS.** Les
     * chemins et les titres portent des espaces, des accents et des guillemets ;
     * sous Windows, cmd les recoupe à sa façon et l'assemblage se ferait au
     * mauvais endroit - ou pas du tout. C'est le même piège que les sabotages
     * passés à `php.bat`, consigné dans CLAUDE.md.
     *
     * @param  array<string, mixed>  $spec
     * @param  list<string>  $fichiers
     * @return array<string, mixed>
     */
    private static function node(array $spec, array &$fichiers): array
    {
        $chemin = self::fichier('spec', $fichiers);
        file_put_contents($chemin, (string) json_encode($spec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $process = new Process([
            (string) config('services.livret_pdf_node_binary', 'node'),
            base_path('scripts/livret-assemble.mjs'),
            $chemin,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Impossible d’assembler le livre des résumés. '.trim($process->getErrorOutput()));
        }

        $sortie = json_decode(trim($process->getOutput()), true);

        return is_array($sortie) ? $sortie : [];
    }

    /** @param list<string> $fichiers */
    private static function fichier(string $role, array &$fichiers): string
    {
        $chemin = tempnam(sys_get_temp_dir(), 'livret-'.$role.'-');
        if ($chemin === false) {
            throw new RuntimeException('Impossible de préparer l’assemblage du livre.');
        }
        $fichiers[] = $chemin;

        return $chemin;
    }
}
