<?php

use App\Models\Atelier;
use App\Models\EventSession;
use App\Models\Slot;

// adminStaff(), seededSubmission() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

it('crée un atelier et des sessions pour un événement', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.program.ateliers.store'), [
        'event_id' => (string) $event->id,
        'atelierTitle' => 'Atelier Santé',
        'atelierTracks' => ['Santé'],
    ])->assertSessionHasNoErrors();

    $atelier = Atelier::first();
    expect($atelier->title)->toBe('Atelier Santé');
    expect($atelier->tracks)->toBe(['Santé']);

    $this->actingAs($staff, 'web')->post(route('admin.program.sessions.store'), [
        'event_id' => (string) $event->id,
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
    $event = $submission->event;

    $this->actingAs($staff, 'web')->post(route('admin.program.slots.store'), [
        'event_id' => (string) $event->id,
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
    $event = $submission->event;

    // Un créneau libre dans l'événement.
    $slot = Slot::create(['event_id' => $event->id, 'room' => 'Amphi A', 'submission_id' => 0]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.slots.auto', ['event_id' => $event->id]), ['match_axis' => '0'])
        ->assertSessionHasNoErrors();

    expect($slot->fresh()->submission_id)->toBe($submission->id);
});

it('met à jour un créneau (heure et communication assignée)', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $event = $submission->event;
    $slot = Slot::create(['event_id' => $event->id, 'room' => 'Amphi A', 'submission_id' => 0]);

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
    $event = openEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.config', ['event_id' => $event->id]), [
            'auto_enabled' => '1',
            'oral_min' => '25',
            'day_start' => '09:00',
        ])->assertSessionHasNoErrors();

    $program = $event->fresh()->settings['program'];
    expect($program['auto']['enabled'])->toBeTrue();
    expect($program['params']['oral_min'])->toBe(25);
    expect($program['params']['day_start'])->toBe('09:00');
});

it('ajoute puis supprime un espace', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.spaces.store', ['event_id' => $event->id]), [
            'spaceName' => 'Amphithéâtre principal',
            'spaceCapacity' => '250',
            'spaceBuilding' => 'Bâtiment A',
        ])->assertSessionHasNoErrors();

    $spaces = $event->fresh()->settings['program']['spaces'];
    expect($spaces)->toHaveCount(1);
    expect($spaces[0]['name'])->toBe('Amphithéâtre principal');

    $this->actingAs($staff, 'web')
        ->delete(route('admin.program.spaces.destroy', ['index' => 0, 'event_id' => $event->id]))
        ->assertSessionHasNoErrors();

    expect($event->fresh()->settings['program']['spaces'])->toBeEmpty();
});

it('laisse le participant choisir un créneau libre pour sa communication acceptée', function () {
    $submission = seededSubmission('accepted');
    $account = $submission->participant->user;
    $slot = Slot::create(['event_id' => $submission->event_id, 'room' => 'Amphi B', 'submission_id' => 0]);

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
    $slot = Slot::create(['event_id' => $submission->event_id, 'room' => 'Amphi B', 'submission_id' => 0]);

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.choose-slot', $submission), ['slot_id' => $slot->id])
        ->assertForbidden();
});

it('génère des créneaux libres à partir des sessions horodatées', function () {
    $staff = adminStaff();
    $event = openEdition();
    // Pauses désactivées pour tester le découpage pur : 90 min / 20 min = 4 créneaux.
    // (Les plénières, exclusives, ne sont pas découpées en créneaux.)
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'program' => ['params' => ['oral_min' => 20, 'auto_break' => false, 'lunch' => false]],
    ])]);
    $event->sessions()->create([
        'type' => 'atelier', 'atelier_id' => 0, 'label' => 'Session A',
        'session_day' => '2026-11-18', 'starts_at' => '2026-11-18 14:00:00', 'ends_at' => '2026-11-18 15:30:00', 'ordre' => 0,
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.slots.generate').'?event_id='.$event->id)
        ->assertSessionHasNoErrors();

    expect($event->slots()->count())->toBe(4);
    expect($event->slots()->where('submission_id', 0)->count())->toBe(4);

    // Idempotent : la session déjà pourvue n'est pas redécoupée.
    $this->actingAs($staff, 'web')->post(route('admin.program.slots.generate').'?event_id='.$event->id);
    expect($event->slots()->count())->toBe(4);
});

it('respecte la pause déjeuner lors de la génération des créneaux', function () {
    $staff = adminStaff();
    $event = openEdition();
    // Créneaux de 30 min, pause déjeuner 12:30-14:00 : aucun créneau ne doit
    // tomber dans cette fenêtre.
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'program' => ['params' => [
            'oral_min' => 30, 'auto_break' => false,
            'lunch' => true, 'lunch_start' => '12:30', 'lunch_end' => '14:00',
        ]],
    ])]);
    $event->sessions()->create([
        'type' => 'atelier', 'atelier_id' => 0, 'label' => 'Session A',
        'session_day' => '2026-11-18', 'starts_at' => '2026-11-18 11:30:00', 'ends_at' => '2026-11-18 14:30:00', 'ordre' => 0,
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.slots.generate').'?event_id='.$event->id)
        ->assertSessionHasNoErrors();

    // 11:30-12:00, 12:00-12:30, [déjeuner], 14:00-14:30 = 3 créneaux.
    expect($event->slots()->count())->toBe(3);
    // Aucun créneau ne commence dans la fenêtre déjeuner.
    $inLunch = $event->slots()->get()->filter(function ($s) {
        $m = (int) $s->starts_at->format('H') * 60 + (int) $s->starts_at->format('i');

        return $m >= 750 && $m < 840; // 12:30 (750) .. 14:00 (840)
    });
    expect($inLunch)->toHaveCount(0);
});

it('insère une pause-café après N créneaux lors de la génération', function () {
    $staff = adminStaff();
    $event = openEdition();
    // 30 min/créneau, pause-café de 60 min après 2 créneaux : sur 14:00-15:30,
    // 2 créneaux (14:00-14:30, 14:30-15:00) puis la pause épuise la session.
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'program' => ['params' => [
            'oral_min' => 30, 'lunch' => false,
            'auto_break' => true, 'break_after' => 2, 'break_min' => 60,
        ]],
    ])]);
    $event->sessions()->create([
        'type' => 'atelier', 'atelier_id' => 0, 'label' => 'Session A',
        'session_day' => '2026-11-18', 'starts_at' => '2026-11-18 14:00:00', 'ends_at' => '2026-11-18 15:30:00', 'ordre' => 0,
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.slots.generate').'?event_id='.$event->id)
        ->assertSessionHasNoErrors();

    expect($event->slots()->count())->toBe(2);
});

it('affecte automatiquement les communications acceptées aux créneaux libres', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $event = $submission->event;
    Slot::create(['event_id' => $event->id, 'room' => 'Salle A', 'submission_id' => 0, 'starts_at' => '2026-11-18 14:00:00']);
    Slot::create(['event_id' => $event->id, 'room' => 'Salle A', 'submission_id' => 0, 'starts_at' => '2026-11-18 14:20:00']);

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.slots.auto').'?event_id='.$event->id, ['match_axis' => '1'])
        ->assertSessionHasNoErrors();

    expect($event->slots()->where('submission_id', $submission->id)->count())->toBe(1);
});

it('associe une salle à un atelier et la propage à ses sessions', function () {
    $staff = adminStaff();
    $event = openEdition();
    $atelier = $event->ateliers()->create(['title' => 'Atelier Santé', 'tracks' => ['Santé'], 'ordre' => 0]);
    $session = $event->sessions()->create(['type' => 'atelier', 'atelier_id' => $atelier->id, 'label' => 'S1', 'ordre' => 0]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.ateliers.room', $atelier).'?event_id='.$event->id, ['atelierRoom' => 'Salle B'])
        ->assertSessionHasNoErrors()
        // ⚠️ La valeur nomme déjà le lieu : le message disait « Salle « Salle B »
        // associée ». On vérifie l'ABSENCE du doublon plutôt que le libellé exact,
        // qui changerait à la première reformulation sans que la règle bouge.
        ->assertSessionHas('ukc_status', fn (string $m) => ! str_contains($m, 'Salle « Salle B »'));

    expect($event->fresh()->settings['program']['atelier_rooms'][(string) $atelier->id])->toBe('Salle B');
    expect($session->fresh()->room)->toBe('Salle B');
});

it('regroupe le programme en un seul module à onglets', function () {
    $staff = adminStaff();
    $event = openEdition();

    // Les huit onglets répondent sur la même page, pilotés par l'URL.
    foreach (['planning', 'ateliers', 'sessions', 'creneaux', 'espaces', 'auto', 'rapports', 'options'] as $tab) {
        $this->actingAs($staff, 'web')
            ->get(route('admin.program.index', ['event_id' => $event->id, 'tab' => $tab]))
            ->assertOk();
    }

    // Un onglet inconnu retombe sur le planning plutôt que d'échouer.
    $this->actingAs($staff, 'web')
        ->get(route('admin.program.index', ['event_id' => $event->id, 'tab' => 'inexistant']))
        ->assertOk();
});

it('redirige les anciennes adresses du programme vers le bon onglet', function () {
    $staff = adminStaff();
    $event = openEdition();

    // ⚠️ Liens et signets existants (secrétariat, documents) : ils doivent
    // continuer de tomber juste après le regroupement des deux pages.
    $this->actingAs($staff, 'web')
        ->get(route('admin.program.sessions', ['event_id' => $event->id]))
        ->assertRedirect(route('admin.program.index', ['event_id' => $event->id, 'tab' => 'sessions']));

    // ⚠️ Les rapports de séance relèvent du Secrétariat, pas du Programme :
    // l'ancienne adresse y renvoie plutôt que vers un onglet qui n'existe plus.
    $this->actingAs($staff, 'web')
        ->get(route('admin.program.reports', ['event_id' => $event->id]))
        ->assertRedirect(route('admin.secretariat.reports', ['event_id' => $event->id]));

    $this->actingAs($staff, 'web')
        ->get(route('admin.program.slots', ['event_id' => $event->id]))
        ->assertRedirect(route('admin.program.index', ['event_id' => $event->id, 'tab' => 'planning']));
});

it('porte la visioconférence sur l’onglet Sessions', function () {
    $staff = adminStaff();
    $event = openEdition();
    $session = sessionFor($event);

    // Elle n'existait que sur l'ancienne page, inaccessible depuis le menu.
    $this->actingAs($staff, 'web')
        ->get(route('admin.program.index', ['event_id' => $event->id, 'tab' => 'sessions']))
        ->assertOk()
        ->assertSee($session->label)
        ->assertSee('Visioconférence');
});
