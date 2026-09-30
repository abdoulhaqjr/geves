<?php

use App\Models\User;

// adminStaff() -> AdminSubmissionWorkflowTest ; seededSubmission() -> idem

function scheduledEdition(): array
{
    $submission = seededSubmission('accepted'); // édition ouverte « uk-2026 », comm. « Innovation durable »
    $edition = $submission->edition;

    $plenary = $edition->sessions()->create(['type' => 'plenary', 'label' => 'Cérémonie d’ouverture', 'ordre' => 0, 'room' => 'Amphi A']);
    $atelier = $edition->ateliers()->create(['title' => 'Atelier Santé', 'tracks' => ['Santé'], 'ordre' => 0]);
    $aSession = $edition->sessions()->create(['type' => 'atelier', 'atelier_id' => $atelier->id, 'label' => 'Session parallèle 1', 'ordre' => 1]);
    $edition->slots()->create([
        'session_id' => $aSession->id,
        'submission_id' => $submission->id,
        'participant_id' => $submission->participant_id,
    ]);

    return [$edition, $submission];
}

it('affiche le programme synthétique saisi (et pas le détail des communications)', function () {
    [$edition, $submission] = scheduledEdition();

    // Le programme public vient du contenu SAISI dans l'onglet « Programme »
    // (settings.program_synthesis), pas d'un agenda auto des sessions.
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'program_synthesis' => [
            'title' => 'Programme synthétique du colloque',
            'days' => [
                ['heading' => 'Lundi - Matin', 'rows' => [
                    ['time' => '08h30 - 10h15', 'label' => 'Cérémonie d’ouverture', 'highlight' => true],
                    ['time' => '', 'label' => 'Photo de famille & Cocktail', 'highlight' => false],
                ]],
            ],
        ],
    ])]);

    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertSee('Programme synthétique du colloque')
        ->assertSee('Lundi - Matin')
        ->assertSee('Cérémonie d’ouverture', false)
        ->assertSee('Photo de famille &amp; Cocktail', false)
        // Le détail des communications n'est jamais exposé au public.
        ->assertDontSee('Innovation durable');
});

it('n’affiche pas de programme quand aucun contenu n’est saisi', function () {
    // Une édition avec des sessions mais SANS programme synthétique saisi : la
    // section publique reste masquée (elle ne dépend plus des sessions).
    [$edition] = scheduledEdition();

    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertDontSee('>Programme<', false);
});

it('enregistre le programme synthétique saisi et le publie', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $edition = $submission->edition;

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.synthesis'), [
            'edition_id' => $edition->id,
            'title' => 'Programme du colloque',
            'days' => [
                ['heading' => 'Jour 1 : Matin', 'rows' => [
                    ['time' => '09h00 - 10h30', 'label' => 'Ouverture solennelle', 'highlight' => '1'],
                    ['time' => '', 'label' => '', 'highlight' => '0'], // ligne vide → ignorée
                ]],
                ['heading' => '', 'rows' => []], // jour vide → ignoré
            ],
        ])
        ->assertRedirect();

    $syn = $edition->fresh()->settings['program_synthesis'];
    expect($syn['title'])->toBe('Programme du colloque');
    expect($syn['days'])->toHaveCount(1);
    expect($syn['days'][0]['rows'])->toHaveCount(1);
    expect($syn['days'][0]['rows'][0]['highlight'])->toBeTrue();

    // Publié tel quel sur la page du colloque.
    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertSee('Programme du colloque')
        ->assertSee('Jour 1 : Matin')
        ->assertSee('Ouverture solennelle');
});

it('génère les fiches de session imprimables', function () {
    $staff = adminStaff();
    [$edition, $submission] = scheduledEdition();

    $this->actingAs($staff, 'web')->get(route('admin.documents.sessions', $edition))
        ->assertOk()
        ->assertSee('Fiches de session')
        ->assertSee('Session parallèle 1')
        ->assertSee('Innovation durable')
        ->assertSee('Cérémonie d’ouverture', false);
});

it('protège les fiches de session sans permission', function () {
    [$edition] = scheduledEdition();
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get(route('admin.documents.sessions', $edition))->assertForbidden();
});
