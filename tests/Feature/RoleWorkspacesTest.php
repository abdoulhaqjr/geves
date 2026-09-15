<?php

use App\Models\Review;
use App\Models\SessionReport;
use App\Models\User;
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
        'label' => 'Session — Atelier 1', 'type' => 'atelier', 'room' => 'Salle 1',
        'moderator_email' => 'mod@example.tg', 'moderator_name' => 'Test Acteur',
    ]);

    $this->actingAs($mod, 'account')
        ->get(route('account.moderation'))
        ->assertOk()
        ->assertSee('Session — Atelier 1')
        ->assertSee('Espace d’échange');
});

it('affiche l’état vide pour un compte sans séance à animer', function () {
    $mod = eventActor('rien@example.tg');

    $this->actingAs($mod, 'account')
        ->get(route('account.moderation'))
        ->assertOk()
        ->assertSee('Aucune séance à animer');
});

it('permet au rapporteur de rédiger et soumettre un rapport de séance', function () {
    $edition = openEdition();
    $rap = eventActor('rap@example.tg');
    $session = $edition->sessions()->create([
        'label' => 'Session — Atelier 2', 'type' => 'atelier',
        'rapporteur_email' => 'rap@example.tg', 'rapporteur_name' => 'Test Acteur',
    ]);

    $this->actingAs($rap, 'account')
        ->get(route('account.reports'))
        ->assertOk()
        ->assertSee('Session — Atelier 2')
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

it('refuse le rapport d’une séance à un non-rapporteur', function () {
    $edition = openEdition();
    $intrus = eventActor('intrus@example.tg');
    $session = $edition->sessions()->create([
        'label' => 'Session — Atelier 3', 'type' => 'atelier',
        'rapporteur_email' => 'quelquun-dautre@example.tg',
    ]);

    $this->actingAs($intrus, 'account')
        ->get(route('account.reports.edit', $session))
        ->assertForbidden();

    $this->actingAs($intrus, 'account')
        ->post(route('account.reports.update', $session), ['action' => 'draft'])
        ->assertForbidden();
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
        ->assertSee($reviewer->access_token); // lien vers le portail relecteur
});
