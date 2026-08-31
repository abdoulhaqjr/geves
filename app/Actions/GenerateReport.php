<?php

namespace App\Actions;

use App\AI\AiManager;
use App\Models\Edition;
use App\Support\EditionReport;
use Illuminate\Support\Str;

/**
 * Génère un rapport personnalisé par IA à partir d'un brief de l'administrateur
 * (et d'un exemple de rapport optionnel dont l'IA reproduit la structure). Le
 * rapport est ancré sur les DONNÉES RÉELLES de l'édition (l'IA n'invente aucun
 * chiffre). L'IA ne fait que **proposer** un brouillon : la décision et la
 * diffusion restent à l'administrateur.
 *
 * @phpstan-type ReportSection array{heading: string, body: string}
 */
class GenerateReport
{
    public function __construct(protected AiManager $manager) {}

    /**
     * @param  array<int, array{mime: string, data: string}>  $files
     * @return array{title: string, sections: list<ReportSection>}
     */
    public function handle(Edition $edition, string $brief, array $files = [], ?string $providerKey = null): array
    {
        $needsPdf = collect($files)->contains(fn ($f) => str_contains($f['mime'], 'pdf'));

        // Fournisseur explicitement choisi (s'il est disponible), sinon résolution auto.
        $provider = null;
        if ($providerKey !== null && $providerKey !== '' && $providerKey !== 'default') {
            $key = $providerKey === 'claude' ? 'anthropic' : $providerKey;
            $candidate = $this->manager->get($key);
            if ($candidate && $candidate->isAvailable() && (! $needsPdf || $candidate->supportsPdf())) {
                $provider = $candidate;
            }
        }
        $provider ??= $this->manager->resolve($needsPdf);
        if (! $provider) {
            throw new \RuntimeException('Aucun fournisseur d’IA n’est configuré (voir Réglages).');
        }

        $raw = $provider->extract($this->instruction($edition, $brief, $files !== []), $files);

        return $this->sanitize($raw);
    }

    protected function instruction(Edition $edition, string $brief, bool $hasExample): string
    {
        $s = (new EditionReport($edition))->summary();
        $cur = $s['currency'];

        $data = implode("\n", [
            '- Participants inscrits : '.$s['participants'],
            '- Communications déposées : '.$s['submissions'],
            '- Communications acceptées : '.$s['accepted'],
            '- Montant encaissé : '.$s['collected'].' '.$cur,
            '- Montant en attente : '.$s['pending'].' '.$cur,
        ]);

        $example = $hasExample
            ? 'Un exemple de rapport est joint : reproduis fidèlement sa STRUCTURE et son format (mêmes sections).'
            : 'Structure le rapport en sections claires (contexte, chiffres clés, analyse, recommandations).';

        $title = $edition->title ?: ('Édition '.$edition->year);

        return <<<TXT
        Tu es analyste à la Direction de la Recherche et de l'Innovation (DRI) de l'Université de Kara.
        Rédige un rapport factuel en français, à partir des DONNÉES RÉELLES ci-dessous. N'invente
        AUCUN chiffre ni fait : n'utilise que ces données (et l'exemple joint le cas échéant).

        Édition : {$title} ({$edition->year})
        Données de l'édition :
        {$data}

        {$example}

        Demande de l'administrateur : {$brief}

        Réponds UNIQUEMENT par un objet JSON STRICT (aucun texte autour) :
        { "title": string, "sections": [ { "heading": string, "body": string } ] }
        Le champ "body" peut contenir plusieurs paragraphes séparés par des retours à la ligne.
        N'utilise pas de HTML.
        TXT;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{title: string, sections: list<ReportSection>}
     */
    protected function sanitize(array $raw): array
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

        return [
            'title' => $title !== '' ? Str::limit($title, 200, '') : 'Rapport',
            'sections' => $sections,
        ];
    }
}
