<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Submission;
use Illuminate\Support\Str;

/**
 * Export bibliographique des communications d'un événement aux formats citables
 * BibTeX (.bib) et RIS (.ris) - gratuit, sans dépendance. Chaque communication
 * devient une entrée « conference paper », prête à importer dans Zotero,
 * Mendeley, EndNote, un gestionnaire LaTeX, etc.
 */
class BibliographyExporter
{
    /**
     * @param  iterable<Submission>  $submissions
     */
    public function bibtex(iterable $submissions, Event $event): string
    {
        $booktitle = $this->booktitle($event);
        $year = $this->year($event);
        $out = [];

        foreach ($submissions as $s) {
            $fields = [
                'author' => $this->authorList($s),
                'title' => (string) $s->title,
                'booktitle' => $booktitle,
                'year' => $year,
                'address' => (string) ($event->location ?? ''),
                'keywords' => $this->keywords($s),
                'abstract' => trim(strip_tags((string) $s->abstract)),
            ];

            $lines = ['@inproceedings{'.$this->citeKey($s, $year).','];
            foreach ($fields as $key => $value) {
                if ($value !== '') {
                    $lines[] = '  '.$key.' = {'.$this->escapeBibtex($value).'},';
                }
            }
            $lines[] = '}';
            $out[] = implode("\n", $lines);
        }

        return implode("\n\n", $out)."\n";
    }

    /**
     * @param  iterable<Submission>  $submissions
     */
    public function ris(iterable $submissions, Event $event): string
    {
        $booktitle = $this->booktitle($event);
        $year = $this->year($event);
        $records = [];

        foreach ($submissions as $s) {
            $lines = ['TY  - CPAPER'];
            foreach ($this->authors($s) as $author) {
                $lines[] = 'AU  - '.$author;
            }
            $lines[] = 'TI  - '.(string) $s->title;
            if ($year !== '') {
                $lines[] = 'PY  - '.$year;
            }
            $lines[] = 'T2  - '.$booktitle;
            if (($loc = (string) ($event->location ?? '')) !== '') {
                $lines[] = 'CY  - '.$loc;
            }
            foreach ($this->keywordList($s) as $kw) {
                $lines[] = 'KW  - '.$kw;
            }
            if (($ab = trim(strip_tags((string) $s->abstract))) !== '') {
                $lines[] = 'AB  - '.$ab;
            }
            $lines[] = 'ER  - ';
            $records[] = implode("\r\n", $lines);
        }

        return implode("\r\n", $records)."\r\n";
    }

    /** Clé de citation : réf. normalisée, sinon nom + année. */
    private function citeKey(Submission $s, string $year): string
    {
        $base = (string) ($s->ref_code ?: '');
        if ($base === '') {
            $last = Str::of($this->authors($s)[0] ?? 'ukc')->explode(' ')->last();
            $base = $last.$year;
        }

        return preg_replace('/[^A-Za-z0-9_-]+/', '', str_replace(' ', '', $base)) ?: 'ukc';
    }

    /** Auteurs joints pour BibTeX (« A and B and C »). */
    private function authorList(Submission $s): string
    {
        return implode(' and ', $this->authors($s));
    }

    /**
     * Noms d'auteurs (repli sur l'auteur correspondant / participant).
     *
     * @return list<string>
     */
    private function authors(Submission $s): array
    {
        $names = collect($s->authors ?? [])
            ->map(fn ($a) => trim((string) ($a['name'] ?? '')))
            ->filter()
            ->values()
            ->all();

        if ($names === [] && $s->participant) {
            $names = [$s->participant->full_name];
        }

        return $names;
    }

    private function keywords(Submission $s): string
    {
        return implode(', ', $this->keywordList($s));
    }

    /** @return list<string> */
    private function keywordList(Submission $s): array
    {
        $raw = (string) ($s->keywords ?? '');
        if ($raw === '' && $s->track) {
            $raw = (string) $s->track;
        }

        return collect(preg_split('/[,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn ($k) => trim($k))->filter()->values()->all();
    }

    private function booktitle(Event $event): string
    {
        return (string) ($event->title ?: $event->series?->name ?: 'Événement');
    }

    private function year(Event $event): string
    {
        return (string) ($event->year ?: $event->date_start?->format('Y') ?: '');
    }

    /** Échappe le minimum pour BibTeX (caractères réservés). */
    private function escapeBibtex(string $value): string
    {
        return str_replace(
            ['\\', '{', '}', '&', '%', '$', '#', '_'],
            ['\\textbackslash{}', '\\{', '\\}', '\\&', '\\%', '\\$', '\\#', '\\_'],
            $value,
        );
    }
}
