<?php

use App\Models\EventSession;
use App\Models\Slot;
use App\Models\User;
use App\Support\ProgramConflicts;
use Illuminate\Support\Carbon;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

/** Crée une session horodatée directement (sans passer par le contrôleur). */
function makeSession(int $editionId, string $type, string $day, string $start, string $end): EventSession
{
    return EventSession::create([
        'edition_id' => $editionId,
        'type' => $type,
        'atelier_id' => 0,
        'label' => ucfirst($type).' '.$start,
        'session_day' => $day,
        'starts_at' => "$day $start:00",
        'ends_at' => "$day $end:00",
    ]);
}

it('bloque une session d’atelier qui chevauche une plénière', function () {
    $staff = adminStaff();
    $edition = openEdition();
    makeSession($edition->id, 'plenary', '2026-09-27', '09:00', '10:00');

    $this->from(route('admin.program.index'))
        ->actingAs($staff, 'web')
        ->post(route('admin.program.sessions.store'), [
            'edition_id' => (string) $edition->id,
            'sType' => 'atelier',
            'sLabel' => 'Atelier concurrent',
            'sDay' => '2026-09-27',
            'sStart' => '09:30',
            'sEnd' => '10:30',
        ])->assertSessionHas('ukc_error');

    expect(EventSession::where('label', 'Atelier concurrent')->exists())->toBeFalse();
});

it('bloque une plénière qui chevauche un atelier existant', function () {
    $staff = adminStaff();
    $edition = openEdition();
    makeSession($edition->id, 'atelier', '2026-09-27', '14:00', '15:30');

    $this->from(route('admin.program.index'))
        ->actingAs($staff, 'web')
        ->post(route('admin.program.sessions.store'), [
            'edition_id' => (string) $edition->id,
            'sType' => 'plenary',
            'sLabel' => 'Plénière tardive',
            'sDay' => '2026-09-27',
            'sStart' => '15:00',
            'sEnd' => '16:00',
        ])->assertSessionHas('ukc_error');

    expect(EventSession::where('label', 'Plénière tardive')->exists())->toBeFalse();
});

it('autorise deux ateliers simultanés (sessions parallèles)', function () {
    $staff = adminStaff();
    $edition = openEdition();
    makeSession($edition->id, 'atelier', '2026-09-27', '14:00', '15:30');

    $this->from(route('admin.program.index'))
        ->actingAs($staff, 'web')
        ->post(route('admin.program.sessions.store'), [
            'edition_id' => (string) $edition->id,
            'sType' => 'atelier',
            'sLabel' => 'Atelier parallèle',
            'sDay' => '2026-09-27',
            'sStart' => '14:00',
            'sEnd' => '15:30',
        ])->assertSessionHasNoErrors()->assertSessionMissing('ukc_error');

    expect(EventSession::where('label', 'Atelier parallèle')->exists())->toBeTrue();
});

it('autorise une session qui touche une plénière sans la chevaucher', function () {
    $staff = adminStaff();
    $edition = openEdition();
    makeSession($edition->id, 'plenary', '2026-09-27', '09:00', '10:00');

    $this->from(route('admin.program.index'))
        ->actingAs($staff, 'web')
        ->post(route('admin.program.sessions.store'), [
            'edition_id' => (string) $edition->id,
            'sType' => 'atelier',
            'sLabel' => 'Atelier juste après',
            'sDay' => '2026-09-27',
            'sStart' => '10:00',
            'sEnd' => '11:00',
        ])->assertSessionMissing('ukc_error');

    expect(EventSession::where('label', 'Atelier juste après')->exists())->toBeTrue();
});

it('bloque un créneau qui tombe dans une plénière', function () {
    $staff = adminStaff();
    $edition = openEdition();
    makeSession($edition->id, 'plenary', '2026-09-27', '09:00', '10:00');

    $this->from(route('admin.program.index'))
        ->actingAs($staff, 'web')
        ->post(route('admin.program.slots.store'), [
            'edition_id' => (string) $edition->id,
            'slRoom' => 'Salle A',
            'slDay' => '2026-09-27',
            'slStart' => '09:30',
        ])->assertSessionHas('ukc_error');

    expect(Slot::count())->toBe(0);
});

it('permet de ré-enregistrer une plénière avec ses propres horaires (pas de conflit avec elle-même)', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $plenary = makeSession($edition->id, 'plenary', '2026-09-27', '09:00', '10:00');

    $this->from(route('admin.program.index'))
        ->actingAs($staff, 'web')
        ->put(route('admin.program.sessions.update', $plenary), [
            'sType' => 'plenary',
            'sLabel' => 'Ouverture (renommée)',
            'sDay' => '2026-09-27',
            'sStart' => '09:00',
            'sEnd' => '10:00',
        ])->assertSessionMissing('ukc_error');

    expect($plenary->fresh()->label)->toBe('Ouverture (renommée)');
});

it('helper : détecte le conflit uniquement quand une plénière est impliquée', function () {
    $edition = openEdition();
    $day = '2026-09-27';
    makeSession($edition->id, 'plenary', $day, '09:00', '10:00');
    makeSession($edition->id, 'atelier', $day, '14:00', '15:00');

    $c = fn (string $s, string $e, bool $plenary) => ProgramConflicts::blockingSession(
        $edition->fresh(), Carbon::parse("$day $s"), Carbon::parse("$day $e"), $plenary
    );

    // Atelier pendant la plénière -> conflit ; atelier // atelier -> pas de conflit.
    expect($c('09:30', '10:30', false))->not->toBeNull();
    expect($c('14:00', '15:00', false))->toBeNull();
    // Nouvelle plénière pendant un atelier -> conflit.
    expect($c('14:30', '15:30', true))->not->toBeNull();
});

it('affectation auto : un même auteur n’est pas placé sur deux créneaux simultanés', function () {
    $staff = adminStaff();
    $sub1 = seededSubmission('accepted');
    $edition = $sub1->edition;
    $participant = $sub1->participant;

    // 2e communication acceptée du MÊME participant.
    $sub2 = $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-002', 'title' => 'Second travail',
        'abstract' => str_repeat('Contexte. ', 10), 'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted',
        'authors' => [['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'corresponding' => true]],
    ]);

    // Deux créneaux libres SIMULTANÉS (deux ateliers en parallèle, salles différentes).
    Slot::create(['edition_id' => $edition->id, 'room' => 'Salle A', 'submission_id' => 0, 'participant_id' => 0, 'starts_at' => '2026-09-27 14:00:00', 'ends_at' => '2026-09-27 14:20:00']);
    Slot::create(['edition_id' => $edition->id, 'room' => 'Salle B', 'submission_id' => 0, 'participant_id' => 0, 'starts_at' => '2026-09-27 14:00:00', 'ends_at' => '2026-09-27 14:20:00']);

    $this->from(route('admin.program.index'))->actingAs($staff, 'web')
        ->post(route('admin.program.slots.auto').'?edition_id='.$edition->id, ['match_axis' => '0'])
        ->assertSessionHasNoErrors();

    // Une seule des deux communications de cet auteur est placée (créneaux en conflit).
    expect($edition->slots()->where('participant_id', $participant->id)->count())->toBe(1);
});

it('affectation auto : deux auteurs différents peuvent être placés en parallèle', function () {
    $staff = adminStaff();
    $sub1 = seededSubmission('accepted');
    $edition = $sub1->edition;

    // 2e communication d'un AUTRE participant (email/téléphone distincts).
    $account2 = User::create([
        'name' => 'Kofi Mensah', 'first_name' => 'Kofi', 'last_name' => 'Mensah',
        'email' => 'kofi@example.tg', 'password' => 'pass12345',
        'grade' => 'Doctorant', 'affiliation' => 'Université de Parakou', 'phone' => '+22891000000',
    ]);
    $account2->markEmailAsVerified();
    $p2 = $edition->participants()->create(['user_id' => $account2->id, 'first_name' => 'Kofi', 'last_name' => 'Mensah', 'email' => $account2->email]);
    $edition->submissions()->create([
        'participant_id' => $p2->id, 'ref_code' => '26-003', 'title' => 'Travail de Kofi',
        'abstract' => str_repeat('Contexte. ', 10), 'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted',
        'authors' => [['name' => 'Kofi Mensah', 'email' => $account2->email, 'corresponding' => true]],
    ]);

    Slot::create(['edition_id' => $edition->id, 'room' => 'Salle A', 'submission_id' => 0, 'participant_id' => 0, 'starts_at' => '2026-09-27 14:00:00', 'ends_at' => '2026-09-27 14:20:00']);
    Slot::create(['edition_id' => $edition->id, 'room' => 'Salle B', 'submission_id' => 0, 'participant_id' => 0, 'starts_at' => '2026-09-27 14:00:00', 'ends_at' => '2026-09-27 14:20:00']);

    $this->from(route('admin.program.index'))->actingAs($staff, 'web')
        ->post(route('admin.program.slots.auto').'?edition_id='.$edition->id, ['match_axis' => '0']);

    // Les deux (auteurs distincts) sont placés malgré des créneaux simultanés.
    expect($edition->slots()->where('submission_id', '>', 0)->count())->toBe(2);
});
