<?php

use App\Enums\MeetingStatus;
use Illuminate\Support\Facades\Artisan;

// openEdition() -> tests/Pest.php ; liveSession() -> MeetingTest ;
// officier() -> SessionAttendanceTest

/**
 * Statut de la visio suivi sur l'horloge, sans intervention manuelle :
 *  - une séance « programmée » passe « en direct » à l'approche de son horaire ;
 *  - une séance « programmée » ou « en direct » dont la fenêtre est passée (+ marge)
 *    passe « terminée » (l'accès est alors coupé).
 * Deux déclencheurs : la commande planifiée `meetings:sync` et l'auto-réparation à
 * l'accès.
 */
function scheduledSession($event, string $visibility = 'public', array $attrs = [])
{
    return $event->sessions()->create(array_merge([
        'type' => 'plenary', 'label' => 'Séance', 'meeting_status' => 'scheduled', 'meeting_visibility' => $visibility,
    ], $attrs));
}

// --- Clôture (live/scheduled → ended) --------------------------------------

it('clôture une séance en direct dont l’horaire est passé', function () {
    $event = openEdition();
    $session = liveSession($event, 'participants', [
        'starts_at' => now()->subHours(5),
        'ends_at' => now()->subHours(4), // dépassé de 4 h > marge 2 h
    ]);

    Artisan::call('meetings:sync');

    expect($session->fresh()->meetingStatusEnum())->toBe(MeetingStatus::Ended);
});

it('clôture une séance programmée dont l’horaire est passé (jamais ouverte)', function () {
    $event = openEdition();
    $session = scheduledSession($event, 'public', [
        'starts_at' => now()->subHours(5),
        'ends_at' => now()->subHours(4),
    ]);

    Artisan::call('meetings:sync');

    expect($session->fresh()->meetingStatusEnum())->toBe(MeetingStatus::Ended);
});

it('ne clôture pas une séance encore dans sa fenêtre', function () {
    $event = openEdition();
    $session = liveSession($event, 'participants', [
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(30), // dans la marge de 2 h
    ]);

    Artisan::call('meetings:sync');

    expect($session->fresh()->meetingStatusEnum())->toBe(MeetingStatus::Live);
});

it('ne clôture pas une séance sans horaire', function () {
    $event = openEdition();
    $session = liveSession($event, 'participants', [
        'starts_at' => null, 'ends_at' => null, 'session_day' => null,
    ]);

    Artisan::call('meetings:sync');

    expect($session->fresh()->meetingStatusEnum())->toBe(MeetingStatus::Live);
});

// --- Ouverture automatique (scheduled → live) ------------------------------

it('passe en direct une séance programmée dont l’horaire approche', function () {
    $event = openEdition();
    $session = scheduledSession($event, 'public', [
        'starts_at' => now()->addMinutes(20), // dans la marge de 30 min
        'ends_at' => now()->addMinutes(80),
    ]);

    Artisan::call('meetings:sync');

    expect($session->fresh()->meetingStatusEnum())->toBe(MeetingStatus::Live);
});

it('ne passe pas en direct une séance programmée trop en avance', function () {
    $event = openEdition();
    $session = scheduledSession($event, 'public', [
        'starts_at' => now()->addHours(2), // bien au-delà de la marge de 30 min
        'ends_at' => now()->addHours(3),
    ]);

    Artisan::call('meetings:sync');

    expect($session->fresh()->meetingStatusEnum())->toBe(MeetingStatus::Scheduled);
});

it('ne redescend jamais une séance ouverte en avance vers « programmée »', function () {
    $event = openEdition();
    // Le staff a ouvert la salle avant l'heure : on ne défait pas ce choix.
    $session = liveSession($event, 'public', [
        'starts_at' => now()->addHours(2),
        'ends_at' => now()->addHours(3),
    ]);

    Artisan::call('meetings:sync');

    expect($session->fresh()->meetingStatusEnum())->toBe(MeetingStatus::Live);
});

it('passe en direct paresseusement à l’accès', function () {
    $event = openEdition();
    $session = scheduledSession($event, 'public', [
        'starts_at' => now()->addMinutes(10),
        'ends_at' => now()->addMinutes(70),
    ]);

    $this->get("/session/{$session->id}/visio")->assertOk();

    expect($session->fresh()->meetingStatusEnum())->toBe(MeetingStatus::Live);
});

// --- Clôture paresseuse à l'accès ------------------------------------------

it('clôture paresseusement à l’accès et coupe la salle', function () {
    $event = openEdition();
    $session = liveSession($event, 'public', [
        'starts_at' => now()->subHours(5),
        'ends_at' => now()->subHours(4),
    ]);

    // L'accès à la salle déclenche la clôture (même sans planificateur).
    $this->get("/session/{$session->id}/visio")->assertOk()->assertSee('terminée', false);

    expect($session->fresh()->meetingStatusEnum())->toBe(MeetingStatus::Ended);
});

it('refuse d’écrire dans le fil d’une séance dont l’horaire est passé', function () {
    $event = openEdition();
    $session = liveSession($event, 'public', [
        'starts_at' => now()->subHours(5),
        'ends_at' => now()->subHours(4),
    ]);

    $this->actingAs(officier('modpost@example.tg'), 'account')
        ->postJson("/visio/session/{$session->id}/messages", ['uid' => 'abc123', 'body' => 'Trop tard'])
        ->assertForbidden();
});
