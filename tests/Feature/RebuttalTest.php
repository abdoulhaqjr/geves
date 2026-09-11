<?php

use App\Models\Review;
use App\Models\Submission;

// seededSubmission() / adminStaff() : helpers partagés (tests/Pest.php).

function reviewedSubmission(): Submission
{
    $submission = seededSubmission('under_review');
    Review::create([
        'submission_id' => $submission->id,
        'reviewer_id' => null,
        'recommendation' => 'minor',
        'score' => 14,
        'comments' => 'Merci de préciser la méthodologie de la section 3.',
        'status' => 'completed',
    ]);

    return $submission;
}

it('ouvre puis referme la phase de réponse (admin)', function () {
    $submission = reviewedSubmission();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.submissions.rebuttal.toggle', $submission))
        ->assertRedirect();
    expect($submission->fresh()->rebuttalOpen())->toBeTrue();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.submissions.rebuttal.toggle', $submission))
        ->assertRedirect();
    expect($submission->fresh()->rebuttalOpen())->toBeFalse();
});

it('révèle les retours relecteurs à l’auteur pendant la phase de réponse', function () {
    $submission = reviewedSubmission();
    $account = $submission->participant->user;

    // Avant ouverture : commentaires masqués (statut under_review).
    $this->actingAs($account, 'account')
        ->get(route('account.submissions.show', $submission))
        ->assertDontSee('méthodologie de la section 3');

    // Ouverture de la phase.
    $submission->update(['meta' => ['rebuttal' => ['opened_at' => now()->toIso8601String()]]]);

    $this->actingAs($account, 'account')
        ->get(route('account.submissions.show', $submission->fresh()))
        ->assertSee('méthodologie de la section 3')
        ->assertSee('Votre réponse aux relecteurs');
});

it('enregistre la réponse de l’auteur tant que la phase est ouverte', function () {
    $submission = reviewedSubmission();
    $submission->update(['meta' => ['rebuttal' => ['opened_at' => now()->toIso8601String()]]]);
    $account = $submission->participant->user;

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.rebuttal', $submission), [
            'rebuttal' => 'Nous avons clarifié la méthodologie ; voir la nouvelle section 3.',
        ])
        ->assertRedirect();

    $fresh = $submission->fresh();
    expect($fresh->hasRebuttal())->toBeTrue();
    expect($fresh->rebuttalText())->toContain('clarifié la méthodologie');
});

it('refuse la réponse de l’auteur si la phase est fermée', function () {
    $submission = reviewedSubmission(); // phase non ouverte
    $account = $submission->participant->user;

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.rebuttal', $submission), ['rebuttal' => 'Réponse'])
        ->assertForbidden();

    expect($submission->fresh()->hasRebuttal())->toBeFalse();
});
