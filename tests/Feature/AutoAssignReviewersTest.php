<?php

use App\Models\Review;
use App\Support\ReviewerExpertise;
use App\Support\ReviewGrid;
use Illuminate\Support\Facades\Mail;

// adminStaff() et seededSubmission() sont définis dans tests/Pest.php.

it('affiche l’aperçu d’attribution avec les relecteurs de l’événement', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted'); // axe « Sciences »
    $event = $submission->event;
    $event->participants()->create([
        'first_name' => 'Kofi', 'last_name' => 'Mensah', 'email' => 'kofi@rev.tg',
        'role' => 'reviewer', 'meta' => ['review_tracks' => ['Sciences']],
    ]);

    $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.auto-assign', ['event_id' => $event->id, 'target' => 1]))
        ->assertOk()
        ->assertSee('Kofi MENSAH')
        ->assertSee('Expertise sur l’axe');
});

it('applique un plan validé et met la soumission en relecture', function () {
    Mail::fake();
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $event = $submission->event;
    $reviewer = $event->participants()->create([
        'first_name' => 'Kofi', 'last_name' => 'Mensah', 'email' => 'kofi@rev.tg',
        'role' => 'reviewer', 'meta' => ['review_tracks' => ['Sciences']],
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.submissions.auto-assign.apply'), [
            'event_id' => $event->id,
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
    $event = $submission->event;
    $conflicted = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', // = auteur → COI élevé
        'role' => 'reviewer',
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.submissions.auto-assign.apply'), [
            'event_id' => $event->id,
            'pairs' => [$submission->id.':'.$conflicted->id],
        ])
        ->assertRedirect();

    expect(Review::where('reviewer_id', $conflicted->id)->exists())->toBeFalse();
});

it('met à jour l’expertise des relecteurs depuis l’onglet de l’événement', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $event = $submission->event;
    $reviewer = $event->participants()->create([
        'first_name' => 'Ama', 'last_name' => 'Sow', 'email' => 'ama@rev.tg', 'role' => 'reviewer',
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.evenements.reviewers.expertise', $event), [
            'reviewer_ids' => [$reviewer->id],
            'expertise' => [$reviewer->id => ['Santé', 'Sciences']],
        ])
        ->assertRedirect();

    expect($reviewer->fresh()->meta['review_tracks'])->toContain('Santé')->toContain('Sciences');
});

it('ignore les identifiants qui ne sont pas des relecteurs de l’événement', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $event = $submission->event;
    $author = $submission->participant; // rôle auteur, pas relecteur

    $this->actingAs($staff, 'web')
        ->post(route('admin.evenements.reviewers.expertise', $event), [
            'reviewer_ids' => [$author->id],
            'expertise' => [$author->id => ['Sciences']],
        ])
        ->assertRedirect();

    expect($author->fresh()->meta['review_tracks'] ?? null)->toBeNull();
});

it('règle l’expertise depuis son onglet, plus depuis la fiche d’événement', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $event = $submission->event;
    $event->participants()->create([
        'first_name' => 'Ama', 'last_name' => 'Sow', 'email' => 'ama@rev.tg', 'role' => 'reviewer',
    ]);

    // La matrice a son onglet dans le module Soumissions.
    $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.reviewers', ['event_id' => $event->id]))
        ->assertOk()
        ->assertSee('Évaluateurs &amp; expertise', false)
        ->assertSee('Ama SOW')
        ->assertSee('Enregistrer les expertises');

    // La fiche d'événement n'a plus d'onglet Évaluation ni de matrice.
    $this->actingAs($staff, 'web')
        ->get(route('admin.evenements.edit', $event))
        ->assertOk()
        ->assertDontSee('Enregistrer les expertises')
        ->assertDontSee('Grille de notation');
});

it('regroupe les règles d’évaluation sur leur onglet', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $event = $submission->event;

    $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.options', ['event_id' => $event->id]))
        ->assertOk()
        ->assertSee('Évaluateurs par soumission')
        ->assertSee('Charge maximale par évaluateur')
        ->assertSee('Délai d’évaluation (jours)', false)
        ->assertSee('Grille de notation');
});

it('enregistre les règles du moteur avec la grille', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $event = $submission->event;

    $this->actingAs($staff, 'web')
        ->post(route('admin.evenements.reviewers.criteria', $event), [
            'review_target' => 3,
            'review_max_load' => 8,
            'review_due_days' => 12,
        ])
        ->assertRedirect(route('admin.submissions.options', ['event_id' => $event->id]));

    $event->refresh();
    expect(ReviewGrid::target($event))->toBe(3);
    expect(ReviewGrid::maxLoad($event))->toBe(8);
    expect(ReviewGrid::dueDays($event))->toBe(12);
});

it('applique les règles de l’événement au plan proposé, sans paramètre d’URL', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $event = $submission->event;
    $event->update(['settings' => array_merge($event->settings ?? [], ['review_target' => 4])]);

    $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.auto-assign', ['event_id' => $event->id]))
        ->assertOk()
        ->assertSee('4</strong> évaluateur(s) par soumission', false);
});

it('revient sur l’onglet Relecteurs après avoir enregistré l’expertise', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $event = $submission->event;
    $reviewer = $event->participants()->create([
        'first_name' => 'Ama', 'last_name' => 'Sow', 'email' => 'ama@rev.tg', 'role' => 'reviewer',
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.evenements.reviewers.expertise', $event), [
            'reviewer_ids' => [$reviewer->id],
            'expertise' => [$reviewer->id => ['Sciences']],
        ])
        ->assertRedirect(route('admin.submissions.reviewers', ['event_id' => $event->id]));
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

it('recanonise l’axe enregistré sur le libellé de l’événement', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $event = $submission->event; // axes : Sciences, Santé
    $reviewer = $event->participants()->create([
        'first_name' => 'Ama', 'last_name' => 'Sow', 'email' => 'ama@rev.tg', 'role' => 'reviewer',
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.evenements.reviewers.expertise', $event), [
            'reviewer_ids' => [$reviewer->id],
            'expertise' => [$reviewer->id => ['  sciences  ']],
        ])->assertRedirect();

    // Stocké tel que l'événement l'écrit, pas tel que le formulaire l'a reçu.
    expect($reviewer->fresh()->meta['review_tracks'])->toBe(['Sciences']);
});
