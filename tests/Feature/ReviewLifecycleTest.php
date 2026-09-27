<?php

use App\Actions\AssignReviewer;
use App\Models\Participant;
use App\Models\Review;
use App\Models\Submission;
use App\Models\User;
use App\Support\ConflictOfInterest;
use App\Support\EditionReport;
use App\Support\ReviewGrid;

// openEdition()/verifiedAccount()/adminStaff()/seededSubmission() -> tests/Pest.php

/** Relecteur inscrit sur l'édition d'une soumission, relié à un compte. */
function reviewerFor(Submission $submission, string $email = 'relecteur@example.tg'): Participant
{
    $account = User::create([
        'name' => 'Kossi Adjo', 'first_name' => 'Kossi', 'last_name' => 'Adjo',
        'email' => $email, 'password' => 'pass12345', 'phone' => '+22891111111',
        'affiliation' => 'Université de Kara',
    ]);
    $account->markEmailAsVerified();

    return Participant::create([
        'edition_id' => $submission->edition_id, 'user_id' => $account->id,
        'first_name' => 'Kossi', 'last_name' => 'Adjo', 'email' => $email, 'role' => 'reviewer',
    ]);
}

it('crée une relecture « invitée » avec échéance, auteur de l’attribution et motif', function () {
    $submission = seededSubmission();
    $admin = adminStaff();

    $this->actingAs($admin, 'web');
    $review = app(AssignReviewer::class)
        ->handle($submission, 'Kossi Adjo', 'kossi@example.tg', 'Expertise sur l’axe Sciences');

    expect($review->status)->toBe('invited');
    expect($review->assigned_at)->not->toBeNull();
    expect($review->assigned_by_id)->toBe($admin->id);
    expect($review->assign_reason)->toBe('Expertise sur l’axe Sciences');
    // Échéance par défaut : 21 jours.
    expect(now()->startOfDay()->diffInDays($review->due_at->startOfDay()))->toBe((float) Review::DEFAULT_DUE_DAYS);
    // La soumission passe « en relecture ».
    expect($submission->fresh()->status)->toBe('under_review');
});

it('applique le délai d’évaluation réglé sur l’édition', function () {
    $submission = seededSubmission();
    $submission->edition->update(['settings' => array_merge($submission->edition->settings ?? [], ['review_due_days' => 7])]);

    $review = app(AssignReviewer::class)->handle($submission->fresh(), 'Kossi Adjo', 'kossi@example.tg');

    expect(now()->startOfDay()->diffInDays($review->due_at->startOfDay()))->toBe(7.0);
});

it('laisse le relecteur accepter sa relecture depuis « Mes relectures »', function () {
    $submission = seededSubmission();
    $reviewer = reviewerFor($submission);
    $review = Review::create(['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'invited', 'due_at' => now()->addDays(10)]);

    $this->actingAs($reviewer->user, 'account')
        ->post(route('account.reviews.respond', $review), ['answer' => 'accept'])
        ->assertRedirect();

    $review->refresh();
    expect($review->status)->toBe('accepted');
    expect($review->responded_at)->not->toBeNull();
});

it('laisse le relecteur refuser avec un motif, et la place est libérée', function () {
    $submission = seededSubmission();
    $reviewer = reviewerFor($submission);
    $review = Review::create(['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'invited', 'due_at' => now()->addDays(10)]);

    $this->actingAs($reviewer->user, 'account')
        ->post(route('account.reviews.respond', $review), ['answer' => 'decline', 'decline_reason' => 'Hors de mon domaine'])
        ->assertRedirect();

    $review->refresh();
    expect($review->status)->toBe('declined');
    expect($review->decline_reason)->toBe('Hors de mon domaine');
    // Refusée = ni active, ni en attente : la place ne compte plus.
    expect($review->isActive())->toBeFalse();
    expect($review->isPending())->toBeFalse();
});

it('rouvre la même relecture quand le comité ré-attribue après un refus', function () {
    $submission = seededSubmission();
    $review = app(AssignReviewer::class)->handle($submission, 'Kossi Adjo', 'kossi@example.tg');
    $review->update(['status' => 'declined', 'responded_at' => now(), 'decline_reason' => 'Indisponible']);

    $again = app(AssignReviewer::class)->handle($submission->fresh(), 'Kossi Adjo', 'kossi@example.tg');

    expect($again->id)->toBe($review->id);
    expect($again->status)->toBe('invited');
    expect($again->decline_reason)->toBeNull();
    expect($again->responded_at)->toBeNull();
    expect(Review::count())->toBe(1);
});

it('interdit d’évaluer une relecture refusée', function () {
    $submission = seededSubmission();
    $reviewer = reviewerFor($submission);
    $review = Review::create(['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'declined']);

    $this->actingAs($reviewer->user, 'account')
        ->post(route('account.reviews.submit', $review), ['recommendation' => 'accept'])
        ->assertStatus(422);
});

it('considère l’évaluation rendue comme une acceptation implicite', function () {
    $submission = seededSubmission();
    $reviewer = reviewerFor($submission);
    $review = Review::create(['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'invited']);

    $this->actingAs($reviewer->user, 'account')
        ->post(route('account.reviews.submit', $review), ['recommendation' => 'minor']);

    $review->refresh();
    expect($review->status)->toBe('completed');
    expect($review->responded_at)->not->toBeNull();
});

it('déduit le retard de l’échéance, pas de l’inachèvement', function () {
    $submission = seededSubmission();
    $reviewer = reviewerFor($submission);

    $fresh = Review::create(['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'invited', 'due_at' => now()->addDays(5)]);
    expect($fresh->isLate())->toBeFalse();

    $fresh->update(['due_at' => now()->subDays(3)]);
    expect($fresh->fresh()->isLate())->toBeTrue();
    expect($fresh->fresh()->daysLate())->toBe(3);

    // Rendue : jamais en retard, même échéance dépassée.
    $fresh->update(['status' => 'completed']);
    expect($fresh->fresh()->isLate())->toBeFalse();
});

it('annule l’assignation au lieu de supprimer la relecture', function () {
    $submission = seededSubmission();
    $reviewer = reviewerFor($submission);
    $review = Review::create(['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'invited']);

    $this->actingAs(adminStaff(), 'web')
        ->delete(route('admin.submissions.review.remove', [$submission, $review]))
        ->assertRedirect();

    // La ligne reste (pièce du dossier) mais ne compte plus.
    expect(Review::count())->toBe(1);
    expect($review->fresh()->status)->toBe('cancelled');
    expect($review->fresh()->isActive())->toBeFalse();
});

it('résume l’état des relectures pour le comité', function () {
    $submission = seededSubmission();
    $r1 = reviewerFor($submission, 'r1@example.tg');
    $r2 = reviewerFor($submission, 'r2@example.tg');
    $r3 = reviewerFor($submission, 'r3@example.tg');

    $reviews = collect([
        Review::create(['submission_id' => $submission->id, 'reviewer_id' => $r1->id, 'status' => 'completed']),
        Review::create(['submission_id' => $submission->id, 'reviewer_id' => $r2->id, 'status' => 'accepted', 'due_at' => now()->subDays(3)]),
        Review::create(['submission_id' => $submission->id, 'reviewer_id' => $r3->id, 'status' => 'declined']),
    ]);

    expect(Review::summarize($reviews))
        ->toBe('2 relectures demandées - 1 rendue, 1 en retard de 3 j (1 refus)');

    expect(Review::summarize(collect()))->toBe('Aucun relecteur assigné.');
});

it('compte le retard réel dans le rapport d’édition', function () {
    $submission = seededSubmission();
    $r1 = reviewerFor($submission, 'r1@example.tg');
    $r2 = reviewerFor($submission, 'r2@example.tg');
    $r3 = reviewerFor($submission, 'r3@example.tg');

    // Dans les délais, en retard, refusée.
    Review::create(['submission_id' => $submission->id, 'reviewer_id' => $r1->id, 'status' => 'invited', 'due_at' => now()->addDays(5)]);
    Review::create(['submission_id' => $submission->id, 'reviewer_id' => $r2->id, 'status' => 'accepted', 'due_at' => now()->subDays(2)]);
    Review::create(['submission_id' => $submission->id, 'reviewer_id' => $r3->id, 'status' => 'declined', 'due_at' => now()->subDays(9)]);

    $stats = (new EditionReport($submission->edition))->reviewStats();

    expect($stats['late'])->toBe(1);
    expect($stats['pending'])->toBe(2);
    expect($stats['declined'])->toBe(1);
    expect($stats['done'])->toBe(0);

    // Charge par relecteur : un refus ne pèse pas sur la complétion.
    $byReviewer = collect((new EditionReport($submission->edition))->reviewsByReviewer())->keyBy('name');
    expect($byReviewer->firstWhere('declined', 1)['assigned'])->toBe(0);
});

it('empêche un relecteur de répondre à la relecture d’un autre', function () {
    $submission = seededSubmission();
    $mine = reviewerFor($submission, 'moi@example.tg');
    $other = reviewerFor($submission, 'autre@example.tg');
    $review = Review::create(['submission_id' => $submission->id, 'reviewer_id' => $other->id, 'status' => 'invited']);

    $this->actingAs($mine->user, 'account')
        ->post(route('account.reviews.respond', $review), ['answer' => 'accept'])
        ->assertForbidden();
});

it('affiche l’état et l’échéance de chaque relecture sur la fiche admin', function () {
    $submission = seededSubmission();
    $reviewer = reviewerFor($submission);
    Review::create([
        'submission_id' => $submission->id, 'reviewer_id' => $reviewer->id,
        'status' => 'invited', 'due_at' => now()->subDays(4),
    ]);

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.submissions.show', $submission))
        ->assertOk()
        ->assertSee('en retard de 4 j')
        ->assertSee('1 relecture demandée');
});

it('propose accepter / refuser sur la page « Mes relectures »', function () {
    $submission = seededSubmission();
    $reviewer = reviewerFor($submission);
    Review::create([
        'submission_id' => $submission->id, 'reviewer_id' => $reviewer->id,
        'status' => 'invited', 'due_at' => now()->addDays(10),
    ]);

    $this->actingAs($reviewer->user, 'account')
        ->get(route('account.reviews'))
        ->assertOk()
        ->assertSee('Accepter')
        ->assertSee('Refuser')
        ->assertSee('À confirmer')
        // L'URL de réponse est transportée dans le JSON Alpine : @js() échappe
        // deux fois (JSON puis attribut HTML), on cherche donc le segment de route.
        ->assertSee('respondUrl', false)
        ->assertSee('reponse', false);
});

it('expose le délai d’évaluation dans l’onglet Relecteurs de l’édition', function () {
    $edition = openEdition(['review_due_days' => 14]);

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.editions.edit', $edition))
        ->assertOk()
        ->assertSee('Délai d’évaluation (jours)', false)
        ->assertSee('value="14"', false);
});

it('enregistre le délai d’évaluation depuis la fiche d’édition', function () {
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.editions.reviewers.criteria', $edition), ['review_due_days' => 10])
        ->assertRedirect();

    expect(ReviewGrid::dueDays($edition->fresh()))->toBe(10);

    // Vide = on retombe sur la durée standard.
    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.editions.reviewers.criteria', $edition), ['review_due_days' => '']);

    expect(ReviewGrid::dueDays($edition->fresh()))->toBe(Review::DEFAULT_DUE_DAYS);
});

it('signale le conflit « même institution » par défaut', function () {
    $submission = seededSubmission();
    $submission->update(['authors' => [
        ['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'affiliation' => 'Université de Kara', 'corresponding' => true],
    ]]);

    $conflicts = ConflictOfInterest::check(
        $submission->fresh(), 'Kossi Adjo', 'kossi@example.tg', 'Université de Kara'
    );

    expect(collect($conflicts)->pluck('label'))->toContain('Même institution');
});

it('ignore le conflit « même institution » quand l’édition le demande', function () {
    $submission = seededSubmission();
    $submission->update(['authors' => [
        ['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'affiliation' => 'Université de Kara', 'corresponding' => true],
    ]]);
    $edition = $submission->edition;
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'options' => ['coi_ignore_same_institution' => true],
    ])]);

    $conflicts = ConflictOfInterest::check(
        $submission->fresh(), 'Kossi Adjo', 'kossi@example.tg', 'Université de Kara'
    );

    expect(collect($conflicts)->pluck('label'))->not->toContain('Même institution');

    // Les autres règles restent actives : le relecteur qui est aussi auteur.
    $asAuthor = ConflictOfInterest::check(
        $submission->fresh(), 'Awa Kodjo', 'awa@example.tg', 'Université de Kara'
    );
    expect(collect($asAuthor)->pluck('label'))->toContain('Auteur de la communication');
});

it('enregistre l’option depuis l’onglet Options de l’édition', function () {
    $edition = openEdition();
    $admin = adminStaff();

    $this->actingAs($admin, 'web')
        ->get(route('admin.editions.edit', $edition))
        ->assertOk()
        ->assertSee('opt_coi_ignore_institution', false);

    $this->actingAs($admin, 'web')->put(route('admin.editions.update', $edition), [
        'colloque_id' => $edition->colloque_id,
        'title' => $edition->title,
        'status' => $edition->status,
        'max_communications' => 3,
        'opt_coi_ignore_institution' => '1',
    ])->assertRedirect();

    expect($edition->fresh()->settings['options']['coi_ignore_same_institution'])->toBeTrue();
});

it('filtre la liste des relectures sans fausser les compteurs', function () {
    $submission = seededSubmission();
    $other = $submission->edition->submissions()->create([
        'participant_id' => $submission->participant_id,
        'ref_code' => '26-002',
        'title' => 'Seconde communication',
        'abstract' => str_repeat('Contexte. ', 10),
        'track' => 'Santé',
        'type' => 'oral',
        'status' => 'submitted',
    ]);
    $reviewer = reviewerFor($submission);

    Review::create(['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'invited', 'due_at' => now()->subDays(2)]);
    Review::create(['submission_id' => $other->id, 'reviewer_id' => $reviewer->id, 'status' => 'declined']);

    // Sans filtre : les deux lignes.
    $this->actingAs($reviewer->user, 'account')
        ->get(route('account.reviews'))
        ->assertOk()
        ->assertSee($submission->ref_code)
        ->assertSee($other->ref_code);

    // Filtré sur « refusée » : une seule ligne, mais le compteur « en retard »
    // décrit toujours l'ensemble (1), pas la sélection.
    $this->actingAs($reviewer->user, 'account')
        ->get(route('account.reviews', ['status' => 'declined']))
        ->assertOk()
        ->assertSee($other->ref_code)
        ->assertDontSee($submission->ref_code)
        ->assertSee('1 en retard');
});

/**
 * Test de garde : « relecture en attente » ≠ « pas encore rendue ».
 *
 * Depuis l'introduction du cycle de vie, un `status != 'completed'` compte aussi
 * les relectures REFUSÉES et ANNULÉES - le relecteur garde alors un badge pour
 * un travail qu'il n'a plus à faire, et le comité voit une charge fantôme. Le
 * motif a déjà été écrit à trois endroits (rapports d'édition, tableau de bord
 * participant, compteurs de l'espace relecteur), d'où ce garde-fou.
 *
 * La règle : passer par `Review::PENDING_STATUSES` / `isPending()` (encore dû),
 * `ACTIVE_STATUSES` / `isActive()` (occupe une place) ou `isLate()` (en retard).
 */
it('ne déduit jamais l’attente d’une relecture de son inachèvement', function () {
    $offenders = [];

    foreach ([base_path('app'), base_path('resources/views')] as $root) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $lines = file($file->getPathname());
            foreach ($lines as $i => $line) {
                // `->where('status', '!=', 'completed')` dans un contexte relecture.
                if (preg_match("/'status',\s*'!=',\s*'completed'/", $line)) {
                    $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()).':'.($i + 1);
                }
                // `where('status', '!=', 'completed')` sur une collection de relectures.
                if (preg_match("/Review::[a-zA-Z]*\(?.*'status'\s*,\s*'!='/", $line)) {
                    $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()).':'.($i + 1);
                }
            }
        }
    }

    expect($offenders)->toBe([], 'Relecture « en attente » déduite de l’inachèvement dans : '.implode(', ', $offenders));
});
