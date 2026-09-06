<?php

use App\Support\ConflictOfInterest;
use App\Support\ReviewerAssignmentPlanner;

/**
 * @param  list<array{id:int, track:string, needed:int, exclude?:list<int>}>  $subs
 * @param  list<array{id:int, tracks:list<string>, load:int}>  $reviewers
 * @param  array<int, array<int, string|null>>  $coi
 * @return list<array{submission_id:int, reviewer_id:int, reason:string, coi:string|null, axis_match:bool}>
 */
function plan(array $subs, array $reviewers, array $coi = [], int $maxLoad = 0, array $bids = []): array
{
    return (new ReviewerAssignmentPlanner)->plan($subs, $reviewers, $coi, $maxLoad, $bids);
}

it('privilégie le relecteur dont l’expertise couvre l’axe', function () {
    $proposals = plan(
        [['id' => 1, 'track' => 'IA', 'needed' => 1]],
        [
            ['id' => 10, 'tracks' => ['Santé'], 'load' => 0],
            ['id' => 11, 'tracks' => ['IA'], 'load' => 0],
        ],
    );

    expect($proposals)->toHaveCount(1)
        ->and($proposals[0]['reviewer_id'])->toBe(11)
        ->and($proposals[0]['axis_match'])->toBeTrue();
});

it('exclut tout conflit d’intérêt élevé', function () {
    $proposals = plan(
        [['id' => 1, 'track' => 'IA', 'needed' => 1]],
        [
            ['id' => 10, 'tracks' => ['IA'], 'load' => 0],
            ['id' => 11, 'tracks' => ['IA'], 'load' => 0],
        ],
        [1 => [10 => ConflictOfInterest::HIGH]],
    );

    expect($proposals)->toHaveCount(1)
        ->and($proposals[0]['reviewer_id'])->toBe(11);
});

it('équilibre la charge à expertise égale', function () {
    $proposals = plan(
        [['id' => 1, 'track' => 'IA', 'needed' => 1]],
        [
            ['id' => 10, 'tracks' => ['IA'], 'load' => 3],
            ['id' => 11, 'tracks' => ['IA'], 'load' => 0],
        ],
    );

    expect($proposals[0]['reviewer_id'])->toBe(11); // le moins chargé
});

it('respecte le plafond de charge par relecteur', function () {
    $proposals = plan(
        [
            ['id' => 1, 'track' => 'IA', 'needed' => 1],
            ['id' => 2, 'track' => 'IA', 'needed' => 1],
            ['id' => 3, 'track' => 'IA', 'needed' => 1],
        ],
        [['id' => 10, 'tracks' => ['IA'], 'load' => 0]],
        [],
        maxLoad: 2,
    );

    // Un seul relecteur, plafond 2 → seules 2 soumissions couvertes.
    expect($proposals)->toHaveCount(2);
    $reviewerLoad = collect($proposals)->where('reviewer_id', 10)->count();
    expect($reviewerLoad)->toBe(2);
});

it('n’assigne pas deux fois le même relecteur à une soumission', function () {
    $proposals = plan(
        [['id' => 1, 'track' => 'IA', 'needed' => 1, 'exclude' => [10]]],
        [
            ['id' => 10, 'tracks' => ['IA'], 'load' => 0],
            ['id' => 11, 'tracks' => ['Santé'], 'load' => 0],
        ],
    );

    expect($proposals)->toHaveCount(1)
        ->and($proposals[0]['reviewer_id'])->toBe(11); // 10 exclu (déjà assigné)
});

it('affecte le nombre de relecteurs demandé par soumission', function () {
    $proposals = plan(
        [['id' => 1, 'track' => 'IA', 'needed' => 2]],
        [
            ['id' => 10, 'tracks' => ['IA'], 'load' => 0],
            ['id' => 11, 'tracks' => ['IA'], 'load' => 0],
            ['id' => 12, 'tracks' => ['Santé'], 'load' => 0],
        ],
    );

    expect($proposals)->toHaveCount(2);
    // Les deux experts de l'axe passent avant le hors-axe.
    expect(collect($proposals)->pluck('reviewer_id')->sort()->values()->all())->toBe([10, 11]);
});

it('privilégie un relecteur volontaire (candidature « intéressé »)', function () {
    $proposals = plan(
        [['id' => 1, 'track' => 'IA', 'needed' => 1]],
        [
            ['id' => 10, 'tracks' => [], 'load' => 0],
            ['id' => 11, 'tracks' => [], 'load' => 0],
        ],
        [],
        0,
        [1 => [11 => ['interest' => 'interested']]],
    );

    expect($proposals[0]['reviewer_id'])->toBe(11)
        ->and($proposals[0]['reason'])->toContain('volontaire');
});

it('ne propose pas un relecteur qui s’est déclaré indisponible', function () {
    $proposals = plan(
        [['id' => 1, 'track' => 'IA', 'needed' => 1]],
        [
            ['id' => 10, 'tracks' => ['IA'], 'load' => 0],
            ['id' => 11, 'tracks' => ['IA'], 'load' => 0],
        ],
        [],
        0,
        [1 => [10 => ['interest' => 'unavailable']]],
    );

    expect($proposals)->toHaveCount(1)->and($proposals[0]['reviewer_id'])->toBe(11);
});

it('exclut un relecteur ayant auto-déclaré un conflit', function () {
    $proposals = plan(
        [['id' => 1, 'track' => 'IA', 'needed' => 1]],
        [
            ['id' => 10, 'tracks' => ['IA'], 'load' => 0],
            ['id' => 11, 'tracks' => ['IA'], 'load' => 0],
        ],
        [],
        0,
        [1 => [10 => ['conflict' => true]]],
    );

    expect($proposals)->toHaveCount(1)->and($proposals[0]['reviewer_id'])->toBe(11);
});

it('ignore les soumissions déjà couvertes (needed = 0)', function () {
    $proposals = plan(
        [['id' => 1, 'track' => 'IA', 'needed' => 0]],
        [['id' => 10, 'tracks' => ['IA'], 'load' => 0]],
    );

    expect($proposals)->toBeEmpty();
});
