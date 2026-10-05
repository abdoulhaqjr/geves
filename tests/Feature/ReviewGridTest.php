<?php

use App\Support\ReviewGrid;

// openEdition() / adminStaff() : helpers partagés (tests/Pest.php).

it('fournit une grille par défaut et calcule la note globale /20', function () {
    $criteria = ReviewGrid::criteria(null);
    expect($criteria)->toBe(ReviewGrid::DEFAULTS);

    // Moyenne des notes à poids égaux, ramenée sur 20.
    $scores = ['originality' => 5, 'rigor' => 5, 'clarity' => 5, 'relevance' => 5, 'contribution' => 5];
    expect(ReviewGrid::overall($scores, $criteria))->toBe(20.0);

    expect(ReviewGrid::overall(['originality' => 4, 'rigor' => 2], $criteria))->toBe(12.0); // moy 3 → 12
    expect(ReviewGrid::overall([], $criteria))->toBeNull();
});

it('borne les notes et ignore les critères hors grille', function () {
    $criteria = [['key' => 'a', 'label' => 'A'], ['key' => 'b', 'label' => 'B']];
    $clean = ReviewGrid::sanitizeScores(['a' => 9, 'b' => -3, 'zzz' => 2], $criteria);
    expect($clean)->toBe(['a' => ReviewGrid::SCALE, 'b' => 0]);
});

it('normalise une grille personnalisée et retombe sur le défaut si vide', function () {
    $norm = ReviewGrid::normalize([['label' => 'Impact sociétal'], ['label' => '  '], ['label' => 'Impact sociétal']]);
    expect($norm)->toHaveCount(1);
    expect($norm[0]['label'])->toBe('Impact sociétal');
    expect($norm[0]['key'])->toMatch('/^[a-z0-9_]+$/'); // slug non vide, stable

    expect(ReviewGrid::normalize([]))->toBe(ReviewGrid::DEFAULTS);
    expect(ReviewGrid::normalize(null))->toBe(ReviewGrid::DEFAULTS);
});

it('lit la grille personnalisée d’une événement', function () {
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'review_criteria' => [['key' => 'x', 'label' => 'Critère X']],
    ])]);

    $criteria = ReviewGrid::criteria($event->fresh());
    expect($criteria)->toHaveCount(1);
    expect($criteria[0]['label'])->toBe('Critère X');
});

it('enregistre la grille de l’événement depuis le back-office', function () {
    $event = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.evenements.reviewers.criteria', $event), [
            'criteria' => ['Originalité', 'Clarté', ''],
        ])
        ->assertRedirect();

    $criteria = ReviewGrid::criteria($event->fresh());
    expect($criteria)->toHaveCount(2);
    expect(collect($criteria)->pluck('label')->all())->toBe(['Originalité', 'Clarté']);
});
