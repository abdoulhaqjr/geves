<?php

use App\Actions\AutoAssignReviewers;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Participant;
use App\Models\ReviewerBid;
use App\Models\User;
use Illuminate\Support\Str;

// adminStaff() et seededSubmission() sont définis dans tests/Pest.php.

function reviewerOn(Event $event, string $email): Participant
{
    $user = User::firstOrCreate(
        ['email' => $email],
        ['first_name' => 'Rel', 'last_name' => ucfirst(explode('@', $email)[0]), 'password' => 'pass12345']
    );
    $user->markEmailAsVerified();

    return $event->participants()->create([
        'user_id' => $user->id,
        'first_name' => 'Rel', 'last_name' => ucfirst(explode('@', $email)[0]),
        'email' => $email, 'role' => 'reviewer', 'access_token' => Str::random(64),
    ]);
}

it('affiche l’appel à candidatures et enregistre une préférence', function () {
    $submission = seededSubmission('submitted');
    $reviewer = reviewerOn($submission->event, 'kofi@rev.tg');

    // L'appel à candidatures est un onglet à part (?tab=candidatures) : depuis
    // « Mes relectures », on voit le lien ; la liste, elle, est sur l'onglet.
    $this->actingAs($reviewer->user, 'account')
        ->get(route('account.reviews'))
        ->assertOk()
        ->assertSee('Appel à candidatures');

    $this->actingAs($reviewer->user, 'account')
        ->get(route('account.reviews', ['tab' => 'candidatures']))
        ->assertOk()
        ->assertSee($submission->ref_code);

    $this->actingAs($reviewer->user, 'account')
        ->post(route('account.reviews.bids'), [
            'bids' => [$submission->id => ['interest' => 'interested']],
        ])->assertRedirect();

    expect(ReviewerBid::where('reviewer_id', $reviewer->id)->where('submission_id', $submission->id)->value('interest'))
        ->toBe('interested');
});

it('n’enregistre pas de candidature sur une soumission d’une autre événement', function () {
    $submission = seededSubmission('submitted');
    $reviewer = reviewerOn($submission->event, 'kofi@rev.tg');

    // Soumission d'une AUTRE événement.
    $otherColloque = EventSeries::create(['name' => 'Autre', 'slug' => 'autre']);
    $otherEdition = $otherColloque->events()->create(['title' => 'A', 'year' => 2027, 'status' => 'open', 'slug' => 'a-2027']);
    $foreign = $otherEdition->submissions()->create(['ref_code' => 'X-1', 'title' => 'Hors événement', 'abstract' => 'x', 'track' => 'IA', 'type' => 'oral', 'status' => 'submitted', 'authors' => []]);

    $this->actingAs($reviewer->user, 'account')
        ->post(route('account.reviews.bids'), [
            'bids' => [$foreign->id => ['interest' => 'interested']],
        ])->assertRedirect();

    expect(ReviewerBid::where('submission_id', $foreign->id)->exists())->toBeFalse();
});

it('privilégie le relecteur volontaire dans l’attribution automatique', function () {
    $submission = seededSubmission('submitted');
    $event = $submission->event;
    $r1 = reviewerOn($event, 'a@rev.tg');
    $r2 = reviewerOn($event, 'b@rev.tg');
    ReviewerBid::create(['submission_id' => $submission->id, 'reviewer_id' => $r2->id, 'interest' => 'interested']);

    $preview = app(AutoAssignReviewers::class)->preview($event, 1, 0);
    $proposal = collect($preview['proposals'])->firstWhere('submission_id', $submission->id);

    expect($proposal['reviewer_id'])->toBe($r2->id)
        ->and($proposal['reason'])->toContain('volontaire');
});

it('exclut de l’attribution un relecteur ayant déclaré un conflit', function () {
    $submission = seededSubmission('submitted');
    $event = $submission->event;
    $only = reviewerOn($event, 'c@rev.tg');
    ReviewerBid::create(['submission_id' => $submission->id, 'reviewer_id' => $only->id, 'conflict' => true]);

    $preview = app(AutoAssignReviewers::class)->preview($event, 1, 0);

    expect(collect($preview['proposals'])->where('reviewer_id', $only->id))->toBeEmpty()
        ->and($preview['uncovered'])->toBeGreaterThan(0);
});
