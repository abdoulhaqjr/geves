<?php

use App\Enums\MeetingStatus;
use Illuminate\Support\Facades\Artisan;

// openEdition() -> tests/Pest.php ; liveSession() -> MeetingTest ;
// officier() -> SessionAttendanceTest

/**
 * Clôture automatique des visios passées : une séance « en direct » dont l'horaire
 * de fin est dépassé (+ marge) passe « terminée » - l'accès est coupé et le statut
 * se met à jour tout seul (commande planifiée + auto-réparation à l'accès).
 */
it('clôture une séance en direct dont l’horaire est passé', function () {
    $event = openEdition();
    $session = liveSession($event, 'participants', [
        'starts_at' => now()->subHours(5),
        'ends_at' => now()->subHours(4), // dépassé de 4 h > marge 2 h
    ]);

    Artisan::call('meetings:close-ended');

    expect($session->fresh()->meetingStatusEnum())->toBe(MeetingStatus::Ended);
});

it('ne clôture pas une séance encore dans sa fenêtre', function () {
    $event = openEdition();
    $session = liveSession($event, 'participants', [
        'starts_at' => now()->subHour(),
        'ends_at' => now()->subMinutes(30), // dans la marge de 2 h
    ]);

    Artisan::call('meetings:close-ended');

    expect($session->fresh()->meetingStatusEnum())->toBe(MeetingStatus::Live);
});

it('ne clôture pas une séance sans horaire', function () {
    $event = openEdition();
    $session = liveSession($event, 'participants', [
        'starts_at' => null, 'ends_at' => null, 'session_day' => null,
    ]);

    Artisan::call('meetings:close-ended');

    expect($session->fresh()->meetingStatusEnum())->toBe(MeetingStatus::Live);
});

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
