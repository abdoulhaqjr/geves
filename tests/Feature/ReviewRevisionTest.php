<?php

use App\Actions\AssignReviewer;
use App\Mail\ReviewerInvitationMail;
use App\Mail\SubmissionStatusMail;
use App\Models\Account;
use App\Models\Participant;
use App\Models\Review;
use App\Models\Submission;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

// openEdition(), verifiedAccount() -> AccountSubmissionTest ; adminStaff(), seededSubmission() -> AdminSubmissionWorkflowTest

/* ----------------------- Resoumission / versionnage ----------------------- */

it('permet à l’auteur de resoumettre une version corrigée', function () {
    Mail::fake();
    $submission = seededSubmission('revision');
    $account = $submission->participant->user;

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.resubmit', $submission), [
            'title' => 'Titre corrigé',
            'abstract' => str_repeat('Nouveau contenu. ', 6),
        ])
        ->assertSessionHasNoErrors();

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
    $account = $submission->participant->user;

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.resubmit', $submission), [
            'title' => 'x',
            'abstract' => str_repeat('a', 60),
        ])
        ->assertForbidden();
});

it('interdit l’accès à la fiche d’une communication d’un autre compte', function () {
    $submission = seededSubmission();
    $other = User::create(['first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@example.tg', 'password' => 'pass12345']);

    $this->actingAs($other, 'account')
        ->get(route('account.submissions.show', $submission))
        ->assertNotFound();
});

/* ------------------------------- Relecture -------------------------------- */

it('assigne un relecteur, passe en relecture et l’invite par e-mail', function () {
    Mail::fake();
    $staff = adminStaff();
    $submission = seededSubmission('submitted');

    $this->actingAs($staff, 'web')->post(route('admin.submissions.review.assign', $submission), [
        'reviewerName' => 'Pr Kossi Anani',
        'reviewerEmail' => 'kossi@example.tg',
    ])->assertSessionHasNoErrors();

    expect($submission->fresh()->status)->toBe('under_review');

    $reviewer = Participant::where('email', 'kossi@example.tg')->first();
    expect($reviewer->role)->toBe('reviewer');
    expect($reviewer->access_token)->not->toBeEmpty();
    expect(Review::where('submission_id', $submission->id)->where('reviewer_id', $reviewer->id)->exists())->toBeTrue();

    Mail::assertQueued(ReviewerInvitationMail::class);
});

it('affiche les relectures confiées dans « Mes relectures » (en aveugle)', function () {
    $submission = seededSubmission('submitted');
    // Le relecteur a un compte (identité unifiée) : l'assignation le relie.
    $account = User::create(['first_name' => 'Kossi', 'last_name' => 'Anani', 'email' => 'kossi@example.tg', 'password' => 'pass12345']);
    (new AssignReviewer)->handle($submission, 'Kossi Anani', 'kossi@example.tg');

    $this->actingAs($account, 'account')
        ->get(route('account.reviews'))
        ->assertOk()
        ->assertSee($submission->ref_code)
        ->assertDontSee('Awa Kodjo'); // aveugle : pas d'auteur
});

it('enregistre une évaluation de relecteur depuis Mes relectures', function () {
    $submission = seededSubmission('under_review');
    $account = User::create(['first_name' => 'Kossi', 'last_name' => 'Anani', 'email' => 'kossi@example.tg', 'password' => 'pass12345']);
    $review = (new AssignReviewer)->handle($submission, 'Kossi Anani', 'kossi@example.tg');

    // Grille par défaut : toutes les notes à 4/5 → note globale dérivée 16/20.
    $this->actingAs($account, 'account')
        ->postJson(route('account.reviews.submit', $review), [
            'recommendation' => 'minor',
            'scores' => [
                'originality' => 4, 'rigor' => 4, 'clarity' => 4, 'relevance' => 4, 'contribution' => 4,
            ],
            'comments' => 'Bon travail, préciser la méthode.',
            'comments_private' => 'Solide dans l’ensemble.',
        ])->assertOk();

    $review->refresh();
    expect($review->status)->toBe('completed');
    expect($review->recommendation)->toBe('minor');
    expect((float) $review->score)->toBe(16.0);
    expect($review->criterionScores()['rigor'])->toBe(4);
});

it('révèle le retour des relecteurs à l’auteur après décision', function () {
    $submission = seededSubmission('under_review');
    $review = (new AssignReviewer)->handle($submission, 'Kossi Anani', 'kossi@example.tg');
    $review->update(['status' => 'completed', 'comments' => 'Merci de clarifier la section 2.']);
    $submission->update(['status' => 'revision']);
    $account = $submission->participant->user;

    $this->actingAs($account, 'account')
        ->get(route('account.submissions.show', $submission->fresh()))
        ->assertSee('Merci de clarifier la section 2.');
});
