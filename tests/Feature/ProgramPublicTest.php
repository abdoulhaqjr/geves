<?php

use App\Models\User;

// adminStaff() -> AdminSubmissionWorkflowTest ; seededSubmission() -> idem

function scheduledEdition(): array
{
    $submission = seededSubmission('accepted'); // événement ouverte « uk-2026 », comm. « Innovation durable »
    $event = $submission->event;

    $plenary = $event->sessions()->create(['type' => 'plenary', 'label' => 'Cérémonie d’ouverture', 'ordre' => 0, 'room' => 'Amphi A']);
    $atelier = $event->ateliers()->create(['title' => 'Atelier Santé', 'tracks' => ['Santé'], 'ordre' => 0]);
    $aSession = $event->sessions()->create(['type' => 'atelier', 'atelier_id' => $atelier->id, 'label' => 'Session parallèle 1', 'ordre' => 1]);
    $event->slots()->create([
        'session_id' => $aSession->id,
        'submission_id' => $submission->id,
        'participant_id' => $submission->participant_id,
    ]);

    return [$event, $submission];
}

it('affiche le programme synthétique saisi (et pas le détail des communications)', function () {
    [$event, $submission] = scheduledEdition();

    // Le programme public vient du contenu SAISI dans l'onglet « Programme »
    // (settings.program_synthesis), pas d'un agenda auto des sessions.
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'program_synthesis' => [
            'title' => 'Programme synthétique du colloque',
            'days' => [
                ['date' => '2026-09-27', 'heading' => 'Matin', 'rows' => [
                    ['type' => 'plenary', 'start' => '08:30', 'end' => '10:15', 'label' => 'Cérémonie d’ouverture', 'highlight' => true],
                    ['type' => 'break', 'start' => '10:15', 'end' => '10:45', 'label' => 'Photo de famille & Cocktail', 'highlight' => false],
                ]],
            ],
        ],
    ])]);

    $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertSee('Programme synthétique du colloque')
        ->assertSee('Matin')
        ->assertSee('08h30 - 10h15')
        ->assertSee('Cérémonie d’ouverture', false)
        ->assertSee('Photo de famille &amp; Cocktail', false)
        // Le détail des communications n'est jamais exposé au public.
        ->assertDontSee('Innovation durable');
});

it('n’affiche pas de programme quand aucun contenu n’est saisi', function () {
    // Une événement avec des sessions mais SANS programme synthétique saisi : la
    // section publique reste masquée (elle ne dépend plus des sessions).
    [$event] = scheduledEdition();

    $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertDontSee('>Programme<', false);
});

it('enregistre le programme synthétique saisi et le publie', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $event = $submission->event;

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.synthesis'), [
            'event_id' => $event->id,
            'title' => 'Programme du colloque',
            'days' => [
                ['date' => '2026-09-27', 'heading' => 'Jour 1 : Matin', 'rows' => [
                    ['type' => 'plenary', 'start' => '09:00', 'end' => '10:30', 'label' => 'Ouverture solennelle', 'highlight' => '1'],
                    ['type' => 'other', 'start' => '', 'end' => '', 'label' => '', 'highlight' => '0'], // ligne vide → ignorée
                ]],
                ['date' => '', 'heading' => '', 'rows' => []], // jour vide → ignoré
            ],
        ])
        ->assertRedirect();

    $syn = $event->fresh()->settings['program_synthesis'];
    expect($syn['title'])->toBe('Programme du colloque');
    expect($syn['days'])->toHaveCount(1);
    expect($syn['days'][0]['rows'])->toHaveCount(1);
    expect($syn['days'][0]['rows'][0]['highlight'])->toBeTrue();
    expect($syn['days'][0]['rows'][0]['start'])->toBe('09:00');

    // Publié tel quel sur la page du colloque.
    $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertSee('Programme du colloque')
        ->assertSee('Jour 1 : Matin')
        ->assertSee('Ouverture solennelle');
});

it('génère les sessions et créneaux depuis le programme (et régénère sans doublonner)', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $event = $submission->event;
    $event->ateliers()->create(['title' => 'Atelier Santé', 'tracks' => ['Santé'], 'ordre' => 0]);
    $ateliersCount = $event->ateliers()->count();

    $payload = [
        'event_id' => $event->id,
        'title' => 'Programme',
        'days' => [
            ['date' => '2026-09-27', 'heading' => 'Matin', 'rows' => [
                ['type' => 'plenary', 'start' => '09:00', 'end' => '10:30', 'label' => 'Ouverture', 'highlight' => '1'],
                ['type' => 'break', 'start' => '10:30', 'end' => '11:00', 'label' => 'Pause', 'highlight' => '0'],
                ['type' => 'atelier', 'start' => '11:00', 'end' => '12:00', 'label' => 'Communications en ateliers', 'highlight' => '0'],
            ]],
        ],
    ];

    $this->actingAs($staff, 'web')->post(route('admin.program.synthesis.generate'), $payload)->assertRedirect();

    // 1 plénière + 1 session par atelier ; la pause ne crée rien.
    expect($event->sessions()->where('type', 'plenary')->count())->toBe(1);
    expect($event->sessions()->where('type', 'atelier')->count())->toBe($ateliersCount);
    // La session d'atelier (11:00-12:00) est découpée en créneaux libres.
    expect($event->slots()->count())->toBeGreaterThan(0);

    $sessionsAfterFirst = $event->sessions()->count();

    // Régénérer remplace (pas de doublon).
    $this->actingAs($staff, 'web')->post(route('admin.program.synthesis.generate'), $payload)->assertRedirect();
    expect($event->sessions()->count())->toBe($sessionsAfterFirst);
});

it('génère les fiches de session imprimables', function () {
    $staff = adminStaff();
    [$event, $submission] = scheduledEdition();

    $this->actingAs($staff, 'web')->get(route('admin.documents.sessions', $event))
        ->assertOk()
        ->assertSee('Fiches de session')
        ->assertSee('Session parallèle 1')
        ->assertSee('Innovation durable')
        ->assertSee('Cérémonie d’ouverture', false);
});

it('protège les fiches de session sans permission', function () {
    [$event] = scheduledEdition();
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get(route('admin.documents.sessions', $event))->assertForbidden();
});

/**
 * ⚠️ **Un onglet = UN JOUR, pas un bloc de saisie.** Une journée se saisit
 * souvent en deux blocs (« Matin », « Après-midi ») : un onglet par bloc donnait
 * quatre pastilles « Matin » côte à côte, et le visiteur qui cherchait le
 * 3 octobre ne savait pas laquelle ouvrir. Les blocs d'une même date se rangent
 * donc DANS l'onglet de leur jour, en intertitres.
 */
it('range les blocs d’une même journée sous un seul onglet de jour', function () {
    [$event] = scheduledEdition();

    $event->update(['settings' => array_merge($event->settings ?? [], [
        'program_synthesis' => [
            'title' => 'Programme',
            'days' => [
                ['date' => '2026-09-27', 'heading' => 'Matin', 'rows' => [
                    ['type' => 'plenary', 'start' => '08:30', 'end' => '10:15', 'label' => 'Ouverture', 'highlight' => true],
                ]],
                ['date' => '2026-09-27', 'heading' => 'Après-midi', 'rows' => [
                    ['type' => 'atelier', 'start' => '14:00', 'end' => '17:00', 'label' => 'Communications', 'highlight' => false],
                ]],
                ['date' => '2026-09-28', 'heading' => 'Matin', 'rows' => [
                    ['type' => 'plenary', 'start' => '09:00', 'end' => '12:00', 'label' => 'Clôture', 'highlight' => false],
                ]],
            ],
        ],
    ])]);

    $html = $this->get(route('public.colloque', $event->slug))->assertOk()->getContent();

    // Deux jours saisis en trois blocs : deux onglets, pas trois.
    expect(substr_count($html, 'ukc-tab--day'))->toBe(2);
    // Les intitulés de bloc restent, en intertitres.
    expect(substr_count($html, 'ukc-prog-syn__bloc'))->toBe(3);
    // Et le contenu des deux blocs du 27 cohabite dans le même panneau.
    expect($html)->toContain('Ouverture')->toContain('Communications')->toContain('Clôture');
});

/**
 * ⚠️ Un bloc SANS date garde son propre onglet : rien ne dit à quel jour il
 * appartient, et le coller au précédent inventerait une information.
 */
it('laisse son propre onglet à un bloc sans date', function () {
    [$event] = scheduledEdition();

    $event->update(['settings' => array_merge($event->settings ?? [], [
        'program_synthesis' => [
            'title' => 'Programme',
            'days' => [
                ['date' => '2026-09-27', 'heading' => 'Matin', 'rows' => [
                    ['type' => 'plenary', 'start' => '08:30', 'end' => '10:15', 'label' => 'Ouverture', 'highlight' => false],
                ]],
                ['date' => '', 'heading' => 'Visites libres', 'rows' => [
                    ['type' => 'other', 'start' => '', 'end' => '', 'label' => 'Sur inscription', 'highlight' => false],
                ]],
            ],
        ],
    ])]);

    $html = $this->get(route('public.colloque', $event->slug))->assertOk()->getContent();

    expect(substr_count($html, 'ukc-tab--day'))->toBe(2);
    expect($html)->toContain('Visites libres');
});
