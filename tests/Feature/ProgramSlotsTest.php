<?php

use App\Models\Atelier;
use App\Models\EventSession;
use App\Models\Slot;

// adminStaff(), seededSubmission() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

it('crée un atelier et des sessions pour une édition', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.program.ateliers.store'), [
        'edition_id' => (string) $edition->id,
        'atelierTitle' => 'Atelier Santé',
        'atelierTracks' => ['Santé'],
    ])->assertSessionHasNoErrors();

    $atelier = Atelier::first();
    expect($atelier->title)->toBe('Atelier Santé');
    expect($atelier->tracks)->toBe(['Santé']);

    $this->actingAs($staff, 'web')->post(route('admin.program.sessions.store'), [
        'edition_id' => (string) $edition->id,
        'sType' => 'plenary',
        'sLabel' => 'Cérémonie d’ouverture',
        'sDay' => '2026-11-18',
        'sStart' => '09:00',
    ])->assertSessionHasNoErrors();

    $session = EventSession::first();
    expect($session->type)->toBe('plenary');
    expect($session->label)->toBe('Cérémonie d’ouverture');
    expect($session->starts_at->format('H:i'))->toBe('09:00');
});

it('crée un créneau et y assigne une communication acceptée', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $edition = $submission->edition;

    $this->actingAs($staff, 'web')->post(route('admin.program.slots.store'), [
        'edition_id' => (string) $edition->id,
        'slRoom' => 'Amphi A',
        'slDay' => '2026-11-18',
        'slStart' => '10:00',
    ])->assertSessionHasNoErrors();

    $slot = Slot::first();
    expect($slot->room)->toBe('Amphi A');

    $this->actingAs($staff, 'web')->post(route('admin.program.slots.assign', $slot), ['submission_id' => $submission->id]);
    expect($slot->fresh()->submission_id)->toBe($submission->id);
});

it('affecte automatiquement les communications acceptées non planifiées aux créneaux libres', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $edition = $submission->edition;

    // Un créneau libre dans l'édition.
    $slot = Slot::create(['edition_id' => $edition->id, 'room' => 'Amphi A', 'submission_id' => 0]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.slots.auto', ['edition_id' => $edition->id]), ['match_axis' => '0'])
        ->assertSessionHasNoErrors();

    expect($slot->fresh()->submission_id)->toBe($submission->id);
});

it('met à jour un créneau (heure et communication assignée)', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $edition = $submission->edition;
    $slot = Slot::create(['edition_id' => $edition->id, 'room' => 'Amphi A', 'submission_id' => 0]);

    $this->actingAs($staff, 'web')->put(route('admin.program.slots.update', $slot), [
        'slDay' => '2026-11-18',
        'slStart' => '10:30',
        'slRoom' => 'Salle B',
        'submission_id' => $submission->id,
    ])->assertSessionHasNoErrors();

    $slot->refresh();
    expect($slot->submission_id)->toBe($submission->id);
    expect($slot->room)->toBe('Salle B');
    expect($slot->starts_at->format('H:i'))->toBe('10:30');
});

it('enregistre les réglages du programme dans settings', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.config', ['edition_id' => $edition->id]), [
            'auto_enabled' => '1',
            'oral_min' => '25',
            'day_start' => '09:00',
        ])->assertSessionHasNoErrors();

    $program = $edition->fresh()->settings['program'];
    expect($program['auto']['enabled'])->toBeTrue();
    expect($program['params']['oral_min'])->toBe(25);
    expect($program['params']['day_start'])->toBe('09:00');
});

it('ajoute puis supprime un espace', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.spaces.store', ['edition_id' => $edition->id]), [
            'spaceName' => 'Amphithéâtre principal',
            'spaceCapacity' => '250',
            'spaceBuilding' => 'Bâtiment A',
        ])->assertSessionHasNoErrors();

    $spaces = $edition->fresh()->settings['program']['spaces'];
    expect($spaces)->toHaveCount(1);
    expect($spaces[0]['name'])->toBe('Amphithéâtre principal');

    $this->actingAs($staff, 'web')
        ->delete(route('admin.program.spaces.destroy', ['index' => 0, 'edition_id' => $edition->id]))
        ->assertSessionHasNoErrors();

    expect($edition->fresh()->settings['program']['spaces'])->toBeEmpty();
});

it('laisse le participant choisir un créneau libre pour sa communication acceptée', function () {
    $submission = seededSubmission('accepted');
    $account = $submission->participant->user;
    $slot = Slot::create(['edition_id' => $submission->edition_id, 'room' => 'Amphi B', 'submission_id' => 0]);

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.choose-slot', $submission), ['slot_id' => $slot->id])
        ->assertSessionHasNoErrors();

    $slot->refresh();
    expect($slot->submission_id)->toBe($submission->id);
    expect($slot->chosen_by_participant)->toBeTrue();
});

it('refuse le choix de créneau si la communication n’est pas acceptée', function () {
    $submission = seededSubmission('under_review');
    $account = $submission->participant->user;
    $slot = Slot::create(['edition_id' => $submission->edition_id, 'room' => 'Amphi B', 'submission_id' => 0]);

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.choose-slot', $submission), ['slot_id' => $slot->id])
        ->assertForbidden();
});

it('génère des créneaux libres à partir des sessions horodatées', function () {
    $staff = adminStaff();
    $edition = openEdition();
    // Session de 90 min → 4 créneaux de 20 min (durée orale par défaut).
    $edition->sessions()->create([
        'type' => 'plenary', 'atelier_id' => 0, 'label' => 'Session A',
        'session_day' => '2026-11-18', 'starts_at' => '2026-11-18 14:00:00', 'ends_at' => '2026-11-18 15:30:00', 'ordre' => 0,
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.slots.generate').'?edition_id='.$edition->id)
        ->assertSessionHasNoErrors();

    expect($edition->slots()->count())->toBe(4);
    expect($edition->slots()->where('submission_id', 0)->count())->toBe(4);

    // Idempotent : la session déjà pourvue n'est pas redécoupée.
    $this->actingAs($staff, 'web')->post(route('admin.program.slots.generate').'?edition_id='.$edition->id);
    expect($edition->slots()->count())->toBe(4);
});

it('affecte automatiquement les communications acceptées aux créneaux libres', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $edition = $submission->edition;
    Slot::create(['edition_id' => $edition->id, 'room' => 'Salle A', 'submission_id' => 0, 'starts_at' => '2026-11-18 14:00:00']);
    Slot::create(['edition_id' => $edition->id, 'room' => 'Salle A', 'submission_id' => 0, 'starts_at' => '2026-11-18 14:20:00']);

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.slots.auto').'?edition_id='.$edition->id, ['match_axis' => '1'])
        ->assertSessionHasNoErrors();

    expect($edition->slots()->where('submission_id', $submission->id)->count())->toBe(1);
});

it('associe une salle à un atelier et la propage à ses sessions', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $atelier = $edition->ateliers()->create(['title' => 'Atelier Santé', 'tracks' => ['Santé'], 'ordre' => 0]);
    $session = $edition->sessions()->create(['type' => 'atelier', 'atelier_id' => $atelier->id, 'label' => 'S1', 'ordre' => 0]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.ateliers.room', $atelier).'?edition_id='.$edition->id, ['atelierRoom' => 'Salle B'])
        ->assertSessionHasNoErrors();

    expect($edition->fresh()->settings['program']['atelier_rooms'][(string) $atelier->id])->toBe('Salle B');
    expect($session->fresh()->room)->toBe('Salle B');
});

it('regroupe le programme en un seul module à onglets', function () {
    $staff = adminStaff();
    $edition = openEdition();

    // Les huit onglets répondent sur la même page, pilotés par l'URL.
    foreach (['planning', 'ateliers', 'sessions', 'creneaux', 'espaces', 'auto', 'rapports', 'options'] as $tab) {
        $this->actingAs($staff, 'web')
            ->get(route('admin.program.index', ['edition_id' => $edition->id, 'tab' => $tab]))
            ->assertOk();
    }

    // Un onglet inconnu retombe sur le planning plutôt que d'échouer.
    $this->actingAs($staff, 'web')
        ->get(route('admin.program.index', ['edition_id' => $edition->id, 'tab' => 'inexistant']))
        ->assertOk();
});

it('redirige les anciennes adresses du programme vers le bon onglet', function () {
    $staff = adminStaff();
    $edition = openEdition();

    // ⚠️ Liens et signets existants (secrétariat, documents) : ils doivent
    // continuer de tomber juste après le regroupement des deux pages.
    $this->actingAs($staff, 'web')
        ->get(route('admin.program.sessions', ['edition_id' => $edition->id]))
        ->assertRedirect(route('admin.program.index', ['edition_id' => $edition->id, 'tab' => 'sessions']));

    $this->actingAs($staff, 'web')
        ->get(route('admin.program.reports', ['edition_id' => $edition->id]))
        ->assertRedirect(route('admin.program.index', ['edition_id' => $edition->id, 'tab' => 'rapports']));

    $this->actingAs($staff, 'web')
        ->get(route('admin.program.slots', ['edition_id' => $edition->id]))
        ->assertRedirect(route('admin.program.index', ['edition_id' => $edition->id, 'tab' => 'planning']));
});

it('porte la visioconférence sur l’onglet Sessions', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $session = sessionFor($edition);

    // Elle n'existait que sur l'ancienne page, inaccessible depuis le menu.
    $this->actingAs($staff, 'web')
        ->get(route('admin.program.index', ['edition_id' => $edition->id, 'tab' => 'sessions']))
        ->assertOk()
        ->assertSee($session->label)
        ->assertSee('Visioconférence');
});
