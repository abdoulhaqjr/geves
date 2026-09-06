<?php

use App\Actions\AutoAssignReviewers;
use App\Models\Colloque;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\ReviewerBid;
use Illuminate\Support\Str;

// adminStaff() et seededSubmission() sont définis dans tests/Pest.php.

function reviewerOn(Edition $edition, string $email): Participant
{
    return $edition->participants()->create([
        'first_name' => 'Rel', 'last_name' => ucfirst(explode('@', $email)[0]),
        'email' => $email, 'role' => 'reviewer', 'access_token' => Str::random(64),
    ]);
}

it('affiche l’appel à candidatures et enregistre une préférence', function () {
    $submission = seededSubmission('submitted');
    $reviewer = reviewerOn($submission->edition, 'kofi@rev.tg');

    $this->get(route('reviewer.portal', $reviewer->access_token))
        ->assertOk()
        ->assertSee('Appel à candidatures')
        ->assertSee($submission->ref_code);

    $this->post(route('reviewer.bids.save', $reviewer->access_token), [
        'bids' => [$submission->id => ['interest' => 'interested']],
    ])->assertRedirect();

    expect(ReviewerBid::where('reviewer_id', $reviewer->id)->where('submission_id', $submission->id)->value('interest'))
        ->toBe('interested');
});

it('n’enregistre pas de candidature sur une soumission d’une autre édition', function () {
    $submission = seededSubmission('submitted');
    $reviewer = reviewerOn($submission->edition, 'kofi@rev.tg');

    // Soumission d'une AUTRE édition.
    $otherColloque = Colloque::create(['name' => 'Autre', 'slug' => 'autre']);
    $otherEdition = $otherColloque->editions()->create(['title' => 'A', 'year' => 2027, 'status' => 'open', 'slug' => 'a-2027']);
    $foreign = $otherEdition->submissions()->create(['ref_code' => 'X-1', 'title' => 'Hors édition', 'abstract' => 'x', 'track' => 'IA', 'type' => 'oral', 'status' => 'submitted', 'authors' => []]);

    $this->post(route('reviewer.bids.save', $reviewer->access_token), [
        'bids' => [$foreign->id => ['interest' => 'interested']],
    ])->assertRedirect();

    expect(ReviewerBid::where('submission_id', $foreign->id)->exists())->toBeFalse();
});

it('privilégie le relecteur volontaire dans l’attribution automatique', function () {
    $submission = seededSubmission('submitted');
    $edition = $submission->edition;
    $r1 = reviewerOn($edition, 'a@rev.tg');
    $r2 = reviewerOn($edition, 'b@rev.tg');
    ReviewerBid::create(['submission_id' => $submission->id, 'reviewer_id' => $r2->id, 'interest' => 'interested']);

    $preview = app(AutoAssignReviewers::class)->preview($edition, 1, 0);
    $proposal = collect($preview['proposals'])->firstWhere('submission_id', $submission->id);

    expect($proposal['reviewer_id'])->toBe($r2->id)
        ->and($proposal['reason'])->toContain('volontaire');
});

it('exclut de l’attribution un relecteur ayant déclaré un conflit', function () {
    $submission = seededSubmission('submitted');
    $edition = $submission->edition;
    $only = reviewerOn($edition, 'c@rev.tg');
    ReviewerBid::create(['submission_id' => $submission->id, 'reviewer_id' => $only->id, 'conflict' => true]);

    $preview = app(AutoAssignReviewers::class)->preview($edition, 1, 0);

    expect(collect($preview['proposals'])->where('reviewer_id', $only->id))->toBeEmpty()
        ->and($preview['uncovered'])->toBeGreaterThan(0);
});
