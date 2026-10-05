<?php

namespace App\Actions;

use App\AI\AiManager;
use App\Models\Event;
use App\Support\EventReport;
use Illuminate\Support\Str;

/**
 * Génère un rapport ou un document personnalisé par IA à partir d'un brief de
 * l'administrateur (et d'un exemple optionnel dont l'IA reproduit la structure).
 * L'IA ne fait que **proposer** un brouillon : la décision et la diffusion
 * restent à l'administrateur.
 *
 * ⚠️ **Deux formes de sortie, parce qu'un export n'est pas un rapport** :
 *  - `table` (colonnes + lignes) quand la demande vise un **tableau** - c'est le
 *    cas dès que le format attendu est XLSX ou CSV ;
 *  - `sections` (titre + corps) quand elle vise un **document** rédigé (PDF, Word).
 * Avant, seule la prose existait : un « export » Excel contenait des paragraphes
 * et, à côté, un déversement brut de toute la base - jamais ce qui était demandé.
 *
 * ⚠️ **Les données réelles sont transmises à l'IA** (les lignes du périmètre
 * choisi, pas seulement cinq totaux) : sans elles, « la liste des communications
 * acceptées par axe » était impossible à produire, et l'IA retombait sur un
 * bilan générique. Elles partent chez le fournisseur configuré : c'est annoncé
 * dans l'écran d'export.
 *
 * @phpstan-type ReportSection array{heading: string, body: string}
 * @phpstan-type ReportTable array{columns: list<string>, rows: list<list<string>>}
 * @phpstan-type Dataset array{0: string, 1: list<string>, 2: array<int, array<int, string|float|int>>}
 */
class GenerateReport
{
    /** Au-delà, le contexte explose : on tronque et on le DIT à l'IA. */
    private const MAX_ROWS = 600;

    public function __construct(protected AiManager $manager) {}

    /**
     * @param  array<int, array{mime: string, data: string}>  $files
     * @param  array<int, Dataset>  $datasets  données réelles mises à disposition de l'IA
     * @param  'table'|'document'  $output  forme de sortie attendue
     * @return array{title: string, sections: list<ReportSection>, table: ReportTable|null}
     */
    public function handle(
        Event $event,
        string $brief,
        array $files = [],
        ?string $providerKey = null,
        string $style = 'report',
        array $datasets = [],
        string $output = 'document',
    ): array {
        $needsPdf = collect($files)->contains(fn ($f) => str_contains($f['mime'], 'pdf'));

        if (! $this->manager->resolve($needsPdf)) {
            throw new \RuntimeException('Aucun fournisseur d’IA n’est configuré (voir Réglages).');
        }

        // Génération de document/rapport : palier qualité (rédaction et structure soignées).
        // Le fournisseur choisi est tenté en premier, avec bascule automatique en cas d'échec.
        $raw = $this->manager->extract(
            $this->instruction($event, $brief, $files !== [], $style, $datasets, $output),
            $files,
            'deep',
            $providerKey,
        );

        return $this->sanitize($raw, $output);
    }

    /**
     * @param  array<int, Dataset>  $datasets
     * @param  'table'|'document'  $output
     */
    protected function instruction(
        Event $event,
        string $brief,
        bool $hasExample,
        string $style = 'report',
        array $datasets = [],
        string $output = 'document',
    ): string {
        $title = $event->title ?: ('Événement '.$event->year);

        $intro = $style === 'document'
            ? $this->documentIntro($title, $event->year, $hasExample)
            : $this->reportIntro($event, $title, $hasExample);

        $data = $this->datasetBlock($datasets);
        $shape = $this->outputRules($output);

        return <<<TXT
        {$intro}
        {$data}

        Demande de l'utilisateur, à satisfaire LITTÉRALEMENT : {$brief}

        {$shape}

        Réponds UNIQUEMENT par un objet JSON STRICT (aucun texte autour) :
        { "title": string,
          "table": { "columns": [string], "rows": [[string]] } | null,
          "sections": [ { "heading": string, "body": string } ] }
        Dans "table", chaque ligne a EXACTEMENT autant de cellules que de colonnes,
        et toutes les cellules sont des chaînes. Le champ "body" peut contenir
        plusieurs paragraphes séparés par des retours à la ligne. N'utilise pas de HTML.
        TXT;
    }

    /**
     * Consignes de FORME : c'est ce qui fait qu'un export Excel contient enfin un
     * tableau, et non des paragraphes.
     *
     * @param  'table'|'document'  $output
     */
    protected function outputRules(string $output): string
    {
        if ($output === 'table') {
            return <<<'TXT'
            FORME ATTENDUE - TABLEAU (le fichier produit sera un Excel ou un CSV) :
            - Renvoie OBLIGATOIREMENT "table", avec des colonnes explicites et
              UNE LIGNE PAR ENREGISTREMENT demandé. C'est le cœur de la réponse.
            - Reprends exactement le découpage demandé (colonnes, filtres, tri,
              regroupement). Si la demande groupe par une valeur (axe, statut…),
              ajoute une colonne portant ce groupe plutôt que des intertitres.
            - "sections" reste VIDE, ou contient au plus une section « Méthode »
              d'une ou deux phrases (périmètre retenu, filtres appliqués).
            - N'ajoute aucune ligne de total ni de commentaire dans le tableau.
            TXT;
        }

        return <<<'TXT'
        FORME ATTENDUE - DOCUMENT (le fichier produit sera un PDF ou un Word) :
        - Rédige "sections" : c'est le corps du document.
        - Si la demande porte sur une liste ou un dénombrement, ajoute EN PLUS
          "table" avec les enregistrements concernés ; sinon laisse "table" à null.
        TXT;
    }

    /**
     * Données réelles mises à disposition de l'IA, en texte tabulé.
     *
     * @param  array<int, Dataset>  $datasets
     */
    protected function datasetBlock(array $datasets): string
    {
        if ($datasets === []) {
            return '';
        }

        $blocks = [];
        foreach ($datasets as [$name, $headers, $rows]) {
            $total = count($rows);
            $kept = array_slice($rows, 0, self::MAX_ROWS);

            $lines = [implode(' | ', $headers)];
            foreach ($kept as $row) {
                $lines[] = implode(' | ', array_map(
                    fn ($cell) => str_replace(['|', "\n", "\r"], ['/', ' ', ' '], (string) $cell),
                    $row,
                ));
            }

            // ⚠️ La troncature est ANNONCÉE : une liste incomplète présentée comme
            // complète est pire qu'un refus.
            $note = $total > count($kept)
                ? ' - ATTENTION : seules les '.count($kept)." premières lignes sur {$total} sont fournies ;"
                    .' signale-le explicitement dans ta réponse.'
                : '';

            $blocks[] = "=== {$name} ({$total} lignes){$note}\n".implode("\n", $lines);
        }

        return "\n\nDONNÉES RÉELLES DE L'ÉDITION - la réponse doit en être tirée, "
            ."sans rien inventer ni extrapoler :\n".implode("\n\n", $blocks);
    }

    /** Cadrage « document administratif » (secrétariat) : sortie épurée, pas d'analyse. */
    protected function documentIntro(string $title, int|string $year, bool $hasExample): string
    {
        $example = $hasExample
            ? 'Un exemple est joint : reproduis fidèlement sa STRUCTURE et son format (mêmes sections/colonnes).'
            : '';

        return <<<TXT
        Tu es le SECRÉTARIAT du colloque « {$title} » ({$year}). Produis un DOCUMENT ADMINISTRATIF
        prêt à imprimer (liste de présence, feuille d'émargement, affiche directionnelle, ordre du
        jour, chevalets nominatifs…), et NON un rapport d'analyse.

        Règles impératives :
        - N'écris NI introduction analytique, NI section « chiffres clés », NI « analyse », NI
          « recommandations ». Produis DIRECTEMENT le document demandé.
        - Structure-le en sections COURTES correspondant à son contenu réel (en-tête / mentions,
          puis les entrées : intitulés, salles, horaires, noms…). Reste concis, factuel et neutre.
        - N'invente AUCUN nom ni donnée : utilise UNIQUEMENT les informations fournies ci-dessous
          (programme réel et contexte de la demande). {$example}
        TXT;
    }

    /** Cadrage « rapport factuel » (analyses/bilans d'événement). */
    protected function reportIntro(Event $event, string $title, bool $hasExample): string
    {
        $s = (new EventReport($event))->summary();
        $cur = $s['currency'];

        $data = implode("\n", [
            '- Participants inscrits : '.$s['participants'],
            '- Communications déposées : '.$s['submissions'],
            '- Communications acceptées : '.$s['accepted'],
            '- Montant encaissé : '.$s['collected'].' '.$cur,
            '- Montant en attente : '.$s['pending'].' '.$cur,
        ]);

        // ⚠️ Pas de plan imposé quand l'utilisateur a décrit ce qu'il veut : le
        // « contexte / chiffres clés / analyse / recommandations » s'imposait à
        // tout, y compris à une simple liste.
        $example = $hasExample
            ? 'Un exemple de rapport est joint : reproduis fidèlement sa STRUCTURE et son format (mêmes sections).'
            : 'Structure la réponse selon la demande elle-même ; n’ajoute une analyse ou des '
                .'recommandations que si elles sont explicitement demandées.';

        return <<<TXT
        Tu es analyste à la Direction de la Recherche et de l'Innovation (DRI) de l'Université de Kara.
        Réponds en français, à partir des DONNÉES RÉELLES ci-dessous. N'invente AUCUN chiffre ni fait :
        n'utilise que ces données (et l'exemple joint le cas échéant).

        Événement : {$title} ({$event->year})
        Totaux de l'événement :
        {$data}

        {$example}
        TXT;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  'table'|'document'  $output
     * @return array{title: string, sections: list<ReportSection>, table: ReportTable|null}
     */
    protected function sanitize(array $raw, string $output = 'document'): array
    {
        $title = trim((string) ($raw['title'] ?? 'Rapport'));

        $rawSections = is_array($raw['sections'] ?? null) ? $raw['sections'] : [];

        $sections = [];
        foreach (array_slice($rawSections, 0, 30) as $section) {
            if (! is_array($section)) {
                continue;
            }
            $heading = Str::limit(trim((string) ($section['heading'] ?? '')), 200, '');
            $body = Str::limit(trim((string) ($section['body'] ?? '')), 8000, '');
            if ($heading !== '' || $body !== '') {
                $sections[] = ['heading' => $heading, 'body' => $body];
            }
        }

        $table = $this->sanitizeTable($raw['table'] ?? null);

        // Un tableau était exigé et rien n'est exploitable : mieux vaut le dire que
        // livrer un fichier vide ou, pire, un déversement générique de la base.
        if ($output === 'table' && $table === null) {
            throw new \RuntimeException(
                'L’IA n’a pas produit de tableau exploitable. Précisez les colonnes attendues, '
                .'ou choisissez un format PDF/Word pour un texte rédigé.'
            );
        }

        return [
            'title' => $title !== '' ? Str::limit($title, 200, '') : 'Rapport',
            'sections' => $sections,
            'table' => $table,
        ];
    }

    /**
     * Normalise le tableau : colonnes non vides, lignes alignées sur ces colonnes,
     * cellules en chaînes. Une ligne plus courte est complétée, une ligne plus
     * longue est coupée - un tableau décalé est illisible dans Excel.
     *
     * @return ReportTable|null
     */
    protected function sanitizeTable(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $columns = [];
        foreach (array_slice(is_array($raw['columns'] ?? null) ? $raw['columns'] : [], 0, 30) as $col) {
            $label = Str::limit(trim((string) (is_scalar($col) ? $col : '')), 120, '');
            if ($label !== '') {
                $columns[] = $label;
            }
        }

        if ($columns === []) {
            return null;
        }

        $rows = [];
        foreach (array_slice(is_array($raw['rows'] ?? null) ? $raw['rows'] : [], 0, 5000) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cells = [];
            foreach (array_values($row) as $cell) {
                $cells[] = Str::limit(trim((string) (is_scalar($cell) ? $cell : '')), 2000, '');
            }
            $cells = array_slice(array_pad($cells, count($columns), ''), 0, count($columns));
            if (implode('', $cells) !== '') {
                $rows[] = $cells;
            }
        }

        return $rows === [] ? null : ['columns' => $columns, 'rows' => $rows];
    }
}
