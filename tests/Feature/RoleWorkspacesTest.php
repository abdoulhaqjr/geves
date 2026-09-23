<?php

use App\Mail\PaymentReminderMail;
use App\Models\Review;
use App\Models\SessionReport;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// openEdition() / verifiedAccount() -> tests/Pest.php

/** Utilisateur participant vérifié avec un e-mail donné. */
function eventActor(string $email, string $first = 'Test', string $last = 'Acteur'): User
{
    $user = User::create([
        'first_name' => $first, 'last_name' => $last,
        'email' => $email, 'password' => 'pass12345',
    ]);
    $user->markEmailAsVerified();

    return $user;
}

it('protège les espaces de fonction derrière le guard account', function () {
    $this->get(route('account.moderation'))->assertRedirect(route('account.login'));
    $this->get(route('account.reports'))->assertRedirect(route('account.login'));
    $this->get(route('account.reviews'))->assertRedirect(route('account.login'));
});

it('liste au modérateur ses séances à animer', function () {
    $edition = openEdition();
    $mod = eventActor('mod@example.tg');
    $edition->sessions()->create([
        'label' => 'Session - Atelier 1', 'type' => 'atelier', 'room' => 'Salle 1',
        'moderator_email' => 'mod@example.tg', 'moderator_name' => 'Test Acteur',
    ]);

    $this->actingAs($mod, 'account')
        ->get(route('account.moderation'))
        ->assertOk()
        ->assertSee('Session - Atelier 1')
        ->assertSee('Espace d’échange');
});

it('propose au modérateur un accès à son espace depuis le tableau de bord', function () {
    $edition = openEdition();
    $mod = eventActor('moddash@example.tg');
    $edition->sessions()->create([
        'label' => 'Session - Atelier 9', 'type' => 'atelier',
        'moderator_email' => 'moddash@example.tg', 'moderator_name' => 'Test Acteur',
    ]);

    $this->actingAs($mod, 'account')
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Mes rôles &amp; activité', false)
        ->assertSee(route('account.moderation'), false);
});

it('affiche l’état vide pour un compte sans séance à animer', function () {
    $mod = eventActor('rien@example.tg');

    $this->actingAs($mod, 'account')
        ->get(route('account.moderation'))
        ->assertOk()
        ->assertSee('Aucune séance à animer');
});

it('permet au rapporteur de rédiger et soumettre un rapport de séance commencée', function () {
    $edition = openEdition();
    $rap = eventActor('rap@example.tg');
    $session = $edition->sessions()->create([
        'label' => 'Session - Atelier 2', 'type' => 'atelier',
        'starts_at' => now()->subHour(), // séance déjà commencée
        'rapporteur_email' => 'rap@example.tg', 'rapporteur_name' => 'Test Acteur',
    ]);

    $this->actingAs($rap, 'account')
        ->get(route('account.reports'))
        ->assertOk()
        ->assertSee('Session - Atelier 2')
        ->assertSee('À rédiger');

    $this->actingAs($rap, 'account')
        ->get(route('account.reports.edit', $session))
        ->assertOk()
        ->assertSee('Rapport de séance');

    $this->actingAs($rap, 'account')
        ->post(route('account.reports.update', $session), [
            'summary' => 'Trois communications présentées, échanges nourris.',
            'highlights' => 'Recommander la publication des actes.',
            'attendance' => 42,
            'action' => 'submit',
        ])
        ->assertRedirect(route('account.reports'));

    $report = SessionReport::where('session_id', $session->id)->first();
    expect($report)->not->toBeNull();
    expect($report->status)->toBe('submitted');
    expect($report->submitted_at)->not->toBeNull();
    expect((int) $report->attendance)->toBe(42);
});

it('empêche le rapporteur de rédiger avant le début de la séance', function () {
    $edition = openEdition();
    $rap = eventActor('rapfutur@example.tg');
    $session = $edition->sessions()->create([
        'label' => 'Session future', 'type' => 'atelier',
        'starts_at' => now()->addDay(), // pas encore commencée
        'rapporteur_email' => 'rapfutur@example.tg',
    ]);

    $this->actingAs($rap, 'account')
        ->get(route('account.reports.edit', $session))
        ->assertOk()
        ->assertSee('Rédaction pas encore ouverte');

    $this->actingAs($rap, 'account')
        ->post(route('account.reports.update', $session), ['action' => 'draft'])
        ->assertForbidden();
});

it('verrouille le rapport pour le rapporteur une fois validé par le secrétariat', function () {
    $edition = openEdition();
    $rap = eventActor('rapval@example.tg');
    $session = $edition->sessions()->create([
        'label' => 'Session validée', 'type' => 'atelier',
        'starts_at' => now()->subHour(),
        'rapporteur_email' => 'rapval@example.tg',
    ]);
    SessionReport::create([
        'session_id' => $session->id, 'edition_id' => $edition->id,
        'summary' => 'Rapport rédigé.', 'status' => 'validated',
        'submitted_at' => now()->subHour(), 'validated_at' => now(),
    ]);

    // Consultation possible, mais formulaire verrouillé.
    $this->actingAs($rap, 'account')
        ->get(route('account.reports.edit', $session))
        ->assertOk()
        ->assertSee('validé par le secrétariat');

    // Toute modification est refusée.
    $this->actingAs($rap, 'account')
        ->post(route('account.reports.update', $session), [
            'summary' => 'Tentative de modification.', 'action' => 'submit',
        ])
        ->assertForbidden();

    expect(SessionReport::where('session_id', $session->id)->value('summary'))->toBe('Rapport rédigé.');
});

it('affiche au secrétariat sa file de rapports à valider (scopée à ses éditions)', function () {
    $edition = openEdition();
    $session = $edition->sessions()->create([
        'label' => 'Séance à valider', 'type' => 'atelier',
        'starts_at' => now()->subHour(), 'rapporteur_email' => 'rap@example.tg', 'rapporteur_name' => 'Rita R.',
    ]);
    SessionReport::create([
        'session_id' => $session->id, 'edition_id' => $edition->id,
        'summary' => 'Brouillon rapporteur.', 'status' => 'submitted', 'submitted_at' => now(),
    ]);

    // Staff « secrétariat » rattaché à l'édition (non super-admin).
    $staff = User::create(['first_name' => 'Sara', 'last_name' => 'Secrétaire', 'email' => 'sec@example.tg', 'password' => 'pass12345']);
    $staff->assignedEditions()->attach($edition->id, ['role' => 'secretariat']);

    $this->actingAs($staff, 'web')
        ->get(route('admin.secretariat'))
        ->assertOk()
        ->assertSee('Rapports à valider')
        ->assertSee('Séance à valider')
        ->assertSee('Rita R.');
});

it('limite les onglets des Rapports à la fonction (comptable → Paiements)', function () {
    $edition = openEdition();
    $staff = User::create(['first_name' => 'Carine', 'last_name' => 'Comptable', 'email' => 'compta@example.tg', 'password' => 'pass12345']);
    $staff->assignedEditions()->attach($edition->id, ['role' => 'accounting']);

    $html = $this->actingAs($staff, 'web')
        ->get(route('admin.reports.index', ['edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('Rapport paiements')     // panneau Paiements rendu
        ->assertDontSee('Rapport soumissions')
        ->assertDontSee('Rapport relectures')
        ->assertDontSee('Rapport participants')
        ->getContent();

    expect($html)->not->toContain("tab='reviews'");   // onglet Relectures absent
    expect($html)->not->toContain("tab='submissions'");
});

it('relance un participant qui n’a pas réglé ses frais (page Paiements)', function () {
    Mail::fake();
    $edition = openEdition(['fees' => [['label' => 'Doctorants', 'amount' => 15000]]]);
    $account = User::create(['first_name' => 'Non', 'last_name' => 'Payé', 'email' => 'nonpaye@example.tg', 'password' => 'pass12345']);
    $participant = $edition->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Non', 'last_name' => 'Payé',
        'email' => 'nonpaye@example.tg', 'title' => 'Doctorants', 'role' => 'author',
    ]);
    $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-900', 'title' => 'Sans paiement',
        'abstract' => 'x', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted', 'authors' => [],
    ]);

    // Page Paiements : le participant apparaît dans les relances.
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertSee('Relances de paiement')
        ->assertSee('nonpaye@example.tg');

    // Relance : e-mail + notification + horodatage.
    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.payments.remind', $participant))
        ->assertRedirect();

    Mail::assertQueued(PaymentReminderMail::class);
    expect($participant->fresh()->meta['payment_reminded_at'] ?? null)->not->toBeNull();
    expect($account->notifications()->count())->toBe(1);
});

it('valide un rapport en un clic sans écraser son contenu', function () {
    $edition = openEdition();
    $session = $edition->sessions()->create([
        'label' => 'Séance', 'type' => 'atelier', 'starts_at' => now()->subHour(), 'rapporteur_email' => 'rap@example.tg',
    ]);
    SessionReport::create([
        'session_id' => $session->id, 'edition_id' => $edition->id,
        'summary' => 'Contenu rédigé par le rapporteur.', 'status' => 'submitted', 'submitted_at' => now(),
    ]);

    // POST « nu » (bouton Valider de la file secrétariat) : verrouille sans vider.
    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.program.reports.save', $session))
        ->assertRedirect();

    $report = SessionReport::where('session_id', $session->id)->first();
    expect($report->status)->toBe('validated');
    expect($report->summary)->toBe('Contenu rédigé par le rapporteur.');
});

it('permet au secrétariat de valider un rapport (et de le rouvrir)', function () {
    $edition = openEdition();
    $session = $edition->sessions()->create([
        'label' => 'Session à valider', 'type' => 'atelier',
        'starts_at' => now()->subHour(), 'rapporteur_email' => 'rap@example.tg',
    ]);
    SessionReport::create([
        'session_id' => $session->id, 'edition_id' => $edition->id,
        'summary' => 'Brouillon rapporteur.', 'status' => 'submitted', 'submitted_at' => now(),
    ]);

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.program.reports.save', $session), [
            'summary' => 'Synthèse améliorée par le secrétariat.',
            'attendance' => 60,
        ])->assertRedirect();

    $report = SessionReport::where('session_id', $session->id)->first();
    expect($report->status)->toBe('validated');
    expect($report->validated_at)->not->toBeNull();
    expect($report->summary)->toBe('Synthèse améliorée par le secrétariat.');

    // Réouverture : redevient modifiable par le rapporteur.
    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.program.reports.reopen', $session))
        ->assertRedirect();
    expect(SessionReport::where('session_id', $session->id)->value('status'))->toBe('submitted');
});

it('refuse le rapport d’une séance à un non-rapporteur', function () {
    $edition = openEdition();
    $intrus = eventActor('intrus@example.tg');
    $session = $edition->sessions()->create([
        'label' => 'Session - Atelier 3', 'type' => 'atelier',
        'rapporteur_email' => 'quelquun-dautre@example.tg',
    ]);

    $this->actingAs($intrus, 'account')
        ->get(route('account.reports.edit', $session))
        ->assertForbidden();

    $this->actingAs($intrus, 'account')
        ->post(route('account.reports.update', $session), ['action' => 'draft'])
        ->assertForbidden();
});

it('affiche au comité les rapports de séance soumis (vue admin)', function () {
    $edition = openEdition();
    $session = $edition->sessions()->create([
        'label' => 'Session - Atelier 4', 'type' => 'atelier',
        'rapporteur_email' => 'rap@example.tg', 'rapporteur_name' => 'Rita Rapporteuse',
    ]);
    SessionReport::create([
        'session_id' => $session->id, 'edition_id' => $edition->id,
        'summary' => 'Synthèse détaillée de la séance.',
        'highlights' => 'Publier les actes.', 'attendance' => 42,
        'status' => 'submitted', 'submitted_at' => now(),
    ]);

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.program.reports', ['edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('Session - Atelier 4')
        ->assertSee('Rita Rapporteuse')
        ->assertSee('Synthèse détaillée de la séance.')
        ->assertSee('Publier les actes.')
        ->assertSee('Soumis');
});

it('notifie le modérateur et le rapporteur à leur désignation sur une séance', function () {
    $edition = openEdition();
    $mod = eventActor('newmod@example.tg');
    $rap = eventActor('newrap@example.tg');

    $this->actingAs(adminStaff(), 'web')->post(route('admin.program.sessions.store'), [
        'edition_id' => (string) $edition->id,
        'sType' => 'plenary',
        'sLabel' => 'Séance inaugurale',
        'sDay' => '', 'sStart' => '', 'sEnd' => '', 'sRoom' => '',
        'sModerator' => 'Test Acteur', 'sModeratorEmail' => 'newmod@example.tg',
        'sRapporteur' => 'Test Acteur', 'sRapporteurEmail' => 'newrap@example.tg',
    ])->assertRedirect();

    expect($mod->notifications()->where('type', SystemNotification::class)->count())->toBe(1);
    expect($rap->notifications()->count())->toBe(1);
    expect($mod->notifications()->first()->data['title'])->toBe('Vous animez une séance');
    expect($rap->notifications()->first()->data['title'])->toBe('Vous rapportez une séance');
});

it('ne renotifie pas un modérateur inchangé lors d’une mise à jour de séance', function () {
    $edition = openEdition();
    $mod = eventActor('stable@example.tg');
    $session = $edition->sessions()->create([
        'label' => 'Séance', 'type' => 'plenary',
        'moderator_email' => 'stable@example.tg', 'moderator_name' => 'Test Acteur',
    ]);

    $this->actingAs(adminStaff(), 'web')->put(route('admin.program.sessions.update', $session), [
        'sType' => 'plenary', 'sLabel' => 'Séance (renommée)',
        'sDay' => '', 'sStart' => '', 'sEnd' => '', 'sRoom' => '',
        'sModerator' => 'Test Acteur', 'sModeratorEmail' => 'stable@example.tg',
    ])->assertRedirect();

    expect($mod->notifications()->count())->toBe(0);
});

it('liste au relecteur ses relectures confiées', function () {
    $edition = openEdition();
    $rev = eventActor('rev@example.tg');
    $reviewer = $edition->participants()->create([
        'user_id' => $rev->id, 'first_name' => 'Test', 'last_name' => 'Acteur',
        'email' => 'rev@example.tg', 'role' => 'reviewer', 'access_token' => Str::random(64),
    ]);
    $participant = $edition->participants()->create([
        'user_id' => null, 'first_name' => 'Auteur', 'last_name' => 'X', 'email' => 'auteur@example.tg',
    ]);
    $submission = $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-777', 'title' => 'Étude sur la durabilité',
        'abstract' => 'Résumé.', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'under_review',
        'authors' => [['name' => 'Auteur X', 'corresponding' => true]],
    ]);
    Review::create(['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'assigned']);

    $this->actingAs($rev, 'account')
        ->get(route('account.reviews'))
        ->assertOk()
        ->assertSee('Étude sur la durabilité')
        ->assertSee('À évaluer')
        ->assertDontSee('Auteur X'); // en aveugle : pas d'auteur
});

it('enregistre une évaluation de relecteur en place (AJAX)', function () {
    $edition = openEdition();
    $rev = eventActor('rev2@example.tg');
    $reviewer = $edition->participants()->create([
        'user_id' => $rev->id, 'first_name' => 'Test', 'last_name' => 'Acteur',
        'email' => 'rev2@example.tg', 'role' => 'reviewer', 'access_token' => Str::random(64),
    ]);
    $participant = $edition->participants()->create([
        'user_id' => null, 'first_name' => 'Auteur', 'last_name' => 'Z', 'email' => 'auteurz@example.tg',
    ]);
    $submission = $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-778', 'title' => 'Autre étude',
        'abstract' => 'Résumé.', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'under_review',
        'authors' => [],
    ]);
    $review = Review::create(['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'assigned']);

    $this->actingAs($rev, 'account')
        ->postJson(route('account.reviews.submit', $review), [
            'recommendation' => 'minor',
            'scores' => ['originality' => 4, 'rigor' => 4, 'clarity' => 4, 'relevance' => 4, 'contribution' => 4],
            'comments' => 'Bon travail.',
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    $review->refresh();
    expect($review->status)->toBe('completed');
    expect($review->recommendation)->toBe('minor');
    expect((float) $review->score)->toBe(16.0);
});

it('refuse une évaluation sur une relecture qui n’est pas la sienne', function () {
    $edition = openEdition();
    $intrus = eventActor('intrus2@example.tg');
    $reviewer = $edition->participants()->create([
        'user_id' => null, 'first_name' => 'Autre', 'last_name' => 'Relecteur',
        'email' => 'autre@example.tg', 'role' => 'reviewer', 'access_token' => Str::random(64),
    ]);
    $participant = $edition->participants()->create(['user_id' => null, 'first_name' => 'A', 'last_name' => 'B', 'email' => 'ab@example.tg']);
    $submission = $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-779', 'title' => 'X',
        'abstract' => 'x', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'under_review', 'authors' => [],
    ]);
    $review = Review::create(['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'assigned']);

    $this->actingAs($intrus, 'account')
        ->postJson(route('account.reviews.submit', $review), ['recommendation' => 'accept'])
        ->assertForbidden();
});
