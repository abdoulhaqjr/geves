<?php

use App\Actions\AssignReviewer;
use App\Mail\ReviewerInvitationMail;
use App\Mail\SubmissionStatusMail;
use App\Models\Participant;
use App\Models\Review;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

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

it('ajoute un modérateur comme évaluateur sans perdre sa fonction de modération', function () {
    Mail::fake();
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $event = $submission->event;
    $account = User::factory()->create([
        'first_name' => 'Mariam', 'last_name' => 'Modératrice', 'email' => 'moderateur2@test.tg',
    ]);
    $moderator = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Mariam', 'last_name' => 'Modératrice',
        'email' => $account->email, 'role' => 'moderator',
    ]);

    $this->actingAs($staff, 'web')->post(route('admin.evenements.reviewers.store', $event), [
        'reviewerUserId' => $account->id,
        'reviewerFirstName' => 'Mariam', 'reviewerLastName' => 'Modératrice',
        'reviewerEmail' => $account->email, 'reviewerTracks' => ['Sciences'],
    ])->assertSessionHasNoErrors();

    $reviewer = $event->participants()->where('role', 'reviewer')->where('user_id', $account->id)->firstOrFail();
    expect($moderator->fresh()->role)->toBe('moderator');
    expect($reviewer->id)->not->toBe($moderator->id);
    expect($reviewer->meta['review_tracks'])->toBe(['Sciences']);

    $review = (new AssignReviewer)->handle($submission, 'Mariam Modératrice', $account->email);
    expect($review->reviewer_id)->toBe($reviewer->id);
    expect($event->participants()->where('role', 'reviewer')->where('user_id', $account->id)->count())->toBe(1);
});

it('attribue directement une évaluation à un modérateur sans changer son rôle', function () {
    $submission = seededSubmission('submitted');
    $account = User::factory()->create([
        'first_name' => 'Mariam', 'last_name' => 'Modératrice', 'email' => 'moderateur2@test.tg',
    ]);
    $moderator = $submission->event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Mariam', 'last_name' => 'Modératrice',
        'email' => $account->email, 'role' => 'moderator',
    ]);

    $review = (new AssignReviewer)->handle($submission, 'Mariam Modératrice', $account->email);

    expect($moderator->fresh()->role)->toBe('moderator');
    expect($review->reviewer->role)->toBe('reviewer');
    expect($review->reviewer->id)->not->toBe($moderator->id);
    expect($review->reviewer->user_id)->toBe($account->id);
    $this->actingAs($account, 'account')->get(route('account.reviews'))->assertOk()->assertSee($submission->ref_code);
});

it('provisionne un compte relecteur (mot de passe à définir) à l’assignation', function () {
    $submission = seededSubmission('submitted');

    expect(User::whereRaw('LOWER(email) = ?', ['neuf@rev.tg'])->exists())->toBeFalse();

    $review = (new AssignReviewer)->handle($submission, 'Nadia Neuf', 'neuf@rev.tg');

    $user = User::whereRaw('LOWER(email) = ?', ['neuf@rev.tg'])->first();
    expect($user)->not->toBeNull();
    expect($user->must_reset_password)->toBeTrue();
    expect((int) $review->reviewer->user_id)->toBe((int) $user->id);
});

it('relie au compte existant sans en créer un second', function () {
    $submission = seededSubmission('submitted');
    $existing = User::create(['first_name' => 'Ex', 'last_name' => 'Ist', 'email' => 'exist@rev.tg', 'password' => 'pass12345']);

    (new AssignReviewer)->handle($submission, 'Ex Ist', 'exist@rev.tg');

    expect(User::whereRaw('LOWER(email) = ?', ['exist@rev.tg'])->count())->toBe(1);
    expect($existing->fresh()->must_reset_password)->toBeFalse();
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
