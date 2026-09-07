<?php

use App\Models\Edition;
use App\Models\Submission;
use App\Support\BibliographyExporter;

function bibEdition(): Edition
{
    return new Edition(['title' => 'Colloque UK 2026', 'year' => 2026, 'location' => 'Kara', 'slug' => 'uk-2026']);
}

function bibSubmission(array $over = []): Submission
{
    return new Submission(array_merge([
        'ref_code' => 'UKC-S-0001',
        'title' => 'IA & durabilité',
        'abstract' => 'Un résumé de test.',
        'keywords' => 'IA, durabilité',
        'track' => 'Sciences',
        'authors' => [['name' => 'Awa Kodjo'], ['name' => 'Kofi Mensah']],
    ], $over));
}

it('génère une entrée BibTeX conforme et échappée', function () {
    $out = (new BibliographyExporter)->bibtex([bibSubmission()], bibEdition());

    expect($out)->toContain('@inproceedings{UKC-S-0001,')
        ->and($out)->toContain('author = {Awa Kodjo and Kofi Mensah}')
        ->and($out)->toContain('title = {IA \& durabilité}') // & échappé
        ->and($out)->toContain('booktitle = {Colloque UK 2026}')
        ->and($out)->toContain('year = {2026}')
        ->and($out)->toContain('keywords = {IA, durabilité}');
});

it('génère un enregistrement RIS conforme', function () {
    $out = (new BibliographyExporter)->ris([bibSubmission()], bibEdition());

    expect($out)->toContain('TY  - CPAPER')
        ->and($out)->toContain('AU  - Awa Kodjo')
        ->and($out)->toContain('AU  - Kofi Mensah')
        ->and($out)->toContain('TI  - IA & durabilité')
        ->and($out)->toContain('PY  - 2026')
        ->and($out)->toContain('T2  - Colloque UK 2026')
        ->and($out)->toContain('ER  -');
});

it('retombe sur l’axe quand aucun mot-clé', function () {
    $out = (new BibliographyExporter)->bibtex([bibSubmission(['keywords' => null])], bibEdition());
    expect($out)->toContain('keywords = {Sciences}');
});
