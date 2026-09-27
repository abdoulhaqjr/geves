<?php

use App\Models\Review;
use App\Support\ReviewerExpertise;
use App\Support\ReviewGrid;
use Illuminate\Support\Facades\Mail;

// adminStaff() et seededSubmission() sont définis dans tests/Pest.php.

it('affiche l’aperçu d’attribution avec les relecteurs de l’édition', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted'); // axe « Sciences »
    $edition = $submission->edition;
    $edition->participants()->create([
        'first_name' => 'Kofi', 'last_name' => 'Mensah', 'email' => 'kofi@rev.tg',
        'role' => 'reviewer', 'meta' => ['review_tracks' => ['Sciences']],
    ]);

    $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.auto-assign', ['edition_id' => $edition->id, 'target' => 1]))
        ->assertOk()
        ->assertSee('Kofi Mensah')
        ->assertSee('Expertise sur l’axe');
});

it('applique un plan validé et met la soumission en relecture', function () {
    Mail::fake();
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $edition = $submission->edition;
    $reviewer = $edition->participants()->create([
        'first_name' => 'Kofi', 'last_name' => 'Mensah', 'email' => 'kofi@rev.tg',
        'role' => 'reviewer', 'meta' => ['review_tracks' => ['Sciences']],
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.submissions.auto-assign.apply'), [
            'edition_id' => $edition->id,
            'pairs' => [$submission->id.':'.$reviewer->id],
        ])
        ->assertRedirect();

    expect(Review::where('submission_id', $submission->id)->where('reviewer_id', $reviewer->id)->exists())->toBeTrue();
    expect($submission->fresh()->status)->toBe('under_review');
});

it('n’applique jamais un conflit d’intérêt élevé (même e-mail que l’auteur)', function () {
    Mail::fake();
    $staff = adminStaff();
    $submission = seededSubmission('submitted'); // auteur awa@example.tg
    $edition = $submission->edition;
    $conflicted = $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', // = auteur → COI élevé
        'role' => 'reviewer',
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.submissions.auto-assign.apply'), [
            'edition_id' => $edition->id,
            'pairs' => [$submission->id.':'.$conflicted->id],
        ])
        ->assertRedirect();

    expect(Review::where('reviewer_id', $conflicted->id)->exists())->toBeFalse();
});

it('met à jour l’expertise des relecteurs depuis l’onglet de l’édition', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $edition = $submission->edition;
    $reviewer = $edition->participants()->create([
        'first_name' => 'Ama', 'last_name' => 'Sow', 'email' => 'ama@rev.tg', 'role' => 'reviewer',
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.editions.reviewers.expertise', $edition), [
            'reviewer_ids' => [$reviewer->id],
            'expertise' => [$reviewer->id => ['Santé', 'Sciences']],
        ])
        ->assertRedirect();

    expect($reviewer->fresh()->meta['review_tracks'])->toContain('Santé')->toContain('Sciences');
});

it('ignore les identifiants qui ne sont pas des relecteurs de l’édition', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $edition = $submission->edition;
    $author = $submission->participant; // rôle auteur, pas relecteur

    $this->actingAs($staff, 'web')
        ->post(route('admin.editions.reviewers.expertise', $edition), [
            'reviewer_ids' => [$author->id],
            'expertise' => [$author->id => ['Sciences']],
        ])
        ->assertRedirect();

    expect($author->fresh()->meta['review_tracks'] ?? null)->toBeNull();
});

it('règle l’expertise depuis son onglet, plus depuis la fiche d’édition', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $edition = $submission->edition;
    $edition->participants()->create([
        'first_name' => 'Ama', 'last_name' => 'Sow', 'email' => 'ama@rev.tg', 'role' => 'reviewer',
    ]);

    // La matrice a son onglet dans le module Soumissions.
    $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.reviewers', ['edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('Relecteurs &amp; expertise', false)
        ->assertSee('Ama Sow')
        ->assertSee('Enregistrer les expertises');

    // La fiche d'édition n'a plus d'onglet Évaluation ni de matrice.
    $this->actingAs($staff, 'web')
        ->get(route('admin.editions.edit', $edition))
        ->assertOk()
        ->assertDontSee('Enregistrer les expertises')
        ->assertDontSee('Grille de notation');
});

it('regroupe les règles d’évaluation sur leur onglet', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $edition = $submission->edition;

    $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.options', ['edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('Relecteurs par soumission')
        ->assertSee('Charge maximale par relecteur')
        ->assertSee('Délai d’évaluation (jours)', false)
        ->assertSee('Grille de notation');
});

it('enregistre les règles du moteur avec la grille', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $edition = $submission->edition;

    $this->actingAs($staff, 'web')
        ->post(route('admin.editions.reviewers.criteria', $edition), [
            'review_target' => 3,
            'review_max_load' => 8,
            'review_due_days' => 12,
        ])
        ->assertRedirect(route('admin.submissions.options', ['edition_id' => $edition->id]));

    $edition->refresh();
    expect(ReviewGrid::target($edition))->toBe(3);
    expect(ReviewGrid::maxLoad($edition))->toBe(8);
    expect(ReviewGrid::dueDays($edition))->toBe(12);
});

it('applique les règles de l’édition au plan proposé, sans paramètre d’URL', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $edition = $submission->edition;
    $edition->update(['settings' => array_merge($edition->settings ?? [], ['review_target' => 4])]);

    $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.auto-assign', ['edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('4</strong> relecteur(s) par soumission', false);
});

it('revient sur l’onglet Relecteurs après avoir enregistré l’expertise', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $edition = $submission->edition;
    $reviewer = $edition->participants()->create([
        'first_name' => 'Ama', 'last_name' => 'Sow', 'email' => 'ama@rev.tg', 'role' => 'reviewer',
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.editions.reviewers.expertise', $edition), [
            'reviewer_ids' => [$reviewer->id],
            'expertise' => [$reviewer->id => ['Sciences']],
        ])
        ->assertRedirect(route('admin.submissions.reviewers', ['edition_id' => $edition->id]));
});

it('apparie un axe malgré un tiret ou une casse différents', function () {
    // L'expertise stocke une COPIE du libellé : un tiret cadratin recopié
    // désactivait silencieusement tout l'appariement par axe.
    expect(ReviewerExpertise::covers(
        ['Atelier 2 — Sciences naturelles'],
        'Atelier 2 - Sciences naturelles'
    ))->toBeTrue();

    expect(ReviewerExpertise::covers(
        ['  atelier 2 -  sciences   naturelles '],
        'Atelier 2 - Sciences naturelles'
    ))->toBeTrue();

    expect(ReviewerExpertise::covers(['Atelier 3'], 'Atelier 2'))->toBeFalse();
    expect(ReviewerExpertise::covers(['Atelier 2'], ''))->toBeFalse();
});

it('recanonise l’axe enregistré sur le libellé de l’édition', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $edition = $submission->edition; // axes : Sciences, Santé
    $reviewer = $edition->participants()->create([
        'first_name' => 'Ama', 'last_name' => 'Sow', 'email' => 'ama@rev.tg', 'role' => 'reviewer',
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.editions.reviewers.expertise', $edition), [
            'reviewer_ids' => [$reviewer->id],
            'expertise' => [$reviewer->id => ['  sciences  ']],
        ])->assertRedirect();

    // Stocké tel que l'édition l'écrit, pas tel que le formulaire l'a reçu.
    expect($reviewer->fresh()->meta['review_tracks'])->toBe(['Sciences']);
});
