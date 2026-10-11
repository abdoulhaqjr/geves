<?php

use App\Enums\EventPhase;
use App\Models\Event;
use App\Models\EventSeries;

// openEdition() -> AccountSubmissionTest

function phaseEdition(array $attrs = []): Event
{
    $series = EventSeries::firstOrCreate(['slug' => 'ph'], ['name' => 'Phase']);

    return $series->events()->create(array_merge([
        'title' => 'Événement test',
        'slug' => 'ph-'.uniqid(),
        'status' => 'open',
        'year' => 2026,
    ], $attrs));
}

it('brouillon → phase Draft (aucune vue publique)', function () {
    $e = phaseEdition(['status' => 'draft']);

    expect($e->phase())->toBe(EventPhase::Draft);
    expect($e->phase()->publicView())->toBeNull();
});

it('appel ouvert avec dates futures = « à venir », pas « en cours » (piège §2.2)', function () {
    $e = phaseEdition([
        'submission_open' => true,
        'submission_deadline' => now()->addMonth(),
        'date_start' => now()->addMonths(3),
        'date_end' => now()->addMonths(3)->addDays(2),
    ]);

    expect($e->phase())->toBe(EventPhase::CallOpen);
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

    expect($e->phase())->toBe(EventPhase::UnderReview);
    expect($e->phase()->publicView())->toBe('upcoming');
});

it('pas d’appel actif avant l’événement → Inscriptions (registration)', function () {
    $e = phaseEdition([
        'submission_open' => false,
        'submission_deadline' => null,
        'date_start' => now()->addMonth(),
        'date_end' => now()->addMonth()->addDays(2),
    ]);

    expect($e->phase())->toBe(EventPhase::Registration);
    expect($e->phase()->publicView())->toBe('upcoming');
});

it('dates de l’événement atteintes → Ongoing (en cours)', function () {
    $e = phaseEdition([
        'date_start' => now()->subDay(),
        'date_end' => now()->addDay(),
    ]);

    expect($e->phase())->toBe(EventPhase::Ongoing);
    expect($e->phase()->publicView())->toBe('ongoing');
});

it('date de fin dépassée → Closed (passé)', function () {
    $e = phaseEdition([
        'date_start' => now()->subMonth(),
        'date_end' => now()->subMonth()->addDays(2),
    ]);

    expect($e->phase())->toBe(EventPhase::Closed);
    expect($e->phase()->publicView())->toBe('past');
});

it('statut archivé → Closed même sans dates', function () {
    $e = phaseEdition(['status' => 'archived', 'date_start' => null, 'date_end' => null]);

    expect($e->phase())->toBe(EventPhase::Closed);
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
        ->assertSee('Événements à venir')
        ->assertSee('Appel Ouvert 2027');
});
