<?php

use App\Enums\EditionPhase;
use App\Models\Colloque;
use App\Models\Edition;

// openEdition() -> AccountSubmissionTest

function phaseEdition(array $attrs = []): Edition
{
    $colloque = Colloque::firstOrCreate(['slug' => 'ph'], ['name' => 'Phase']);

    return $colloque->editions()->create(array_merge([
        'title' => 'Édition test',
        'slug' => 'ph-'.uniqid(),
        'status' => 'open',
        'year' => 2026,
    ], $attrs));
}

it('brouillon → phase Draft (aucune vue publique)', function () {
    $e = phaseEdition(['status' => 'draft']);

    expect($e->phase())->toBe(EditionPhase::Draft);
    expect($e->phase()->publicView())->toBeNull();
});

it('appel ouvert avec dates futures = « à venir », pas « en cours » (piège §2.2)', function () {
    $e = phaseEdition([
        'submission_open' => true,
        'submission_deadline' => now()->addMonth(),
        'date_start' => now()->addMonths(3),
        'date_end' => now()->addMonths(3)->addDays(2),
    ]);

    expect($e->phase())->toBe(EditionPhase::CallOpen);
    expect($e->phase()->publicView())->toBe('upcoming');
    expect($e->phase()->isOngoing())->toBeFalse();
});

it('soumissions closes avant l’événement → Évaluation (under_review)', function () {
    $e = phaseEdition([
        'submission_open' => false,
        'submission_deadline' => now()->subWeek(),
        'date_start' => now()->addMonth(),
        'date_end' => now()->addMonth()->addDays(2),
    ]);

    expect($e->phase())->toBe(EditionPhase::UnderReview);
    expect($e->phase()->publicView())->toBe('upcoming');
});

it('pas d’appel actif avant l’événement → Inscriptions (registration)', function () {
    $e = phaseEdition([
        'submission_open' => false,
        'submission_deadline' => null,
        'date_start' => now()->addMonth(),
        'date_end' => now()->addMonth()->addDays(2),
    ]);

    expect($e->phase())->toBe(EditionPhase::Registration);
    expect($e->phase()->publicView())->toBe('upcoming');
});

it('dates de l’événement atteintes → Ongoing (en cours)', function () {
    $e = phaseEdition([
        'date_start' => now()->subDay(),
        'date_end' => now()->addDay(),
    ]);

    expect($e->phase())->toBe(EditionPhase::Ongoing);
    expect($e->phase()->publicView())->toBe('ongoing');
});

it('date de fin dépassée → Closed (passé)', function () {
    $e = phaseEdition([
        'date_start' => now()->subMonth(),
        'date_end' => now()->subMonth()->addDays(2),
    ]);

    expect($e->phase())->toBe(EditionPhase::Closed);
    expect($e->phase()->publicView())->toBe('past');
});

it('statut archivé → Closed même sans dates', function () {
    $e = phaseEdition(['status' => 'archived', 'date_start' => null, 'date_end' => null]);

    expect($e->phase())->toBe(EditionPhase::Closed);
});

it('l’accueil range un appel ouvert dans « à venir »', function () {
    phaseEdition([
        'title' => 'Appel Ouvert 2027',
        'submission_open' => true,
        'submission_deadline' => now()->addMonth(),
        'date_start' => now()->addMonths(4),
        'date_end' => now()->addMonths(4)->addDays(2),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Colloques à venir')
        ->assertSee('Appel Ouvert 2027');
});
