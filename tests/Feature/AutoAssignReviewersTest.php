<?php

use App\Models\Review;
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

it('met à jour l’expertise d’un relecteur', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $reviewer = $submission->edition->participants()->create([
        'first_name' => 'Ama', 'last_name' => 'Sow', 'email' => 'ama@rev.tg', 'role' => 'reviewer',
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.submissions.reviewer.expertise', $reviewer->id), ['tracks' => ['Santé', 'Sciences']])
        ->assertRedirect();

    expect($reviewer->fresh()->meta['review_tracks'])->toContain('Santé')->toContain('Sciences');
});

it('refuse la mise à jour d’expertise sur un non-relecteur', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $author = $submission->participant; // rôle auteur, pas relecteur

    $this->actingAs($staff, 'web')
        ->post(route('admin.submissions.reviewer.expertise', $author->id), ['tracks' => ['Sciences']])
        ->assertNotFound();
});
