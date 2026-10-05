<?php

use App\Models\Participant;
use App\Models\Review;
use App\Models\ReviewerBid;
use App\Models\SessionReport;
use App\Models\Submission;
use App\Models\User;
use App\Support\EventLock;

/**
 * Un événement **archivée** est en lecture seule pour les participants : plus de
 * dépôt, plus de relecture, plus de rapport, plus de paiement. L'admin et les
 * gestionnaires gardent la main (back-office, RBAC par événement).
 *
 * ⚠️ Le déclencheur est le statut `archived`, pas `closed` : `closed` = dépôts
 * clos, c'est justement la période où les relecteurs travaillent.
 */

// openEdition()/verifiedAccount()/adminStaff()/seededSubmission() -> tests/Pest.php

/** Relecteur (participant + compte) sur l'événement d'une soumission. */
function lockedReviewer(Submission $submission): Participant
{
    $account = User::create([
        'name' => 'Kossi Adjo', 'first_name' => 'Kossi', 'last_name' => 'Adjo',
        'email' => 'kossi.lock@example.tg', 'password' => 'pass12345',
        'phone' => '+22893333333', 'affiliation' => 'Université de Kara',
    ]);
    $account->markEmailAsVerified();

    return Participant::create([
        'event_id' => $submission->event_id, 'user_id' => $account->id,
        'first_name' => 'Kossi', 'last_name' => 'Adjo',
        'email' => $account->email, 'role' => 'reviewer',
    ]);
}

it('ne gèle que les événements archivées', function () {
    $event = openEdition();

    foreach (['draft', 'open', 'closed'] as $status) {
        $event->update(['status' => $status]);
        expect(EventLock::frozen($event->fresh()))->toBeFalse();
    }

    $event->update(['status' => 'archived']);
    expect(EventLock::frozen($event->fresh()))->toBeTrue();
    expect(EventLock::frozen(null))->toBeFalse();
});

it('interdit au relecteur de rendre une évaluation sur un événement archivé', function () {
    $submission = seededSubmission();
    $reviewer = lockedReviewer($submission);
    $review = Review::create([
        'submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'accepted',
    ]);
    $submission->event->update(['status' => 'archived']);

    $this->actingAs($reviewer->user, 'account')
        ->post(route('account.reviews.submit', $review), ['recommendation' => 'accept'])
        ->assertForbidden();

    expect($review->fresh()->status)->toBe('accepted');
});

it('interdit au relecteur d’accepter ou de refuser sur un événement archivé', function () {
    $submission = seededSubmission();
    $reviewer = lockedReviewer($submission);
    $review = Review::create([
        'submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'invited',
    ]);
    $submission->event->update(['status' => 'archived']);

    $this->actingAs($reviewer->user, 'account')
        ->post(route('account.reviews.respond', $review), ['answer' => 'decline'])
        ->assertForbidden();

    expect($review->fresh()->status)->toBe('invited');
});

it('laisse le relecteur travailler sur un événement seulement clôturée', function () {
    $submission = seededSubmission();
    $reviewer = lockedReviewer($submission);
    $review = Review::create([
        'submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'invited',
    ]);
    // Dépôts clos : c'est précisément le moment où l'on relit.
    $submission->event->update(['status' => 'closed']);

    $this->actingAs($reviewer->user, 'account')
        ->post(route('account.reviews.respond', $review), ['answer' => 'accept'])
        ->assertRedirect();

    expect($review->fresh()->status)->toBe('accepted');
});

it('retire l’appel à candidatures d’un événement archivé', function () {
    $submission = seededSubmission('submitted');
    $reviewer = lockedReviewer($submission);

    $this->actingAs($reviewer->user, 'account')
        ->get(route('account.reviews', ['tab' => 'candidatures']))
        ->assertOk()
        ->assertSee($submission->ref_code);

    $submission->event->update(['status' => 'archived']);

    $this->actingAs($reviewer->user, 'account')
        ->get(route('account.reviews', ['tab' => 'candidatures']))
        ->assertOk()
        ->assertDontSee($submission->ref_code);
});

it('refuse une candidature envoyée malgré tout sur un événement archivé', function () {
    $submission = seededSubmission('submitted');
    $reviewer = lockedReviewer($submission);
    $submission->event->update(['status' => 'archived']);

    $this->actingAs($reviewer->user, 'account')
        ->post(route('account.reviews.bids'), [
            'bids' => [$submission->id => ['interest' => 'interested']],
        ])->assertRedirect();

    expect(ReviewerBid::count())->toBe(0);
});

it('interdit à l’auteur de corriger sa communication sur un événement archivé', function () {
    $submission = seededSubmission();
    $submission->event->update(['status' => 'archived']);
    $author = $submission->participant->user;

    $this->actingAs($author, 'account')
        ->post(route('account.submissions.update', $submission), [
            'title' => 'Titre modifié', 'abstract' => str_repeat('Mot ', 60),
        ])
        ->assertForbidden();

    expect($submission->fresh()->title)->not->toBe('Titre modifié');
});

it('laisse consulter la page de paiement mais ferme le règlement', function () {
    $event = openEdition();
    $account = verifiedAccount();
    Participant::create([
        'event_id' => $event->id, 'user_id' => $account->id,
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email, 'role' => 'author',
    ]);
    $event->update(['status' => 'archived']);

    // Lecture conservée (tarifs, reçu éventuel), parcours de règlement clos.
    $this->actingAs($account, 'account')
        ->get(route('account.payment', $event))
        ->assertOk()
        ->assertSee('archivée', false);

    $this->actingAs($account, 'account')
        ->post(route('account.payment.pay', $event), ['gateway' => 'manual'])
        ->assertForbidden();
});

it('laisse l’admin intervenir sur un événement archivé', function () {
    $submission = seededSubmission();
    $submission->event->update(['status' => 'archived']);

    // Le gel ne concerne que l'espace participant : le back-office garde la main
    // (corriger une décision, émettre une attestation oubliée…).
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.submissions.show', $submission))
        ->assertOk();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.submissions.review.assign', $submission), [
            'reviewerName' => 'Ama Sow', 'reviewerEmail' => 'ama.sow@example.tg',
        ])
        ->assertRedirect();

    expect(Review::count())->toBe(1);
});

it('laisse le relecteur consulter l’évaluation qu’il a rendue', function () {
    $submission = seededSubmission();
    $reviewer = lockedReviewer($submission);
    Review::create([
        'submission_id' => $submission->id, 'reviewer_id' => $reviewer->id,
        'status' => 'completed', 'recommendation' => 'minor', 'score' => 15,
        'comments' => 'Communication solide et pertinente.',
    ]);
    $submission->event->update(['status' => 'archived']);

    // Le gel retire les actions, jamais la lecture de son propre travail.
    $this->actingAs($reviewer->user, 'account')
        ->get(route('account.reviews'))
        ->assertOk()
        ->assertSee($submission->ref_code)
        ->assertSee('Consulter')
        ->assertSee('Archivée');
});

it('laisse le rapporteur relire son rapport sur un événement archivé', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $session = sessionFor($event);
    $session->update(['rapporteur_email' => $account->email, 'starts_at' => now()->subDay()]);
    SessionReport::create([
        'session_id' => $session->id, 'event_id' => $event->id,
        'summary' => 'Synthèse de la séance plénière.',
    ]);
    $event->update(['status' => 'archived']);

    $this->actingAs($account, 'account')
        ->get(route('account.reports.edit', $session))
        ->assertOk()
        ->assertSee('Synthèse de la séance plénière.')
        ->assertSee('archivée', false);

    // …mais il ne peut plus la modifier.
    $this->actingAs($account, 'account')
        ->post(route('account.reports.update', $session), ['summary' => 'Réécriture', 'action' => 'draft'])
        ->assertForbidden();
});
