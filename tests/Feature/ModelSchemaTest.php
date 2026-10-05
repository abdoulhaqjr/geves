<?php

use App\Models\EventSeries;
use App\Models\EventSession;
use App\Models\User;

it('crée un colloque avec une événement et ses entités liées', function () {
    $series = EventSeries::create(['name' => 'EventSeries de Kara', 'slug' => 'kara']);

    $event = $series->events()->create([
        'title' => 'Événement 2026',
        'event_label' => '1re événement',
        'year' => 2026,
        'status' => 'open',
        'settings' => ['fees' => ['doctorant' => 10000]],
    ]);

    $participant = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);

    $submission = $event->submissions()->create([
        'participant_id' => $participant->id,
        'title' => 'Résumé test',
        'authors' => [['name' => 'Awa Kodjo']],
        'status' => 'submitted',
    ]);

    $submission->reviews()->create(['recommendation' => 'accept', 'score' => 16.5]);
    $event->payments()->create(['participant_id' => $participant->id, 'amount' => 10000, 'status' => 'paid']);
    $atelier = $event->ateliers()->create(['title' => 'Atelier A', 'tracks' => ['axe1', 'axe2']]);
    $session = $event->sessions()->create(['atelier_id' => $atelier->id, 'type' => 'atelier', 'label' => 'Session 1']);
    $event->slots()->create(['session_id' => $session->id, 'submission_id' => $submission->id]);
    $event->attestations()->create(['uuid' => 'uuid-1', 'type' => 'participation', 'participant_id' => $participant->id]);
    $event->committeeMembers()->create(['person_name' => 'Pr. Doe', 'role' => 'scientifique']);
    $event->media()->create(['type' => 'photo', 'url' => 'https://example.tg/p.jpg']);

    // Casts JSON
    expect($event->fresh()->settings)->toBe(['fees' => ['doctorant' => 10000]]);
    expect($atelier->fresh()->tracks)->toBe(['axe1', 'axe2']);
    expect($submission->fresh()->authors)->toBe([['name' => 'Awa Kodjo']]);

    // Relations inverses / traversée
    expect($event->series->name)->toBe('EventSeries de Kara');
    expect($session->slots()->count())->toBe(1);
    expect($participant->submissions()->count())->toBe(1);
    expect($submission->reviews->first()->score)->toBe('16.50');
    expect(EventSession::first()->getTable())->toBe('event_sessions');
});

it('gère un compte participant avec la règle canSubmit', function () {
    $account = User::create([
        'first_name' => 'Kofi', 'last_name' => 'Mensah',
        'email' => 'kofi@example.tg', 'password' => 'secret-pass',
    ]);

    expect($account->full_name)->toBe('Kofi Mensah');
    expect($account->initials)->toBe('KM');
    expect($account->canSubmit())->toBeFalse(); // ni vérifié ni affilié

    $account->update(['affiliation' => 'Université de Kara']);
    $account->markEmailAsVerified();
    expect($account->fresh()->canSubmit())->toBeTrue();
    expect(password_verify('secret-pass', $account->password))->toBeTrue(); // cast hashed
});
