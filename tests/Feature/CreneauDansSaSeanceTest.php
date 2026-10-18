<?php

use App\Models\Atelier;
use App\Models\EventSession;
use App\Models\Slot;

// adminStaff() -> Pest.php ; openEdition() -> Pest.php ; makeSession() -> ProgramPlenaryExclusivityTest

/**
 * Un créneau se range DANS une séance : il en prend le jour, la salle et une
 * heure de sa fenêtre. Tout cela se saisissait à la main, donc tout cela pouvait
 * contredire la séance - un créneau de l'atelier 1 posé un autre jour, dans une
 * autre salle, à une heure où personne n'est en salle.
 *
 * ⚠️ Le formulaire déduit et borne (cf. `program-pure.js`), mais la décision est
 * ICI : un champ en lecture seule se retire en deux clics.
 */
function seanceAvecSalle(int $eventId, string $room = 'Salle 1'): EventSession
{
    $session = makeSession($eventId, 'atelier', '2026-10-12', '14:00', '17:00');
    $session->update(['room' => $room, 'label' => 'Atelier 1 - séance 1']);

    return $session->fresh();
}

it('prend le jour et la salle de sa séance, contre la saisie', function () {
    $staff = adminStaff();
    $event = openEdition();
    $session = seanceAvecSalle($event->id);

    $this->actingAs($staff, 'web')->post(route('admin.program.slots.store'), [
        'event_id' => (string) $event->id,
        'slSessionId' => (string) $session->id,
        // Le formulaire affiche ces deux champs en lecture seule ; on poste
        // quand même autre chose, comme le ferait une requête forgée.
        'slDay' => '2026-10-30',
        'slRoom' => 'Salle Z',
        'slStart' => '15:00',
    ])->assertSessionMissing('ukc_error');

    $slot = Slot::first();
    expect($slot->starts_at->format('Y-m-d H:i'))->toBe('2026-10-12 15:00');
    expect($slot->room)->toBe('Salle 1');
});

it('refuse une heure qui sort de la séance', function () {
    $staff = adminStaff();
    $event = openEdition();
    $session = seanceAvecSalle($event->id);

    $this->from(route('admin.program.index'))
        ->actingAs($staff, 'web')
        ->post(route('admin.program.slots.store'), [
            'event_id' => (string) $event->id,
            'slSessionId' => (string) $session->id,
            'slStart' => '18:00',
        ])->assertSessionHas('ukc_error');

    expect(Slot::count())->toBe(0);
});

it('accepte le début de la séance et refuse son heure de fin', function () {
    $staff = adminStaff();
    $event = openEdition();
    $session = seanceAvecSalle($event->id);

    // ⚠️ Commencer À l'heure de fin, c'est être hors de la séance.
    $this->actingAs($staff, 'web')->post(route('admin.program.slots.store'), [
        'event_id' => (string) $event->id,
        'slSessionId' => (string) $session->id,
        'slStart' => '14:00',
    ])->assertSessionMissing('ukc_error');
    expect(Slot::count())->toBe(1);

    $this->from(route('admin.program.index'))
        ->actingAs($staff, 'web')
        ->post(route('admin.program.slots.store'), [
            'event_id' => (string) $event->id,
            'slSessionId' => (string) $session->id,
            'slStart' => '17:00',
        ])->assertSessionHas('ukc_error');
    expect(Slot::count())->toBe(1);
});

it('n’exige pas que la durée du créneau tienne dans la séance', function () {
    // Un poster dure 90 min par défaut, bien plus que la séance qui l'accueille :
    // exiger que la fin y entre refuserait des placements normaux.
    $staff = adminStaff();
    $event = openEdition();
    $session = seanceAvecSalle($event->id);

    $this->actingAs($staff, 'web')->post(route('admin.program.slots.store'), [
        'event_id' => (string) $event->id,
        'slSessionId' => (string) $session->id,
        'slStart' => '16:55',
    ])->assertSessionMissing('ukc_error');

    expect(Slot::first()->starts_at->format('H:i'))->toBe('16:55');
});

it('commence avec sa séance quand aucune heure n’est saisie', function () {
    $staff = adminStaff();
    $event = openEdition();
    $session = seanceAvecSalle($event->id);

    $this->actingAs($staff, 'web')->post(route('admin.program.slots.store'), [
        'event_id' => (string) $event->id,
        'slSessionId' => (string) $session->id,
    ])->assertSessionMissing('ukc_error');

    expect(Slot::first()->starts_at->format('Y-m-d H:i'))->toBe('2026-10-12 14:00');
});

it('laisse la saisie faire foi quand la séance n’a pas d’horaires', function () {
    $staff = adminStaff();
    $event = openEdition();
    $session = EventSession::create([
        'event_id' => $event->id, 'type' => 'atelier', 'atelier_id' => 0, 'label' => 'Séance à caler',
    ]);

    $this->actingAs($staff, 'web')->post(route('admin.program.slots.store'), [
        'event_id' => (string) $event->id,
        'slSessionId' => (string) $session->id,
        'slDay' => '2026-10-30',
        'slStart' => '09:30',
        'slRoom' => 'Salle Z',
    ])->assertSessionMissing('ukc_error');

    $slot = Slot::first();
    expect($slot->starts_at->format('Y-m-d H:i'))->toBe('2026-10-30 09:30');
    expect($slot->room)->toBe('Salle Z');
});

it('range un créneau dans sa propre plénière, mais pas dans une autre', function () {
    // ⚠️ Un créneau rangé dans une plénière EST son contenu, pas une activité
    // concurrente. Sans cette exception, choisir une plénière dans le formulaire
    // devenait impossible dès que le créneau en prenait l'horaire - c'est-à-dire
    // toujours, depuis qu'il en hérite.
    $staff = adminStaff();
    $event = openEdition();
    $sienne = makeSession($event->id, 'plenary', '2026-10-12', '09:00', '10:30');
    $autre = makeSession($event->id, 'plenary', '2026-10-13', '09:00', '10:30');

    $this->actingAs($staff, 'web')->post(route('admin.program.slots.store'), [
        'event_id' => (string) $event->id,
        'slSessionId' => (string) $sienne->id,
        'slStart' => '09:15',
    ])->assertSessionMissing('ukc_error');
    expect(Slot::count())->toBe(1);

    // Rattaché à la première, mais posé dans la fenêtre de la seconde : refusé.
    $this->from(route('admin.program.index'))
        ->actingAs($staff, 'web')
        ->post(route('admin.program.slots.store'), [
            'event_id' => (string) $event->id,
            'slDay' => '2026-10-13',
            'slStart' => '09:15',
        ])->assertSessionHas('ukc_error');
    expect(Slot::count())->toBe(1);
});

it('déplace le créneau avec la séance qu’on lui donne', function () {
    $staff = adminStaff();
    $event = openEdition();
    $session = seanceAvecSalle($event->id);
    $slot = Slot::create([
        'event_id' => $event->id, 'room' => 'Amphi A', 'submission_id' => 0,
        'starts_at' => '2026-10-20 08:00', 'ends_at' => '2026-10-20 08:20',
    ]);

    $this->actingAs($staff, 'web')->put(route('admin.program.slots.update', $slot), [
        'slSessionId' => (string) $session->id,
    ])->assertSessionMissing('ukc_error');

    $slot->refresh();
    // Le jour et la salle viennent de la séance ; l'heure déjà portée est hors
    // de sa fenêtre, donc elle est reprise au début de la séance.
    expect($slot->starts_at->format('Y-m-d H:i'))->toBe('2026-10-12 14:00');
    expect($slot->room)->toBe('Salle 1');
});

it('ne propose au formulaire que les communications à placer dans les axes de la séance', function () {
    $staff = adminStaff();
    $event = openEdition();

    $atelier = Atelier::create(['event_id' => $event->id, 'title' => 'Atelier Sciences', 'tracks' => ['Sciences'], 'ordre' => 0]);
    $session = seanceAvecSalle($event->id);
    $session->update(['atelier_id' => $atelier->id]);

    $participant = $event->participants()->create([
        'user_id' => verifiedAccount()->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg',
    ]);
    $faire = fn (string $ref, string $track) => $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => $ref, 'title' => 'Titre '.$ref,
        'track' => $track, 'type' => 'oral', 'status' => 'accepted', 'revision_of' => 0,
    ]);

    $libre = $faire('AA-001', 'Sciences');
    $horsAxe = $faire('AA-002', 'Santé');
    $placee = $faire('AA-003', 'Sciences');
    Slot::create(['event_id' => $event->id, 'submission_id' => $placee->id, 'participant_id' => $participant->id]);

    $meta = $this->actingAs($staff, 'web')
        ->get(route('admin.program.index', ['event_id' => $event->id, 'tab' => 'creneaux']))
        ->assertOk()
        ->viewData('slotMeta');

    // La séance dit son jour, sa salle, sa fenêtre et ses axes.
    expect($meta['sessions'][(string) $session->id])->toMatchArray([
        'day' => '2026-10-12', 'start' => '14:00', 'end' => '17:00',
        'room' => 'Salle 1', 'tracks' => ['Sciences'],
    ]);

    // Chaque communication dit son axe et le créneau qui la porte déjà : c'est
    // avec ça que le formulaire écarte les placées et les hors-axe.
    $par = collect($meta['submissions'])->keyBy('id');
    expect($par[(string) $libre->id]['slot'])->toBe('');
    expect($par[(string) $horsAxe->id]['track'])->toBe('Santé');
    expect($par[(string) $placee->id]['slot'])->not->toBe('');
});
