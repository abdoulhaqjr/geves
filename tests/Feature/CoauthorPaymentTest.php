<?php

use App\Actions\NotifyCoauthorsForPayment;
use App\Mail\CoauthorPaymentInvitationMail;
use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Email;
use App\Models\Participant;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

function coauthorEdition(): Edition
{
    return openEdition(['fees' => [
        ['label' => 'Doctorant', 'amount' => 10000],
        ['label' => 'Docteur', 'amount' => 20000],
    ]]);
}

function acceptedWithCoauthor(Edition $edition, string $coEmail = 'co@example.tg', string $coGrade = 'Docteur', string $status = 'accepted'): Submission
{
    $author = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    return $edition->submissions()->create([
        'participant_id' => $author->id,
        'title' => 'Sur les colloques',
        'status' => $status,
        'revision_of' => 0,
        'ref_code' => 'AA-001',
        'access_token' => Str::random(64),
        'track' => 'Sciences',
        'type' => 'oral',
        'authors' => [
            ['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'corresponding' => true],
            ['name' => 'Ben Co', 'email' => $coEmail, 'affiliation' => 'UK', 'grade' => $coGrade, 'corresponding' => false],
        ],
    ]);
}

/**
 * ⚠️ **La page oriente, elle n'encaisse plus.** Elle doublonnait le chemin par
 * compte - celui que `LinkSubmissionAuthors` ouvre dès la soumission - et les
 * deux créaient leur propre participant, l'un par e-mail sans `user_id`,
 * l'autre par `user_id`. Elle confirme donc que la personne est bien citée,
 * puis l'envoie créer son compte ou s'y connecter.
 */
it('retrouve les communications du co-auteur et l’oriente vers son espace', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    $page = $this->get(route('public.coauthor', $edition->slug).'?email=co@example.tg')
        ->assertOk()
        ->assertSee('AA-001')
        ->assertSee('votre espace participant', false)
        ->assertSee('Créer mon compte');

    // L'adresse voyage avec l'inscription : c'est elle qui rattachera la
    // communication au compte créé (cf. LinkPendingAuthorships).
    $page->assertSee(urlencode('co@example.tg'), false);

    // Plus aucun geste de paiement ici.
    $page->assertDontSee('Montant à régler');
    $page->assertDontSee('<form method="POST"', false);
});

/** Déjà connecté : on ne lui redemande rien, il règle dans son espace. */
it('renvoie un visiteur connecté vers le règlement de son espace', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    $this->actingAs(verifiedAccount(), 'account')
        ->get(route('public.coauthor', $edition->slug).'?email=co@example.tg')
        ->assertRedirect(route('account.payment', $edition));
});

/** ⚠️ La route de paiement sans compte n'existe plus : elle était la cause du doublon. */
it('n’expose plus de paiement sans compte', function () {
    expect(Route::has('public.coauthor.pay'))->toBeFalse();
});

it('n’associe rien à un e-mail inconnu', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    $this->get(route('public.coauthor', $edition->slug).'?email=personne@example.tg')
        ->assertSee('Aucune communication');
});

/**
 * ⚠️ Conservé pour les règlements co-auteur **anciens** : le retour de
 * passerelle d'un paiement `meta.flow = coauthor` atterrit encore sur cette
 * page, et doit y trouver sa confirmation et son reçu.
 */
it('affiche l’état « déjà réglé » pour un co-auteur ayant payé', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    $co = $edition->participants()->create(['first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'co@example.tg', 'role' => 'coauthor', 'title' => 'Docteur']);
    $edition->payments()->create(['participant_id' => $co->id, 'amount' => 20000, 'status' => 'paid', 'gateway' => 'manual', 'method' => 'cash', 'receipt_no' => 'REC-2026-001', 'paid_at' => now()]);

    $this->get(route('public.coauthor', $edition->slug).'?email=co@example.tg')
        ->assertSee('Frais déjà réglés');
});

it('notifie les co-auteurs d’une communication acceptée et journalise', function () {
    Mail::fake();
    $edition = coauthorEdition();
    $submission = acceptedWithCoauthor($edition);

    $count = app(NotifyCoauthorsForPayment::class)->handle($submission);

    expect($count)->toBe(1);
    Mail::assertQueued(CoauthorPaymentInvitationMail::class, fn ($m) => $m->hasTo('co@example.tg'));
    expect(Email::where('template', 'coauthor_payment')->where('recipient', 'co@example.tg')->count())->toBe(1);
});

it('ne notifie pas les co-auteurs si la communication n’est pas acceptée', function () {
    Mail::fake();
    $edition = coauthorEdition();
    $submission = acceptedWithCoauthor($edition, status: 'submitted');

    expect(app(NotifyCoauthorsForPayment::class)->handle($submission))->toBe(0);
    Mail::assertNothingQueued();
});

it('déclenche la notification des co-auteurs depuis l’acceptation admin', function () {
    Mail::fake();
    $edition = coauthorEdition();
    $submission = acceptedWithCoauthor($edition, status: 'submitted');

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.submissions.status', $submission), ['status' => 'accepted']);

    Mail::assertQueued(CoauthorPaymentInvitationMail::class, 1);
});

it('rend un co-auteur payé éligible à l’attestation de communication', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    $co = $edition->participants()->create(['first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'co@example.tg', 'role' => 'coauthor', 'title' => 'Docteur']);
    $edition->payments()->create(['participant_id' => $co->id, 'amount' => 20000, 'status' => 'paid', 'gateway' => 'manual', 'method' => 'cash', 'paid_at' => now()]);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'edition_id' => (string) $edition->id,
        'type' => 'communication',
    ]);

    expect(Attestation::where('participant_id', $co->id)->where('type', 'communication')->exists())->toBeTrue();
});

it('n’associe pas un co-auteur non payé à l’attestation de communication', function () {
    $edition = coauthorEdition();
    acceptedWithCoauthor($edition);

    $co = $edition->participants()->create(['first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'co@example.tg', 'role' => 'coauthor', 'title' => 'Docteur']);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'edition_id' => (string) $edition->id,
        'type' => 'communication',
    ]);

    expect(Attestation::where('participant_id', $co->id)->exists())->toBeFalse();
});

/**
 * Le chemin complet que la porte d'entrée promet : le co-auteur cité dans une
 * soumission crée son compte avec la même adresse, la liaison en attente
 * s'active (`LinkPendingAuthorships`), **un seul** participant existe, et son
 * espace lui propose le montant de son grade.
 */
it('mène le co-auteur de la porte d’entrée au règlement dans son espace', function () {
    $edition = coauthorEdition();
    $submission = acceptedWithCoauthor($edition);

    // Liaison « invitée », telle que LinkSubmissionAuthors la pose pour un non-inscrit.
    SubmissionAuthor::create([
        'submission_id' => $submission->id, 'edition_id' => $edition->id, 'role' => 'coauthor',
        'author_index' => 1, 'name' => 'Ben Co', 'email' => 'co@example.tg',
        'token' => Str::random(48), 'status' => 'invited', 'invited_at' => now(),
    ]);

    $this->post(route('account.register.store'), [
        'first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'co@example.tg',
        'phone' => '+22890000042', 'password' => 'Motdepasse!1', 'password_confirmation' => 'Motdepasse!1',
        'terms' => '1', 'grade' => 'Docteur', 'affiliation' => 'UK', 'country' => 'TG',
    ])->assertRedirect(route('account.dashboard'));

    $compte = User::where('email', 'co@example.tg')->firstOrFail();

    // ⚠️ UN SEUL participant : c'est tout l'objet du retrait du paiement sans
    // compte, qui en créait un second sans `user_id`.
    $participants = Participant::where('edition_id', $edition->id)
        ->whereRaw('LOWER(email) = ?', ['co@example.tg'])->get();

    expect($participants)->toHaveCount(1);
    expect($participants->first()->user_id)->toBe($compte->id);

    // Et son espace lui propose bien le tarif de son grade.
    $this->actingAs($compte, 'account')
        ->get(route('account.payment', $edition))
        ->assertOk()
        ->assertSee('20 000');
});
