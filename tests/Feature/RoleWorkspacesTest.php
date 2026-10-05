<?php

use App\Mail\CampaignMail;
use App\Models\Payment;
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
    $event = openEdition();
    $mod = eventActor('mod@example.tg');
    $event->sessions()->create([
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
    $event = openEdition();
    $mod = eventActor('moddash@example.tg');
    $event->sessions()->create([
        'label' => 'Session - Atelier 9', 'type' => 'atelier',
        'moderator_email' => 'moddash@example.tg', 'moderator_name' => 'Test Acteur',
    ]);

    $this->actingAs($mod, 'account')
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Mes rôles &amp; activité', false)
        ->assertSee(route('account.moderation'), false);
});

it('ferme la page de modération à un compte sans séance confiée', function () {
    // ⚠️ Ce cas montrait l'état vide : la page s'ouvrait à tout compte connecté.
    // Elle appartient désormais aux modérateurs (cf. ModerationPageTest) - un
    // espace de travail n'est pas une page qu'on visite.
    $mod = eventActor('rien@example.tg');

    $this->actingAs($mod, 'account')
        ->get(route('account.moderation'))
        ->assertForbidden();
});

it('permet au rapporteur de rédiger et soumettre un rapport de séance commencée', function () {
    $event = openEdition();
    $rap = eventActor('rap@example.tg');
    $session = $event->sessions()->create([
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
    $event = openEdition();
    $rap = eventActor('rapfutur@example.tg');
    $session = $event->sessions()->create([
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
    $event = openEdition();
    $rap = eventActor('rapval@example.tg');
    $session = $event->sessions()->create([
        'label' => 'Session validée', 'type' => 'atelier',
        'starts_at' => now()->subHour(),
        'rapporteur_email' => 'rapval@example.tg',
    ]);
    SessionReport::create([
        'session_id' => $session->id, 'event_id' => $event->id,
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

it('affiche au secrétariat sa file de rapports à valider (scopée à ses événements)', function () {
    $event = openEdition();
    $session = $event->sessions()->create([
        'label' => 'Séance à valider', 'type' => 'atelier',
        'starts_at' => now()->subHour(), 'rapporteur_email' => 'rap@example.tg', 'rapporteur_name' => 'Rita R.',
    ]);
    SessionReport::create([
        'session_id' => $session->id, 'event_id' => $event->id,
        'summary' => 'Brouillon rapporteur.', 'status' => 'submitted', 'submitted_at' => now(),
    ]);

    // Staff « secrétariat » rattaché à l'événement (non super-admin).
    $staff = User::create(['first_name' => 'Sara', 'last_name' => 'Secrétaire', 'email' => 'sec@example.tg', 'password' => 'pass12345']);
    $staff->assignedEditions()->attach($event->id, ['role' => 'secretariat']);

    $this->actingAs($staff, 'web')
        ->get(route('admin.secretariat'))
        ->assertOk()
        ->assertSee('Rapports à valider')
        ->assertSee('Séance à valider')
        ->assertSee('Rita R.');
});

it('limite les onglets des Rapports à la fonction (comptable → Paiements)', function () {
    $event = openEdition();
    $staff = User::create(['first_name' => 'Carine', 'last_name' => 'Comptable', 'email' => 'compta@example.tg', 'password' => 'pass12345']);
    $staff->assignedEditions()->attach($event->id, ['role' => 'accounting']);

    $html = $this->actingAs($staff, 'web')
        ->get(route('admin.reports.index', ['event_id' => $event->id]))
        ->assertOk()
        ->assertSee('Rapport paiements')     // panneau Paiements rendu
        ->assertDontSee('Rapport soumissions')
        ->assertDontSee('Rapport relectures')
        ->assertDontSee('Rapport participants')
        ->getContent();

    expect($html)->not->toContain("tab='reviews'");   // onglet Relectures absent
    expect($html)->not->toContain("tab='submissions'");
});

it('supprime un paiement en attente ou échoué, jamais un paiement réglé', function () {
    $event = openEdition();
    $pending = Payment::create(['event_id' => $event->id, 'amount' => 1000, 'method' => 'fedapay', 'gateway' => 'fedapay', 'status' => 'pending']);
    $paid = Payment::create(['event_id' => $event->id, 'amount' => 2000, 'method' => 'cash', 'gateway' => 'manual', 'status' => 'paid']);

    $this->actingAs(adminStaff(), 'web')->delete(route('admin.payments.destroy', $pending))->assertRedirect();
    expect(Payment::find($pending->id))->toBeNull();

    // Un paiement réglé porte un reçu et ouvre des droits : il se rembourse.
    $this->actingAs(adminStaff(), 'web')->delete(route('admin.payments.destroy', $paid))->assertRedirect();
    expect(Payment::find($paid->id))->not->toBeNull();
});

it('n’affiche plus les relances de paiement sur la page Paiements', function () {
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertDontSee('Relances de paiement');
});

it('valide un rapport en un clic sans écraser son contenu', function () {
    $event = openEdition();
    $session = $event->sessions()->create([
        'label' => 'Séance', 'type' => 'atelier', 'starts_at' => now()->subHour(), 'rapporteur_email' => 'rap@example.tg',
    ]);
    SessionReport::create([
        'session_id' => $session->id, 'event_id' => $event->id,
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
    $event = openEdition();
    $session = $event->sessions()->create([
        'label' => 'Session à valider', 'type' => 'atelier',
        'starts_at' => now()->subHour(), 'rapporteur_email' => 'rap@example.tg',
    ]);
    SessionReport::create([
        'session_id' => $session->id, 'event_id' => $event->id,
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
    $event = openEdition();
    $intrus = eventActor('intrus@example.tg');
    $session = $event->sessions()->create([
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
    $event = openEdition();
    $session = $event->sessions()->create([
        'label' => 'Session - Atelier 4', 'type' => 'atelier',
        'rapporteur_email' => 'rap@example.tg', 'rapporteur_name' => 'Rita Rapporteuse',
    ]);
    SessionReport::create([
        'session_id' => $session->id, 'event_id' => $event->id,
        'summary' => 'Synthèse détaillée de la séance.',
        'highlights' => 'Publier les actes.', 'attendance' => 42,
        'status' => 'submitted', 'submitted_at' => now(),
    ]);

    // Fusionné dans le tableau de bord : le rapport soumis y figure (à valider),
    // son contenu étant chargé dans le panneau latéral de validation.
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.secretariat'))
        ->assertOk()
        ->assertSee('Session - Atelier 4')
        ->assertSee('Rita Rapporteuse')
        ->assertSee('Publier les actes.')
        ->assertSee('À valider');
});

it('redirige l’ancienne page /secretariat/rapports vers le tableau de bord', function () {
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.secretariat.reports', ['event_id' => 1]))
        ->assertRedirect(route('admin.secretariat'));
});

it('notifie le modérateur et le rapporteur à leur désignation sur une séance', function () {
    $event = openEdition();
    $mod = eventActor('newmod@example.tg');
    $rap = eventActor('newrap@example.tg');

    $this->actingAs(adminStaff(), 'web')->post(route('admin.program.sessions.store'), [
        'event_id' => (string) $event->id,
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

it('relance le rapporteur par notification silencieuse et e-mail du modèle', function () {
    Mail::fake();
    $event = openEdition();
    $rap = eventActor('raprelance@example.tg');
    $session = $event->sessions()->create([
        'label' => 'Séance à rapporter', 'type' => 'atelier',
        'starts_at' => now()->subHour(),
        'rapporteur_email' => 'raprelance@example.tg', 'rapporteur_name' => 'Rita R.',
    ]);

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.secretariat.remind', $session))
        ->assertRedirect();

    // Notification in-app (silencieuse) au rapporteur + e-mail du modèle.
    expect($rap->notifications()->where('type', SystemNotification::class)->count())->toBe(1);
    expect($rap->notifications()->first()->data['title'])->toBe('Rapport de séance à rédiger');
    Mail::assertSent(CampaignMail::class);
});

it('refuse la relance sans rapporteur désigné', function () {
    $event = openEdition();
    $session = $event->sessions()->create(['label' => 'Séance sans rapporteur', 'type' => 'plenary']);

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.secretariat.remind', $session))
        ->assertRedirect();
    expect(session('ukc_error'))->not->toBeNull();
});

it('ne renotifie pas un modérateur inchangé lors d’une mise à jour de séance', function () {
    $event = openEdition();
    $mod = eventActor('stable@example.tg');
    $session = $event->sessions()->create([
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
    $event = openEdition();
    $rev = eventActor('rev@example.tg');
    $reviewer = $event->participants()->create([
        'user_id' => $rev->id, 'first_name' => 'Test', 'last_name' => 'Acteur',
        'email' => 'rev@example.tg', 'role' => 'reviewer', 'access_token' => Str::random(64),
    ]);
    $participant = $event->participants()->create([
        'user_id' => null, 'first_name' => 'Auteur', 'last_name' => 'X', 'email' => 'auteur@example.tg',
    ]);
    $submission = $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-777', 'title' => 'Étude sur la durabilité',
        'abstract' => 'Résumé.', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'under_review',
        'authors' => [['name' => 'Auteur X', 'corresponding' => true]],
    ]);
    Review::create(['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'invited']);

    $this->actingAs($rev, 'account')
        ->get(route('account.reviews'))
        ->assertOk()
        ->assertSee('Étude sur la durabilité')
        ->assertSee('À évaluer')
        ->assertDontSee('Auteur X'); // en aveugle : pas d'auteur
});

it('enregistre une évaluation de relecteur en place (AJAX)', function () {
    $event = openEdition();
    $rev = eventActor('rev2@example.tg');
    $reviewer = $event->participants()->create([
        'user_id' => $rev->id, 'first_name' => 'Test', 'last_name' => 'Acteur',
        'email' => 'rev2@example.tg', 'role' => 'reviewer', 'access_token' => Str::random(64),
    ]);
    $participant = $event->participants()->create([
        'user_id' => null, 'first_name' => 'Auteur', 'last_name' => 'Z', 'email' => 'auteurz@example.tg',
    ]);
    $submission = $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-778', 'title' => 'Autre étude',
        'abstract' => 'Résumé.', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'under_review',
        'authors' => [],
    ]);
    $review = Review::create(['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'invited']);

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
    $event = openEdition();
    $intrus = eventActor('intrus2@example.tg');
    $reviewer = $event->participants()->create([
        'user_id' => null, 'first_name' => 'Autre', 'last_name' => 'Relecteur',
        'email' => 'autre@example.tg', 'role' => 'reviewer', 'access_token' => Str::random(64),
    ]);
    $participant = $event->participants()->create(['user_id' => null, 'first_name' => 'A', 'last_name' => 'B', 'email' => 'ab@example.tg']);
    $submission = $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-779', 'title' => 'X',
        'abstract' => 'x', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'under_review', 'authors' => [],
    ]);
    $review = Review::create(['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id, 'status' => 'invited']);

    $this->actingAs($intrus, 'account')
        ->postJson(route('account.reviews.submit', $review), ['recommendation' => 'accept'])
        ->assertForbidden();
});

it('filtre les participants par paiement et par invitation', function () {
    $event = openEdition(['formation_enabled' => true]);

    $payeur = $event->participants()->create(['first_name' => 'Paul', 'last_name' => 'Payeur', 'email' => 'paul@test.tg', 'role' => 'author']);
    Payment::create(['event_id' => $event->id, 'participant_id' => $payeur->id, 'amount' => 25000, 'method' => 'cash', 'gateway' => 'manual', 'status' => 'paid']);

    // Ne règle QU'UNE formation : ne doit pas ressortir dans « Ont payé ».
    $stagiaire = $event->participants()->create(['first_name' => 'Sara', 'last_name' => 'Stagiaire', 'email' => 'sara@test.tg', 'role' => 'trainee']);
    Payment::create([
        'event_id' => $event->id, 'participant_id' => $stagiaire->id, 'amount' => 5000,
        'method' => 'cash', 'gateway' => 'manual', 'status' => 'paid', 'meta' => ['flow' => 'formation', 'formation_id' => 1],
    ]);

    $invite = $event->participants()->create([
        'first_name' => 'Ines', 'last_name' => 'Invitee', 'email' => 'ines@test.tg', 'role' => 'author',
        'meta' => ['invited_at' => now()->toDateTimeString()],
    ]);

    $admin = adminStaff();
    $url = fn (array $q) => route('admin.participants.index', $q + ['event_id' => $event->id]);

    $this->actingAs($admin, 'web')->get($url(['paid' => '1']))
        ->assertOk()->assertSee('Paul Payeur')->assertDontSee('Sara Stagiaire');

    $this->actingAs($admin, 'web')->get($url(['paid' => '0']))
        ->assertOk()->assertSee('Sara Stagiaire')->assertDontSee('Paul Payeur');

    $this->actingAs($admin, 'web')->get($url(['invited' => '1']))
        ->assertOk()->assertSee('Ines Invitee')->assertDontSee('Paul Payeur');

    $this->actingAs($admin, 'web')->get($url(['invited' => '0']))
        ->assertOk()->assertSee('Paul Payeur')->assertDontSee('Ines Invitee');
});

it('n’expose plus « En tant que » dans la liste des participants', function () {
    $event = openEdition();
    $event->participants()->create(['first_name' => 'Paul', 'last_name' => 'Payeur', 'email' => 'paul@test.tg', 'role' => 'author']);

    // L'impersonation reste accessible depuis « Sa page », pas depuis la liste.
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.participants.index', ['event_id' => $event->id]))
        ->assertOk()
        ->assertSee('Sa page')
        ->assertDontSee('En tant que');
});
