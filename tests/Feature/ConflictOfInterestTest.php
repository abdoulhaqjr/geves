<?php

use App\Models\Submission;
use App\Support\ConflictOfInterest;

// openEdition() -> AccountSubmissionTest

function coiSubmission(array $authors, array $attrs = []): Submission
{
    $edition = openEdition();

    return $edition->submissions()->create(array_merge([
        'title' => 'Étude sur la nutrition',
        'ref_code' => 'AA-'.random_int(100, 999),
        'status' => 'submitted',
        'type' => 'oral',
        'authors' => $authors,
        'revision_of' => 0,
        'version' => 1,
    ], $attrs));
}

$authors = [
    ['name' => 'Awa Kodjo', 'email' => 'awa@uk.tg', 'affiliation' => 'Université de Kara', 'corresponding' => true],
    ['name' => 'Jean Koffi', 'email' => 'jean@up.bj', 'affiliation' => 'Université de Parakou'],
];

it('signale un relecteur qui est auteur de la communication', function () use ($authors) {
    $s = coiSubmission($authors);

    $c = ConflictOfInterest::check($s, 'Awa Kodjo', 'awa@uk.tg', 'Université de Kara');

    expect(collect($c)->pluck('label'))->toContain('Auteur de la communication');
    expect(ConflictOfInterest::worstLevel($c))->toBe(ConflictOfInterest::HIGH);
});

it('signale la même institution qu’un auteur', function () use ($authors) {
    $s = coiSubmission($authors);

    $c = ConflictOfInterest::check($s, 'Sika Bamana', 'sika@ailleurs.tg', 'université de kara'); // casse/espaces différents

    expect(collect($c)->firstWhere('label', 'Même institution'))->not->toBeNull();
    expect(ConflictOfInterest::worstLevel($c))->toBe(ConflictOfInterest::HIGH);
});

it('signale une homonymie de nom (lien familial possible) en niveau faible', function () use ($authors) {
    $s = coiSubmission($authors);

    $c = ConflictOfInterest::check($s, 'Yao Koffi', 'yao@ailleurs.tg', 'Autre institut');

    $hit = collect($c)->firstWhere('label', 'Homonymie de nom');
    expect($hit)->not->toBeNull();
    expect($hit['level'])->toBe(ConflictOfInterest::LOW);
});

it('signale une co-publication récente avec un auteur', function () use ($authors) {
    $s = coiSubmission($authors);
    // Autre communication co-signée par le relecteur ET un auteur de $s.
    coiSubmission([
        ['name' => 'Marie Tchalla', 'email' => 'marie@x.tg'],
        ['name' => 'Awa Kodjo', 'email' => 'awa@uk.tg'],
    ], ['title' => 'Papier conjoint 2024', 'ref_code' => 'AA-777']);

    $c = ConflictOfInterest::check($s, 'Marie Tchalla', 'marie@x.tg', 'Institut Z');

    $hit = collect($c)->firstWhere('label', 'Co-publication récente');
    expect($hit)->not->toBeNull();
    expect($hit['level'])->toBe(ConflictOfInterest::MEDIUM);
    expect($hit['detail'])->toContain('Awa Kodjo');
});

it('ne signale aucun conflit pour un relecteur indépendant', function () use ($authors) {
    $s = coiSubmission($authors);

    $c = ConflictOfInterest::check($s, 'Paul Independent', 'paul@institut-x.fr', 'Institut X');

    expect($c)->toBe([]);
    expect(ConflictOfInterest::worstLevel($c))->toBeNull();
});
