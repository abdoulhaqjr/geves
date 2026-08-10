<?php

use App\Actions\AssignReviewer;
use App\Livewire\Account\Submissions\Show as AccountSubmissionShow;
use App\Livewire\Admin\Submissions\Show as AdminSubmissionShow;
use App\Livewire\Reviewer\Evaluate;
use App\Livewire\Reviewer\Portal;
use App\Mail\ReviewerInvitationMail;
use App\Mail\SubmissionStatusMail;
use App\Models\Account;
use App\Models\Participant;
use App\Models\Review;
use App\Models\Submission;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

// openEdition(), verifiedAccount() -> AccountSubmissionTest ; adminStaff(), seededSubmission() -> AdminSubmissionWorkflowTest

/* ----------------------- Resoumission / versionnage ----------------------- */

it('permet à l’auteur de resoumettre une version corrigée', function () {
    Mail::fake();
    $submission = seededSubmission('revision');
    $account = $submission->participant->account;

    Livewire::actingAs($account, 'account')
        ->test(AccountSubmissionShow::class, ['submission' => $submission])
        ->set('title', 'Titre corrigé')
        ->set('abstract', str_repeat('Nouveau contenu. ', 6))
        ->call('resubmit')
        ->assertHasNoErrors();

    $canonical = $submission->fresh();
    expect($canonical->title)->toBe('Titre corrigé');
    expect($canonical->version)->toBe(2);
    expect($canonical->status)->toBe('submitted');

    // Une archive de version a été créée (revision_of = canonique).
    $archive = Submission::where('revision_of', $submission->id)->first();
    expect($archive)->not->toBeNull();
    expect($archive->version)->toBe(1);

    Mail::assertQueued(SubmissionStatusMail::class, fn ($m) => $m->statusKey === 'submitted');
});

it('bloque la resoumission si aucune correction n’est demandée', function () {
    $submission = seededSubmission('under_review');
    $account = $submission->participant->account;

    Livewire::actingAs($account, 'account')
        ->test(AccountSubmissionShow::class, ['submission' => $submission])
        ->call('resubmit')
        ->assertStatus(403);
});

it('interdit l’accès à la fiche d’une communication d’un autre compte', function () {
    $submission = seededSubmission();
    $other = Account::create(['first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@example.tg', 'password' => 'pass12345']);

    Livewire::actingAs($other, 'account')
        ->test(AccountSubmissionShow::class, ['submission' => $submission])
        ->assertStatus(404);
});

/* ------------------------------- Relecture -------------------------------- */

it('assigne un relecteur, passe en relecture et l’invite par e-mail', function () {
    Mail::fake();
    $staff = adminStaff();
    $submission = seededSubmission('submitted');

    Livewire::actingAs($staff, 'web')
        ->test(AdminSubmissionShow::class, ['submission' => $submission])
        ->set('reviewerName', 'Pr Kossi Anani')
        ->set('reviewerEmail', 'kossi@example.tg')
        ->call('assignReviewer')
        ->assertHasNoErrors();

    expect($submission->fresh()->status)->toBe('under_review');

    $reviewer = Participant::where('email', 'kossi@example.tg')->first();
    expect($reviewer->role)->toBe('reviewer');
    expect($reviewer->access_token)->not->toBeEmpty();
    expect(Review::where('submission_id', $submission->id)->where('reviewer_id', $reviewer->id)->exists())->toBeTrue();

    Mail::assertQueued(ReviewerInvitationMail::class);
});

it('donne accès au portail relecteur par jeton et le refuse sinon', function () {
    $submission = seededSubmission('submitted');
    $reviewer = (new AssignReviewer)->handle($submission, 'Kossi Anani', 'kossi@example.tg');

    Livewire::test(Portal::class, ['token' => $reviewer->reviewer->access_token])
        ->assertOk()
        ->assertSee($submission->ref_code)
        ->assertDontSee('Awa Kodjo'); // aveugle : pas d'auteur

    Livewire::test(Portal::class, ['token' => 'jeton-invalide'])->assertStatus(404);
});

it('enregistre une évaluation de relecteur', function () {
    $submission = seededSubmission('under_review');
    $review = (new AssignReviewer)->handle($submission, 'Kossi Anani', 'kossi@example.tg');
    $reviewer = $review->reviewer;

    Livewire::test(Evaluate::class, ['token' => $reviewer->access_token, 'review' => $review])
        ->assertDontSee('Awa Kodjo')
        ->set('recommendation', 'minor')
        ->set('score', '15.5')
        ->set('comments', 'Bon travail, préciser la méthode.')
        ->set('comments_private', 'Solide dans l’ensemble.')
        ->call('submitEvaluation')
        ->assertHasNoErrors();

    $review->refresh();
    expect($review->status)->toBe('completed');
    expect($review->recommendation)->toBe('minor');
    expect((float) $review->score)->toBe(15.5);
});

it('révèle le retour des relecteurs à l’auteur après décision', function () {
    $submission = seededSubmission('under_review');
    $review = (new AssignReviewer)->handle($submission, 'Kossi Anani', 'kossi@example.tg');
    $review->update(['status' => 'completed', 'comments' => 'Merci de clarifier la section 2.']);
    $submission->update(['status' => 'revision']);
    $account = $submission->participant->account;

    Livewire::actingAs($account, 'account')
        ->test(AccountSubmissionShow::class, ['submission' => $submission->fresh()])
        ->assertSee('Merci de clarifier la section 2.');
});
