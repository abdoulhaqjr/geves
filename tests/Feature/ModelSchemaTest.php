<?php

use App\Models\Colloque;
use App\Models\EventSession;
use App\Models\User;

it('crée un colloque avec une édition et ses entités liées', function () {
    $colloque = Colloque::create(['name' => 'Colloque de Kara', 'slug' => 'kara']);

    $edition = $colloque->editions()->create([
        'title' => 'Édition 2026',
        'edition_label' => '1re édition',
        'year' => 2026,
        'status' => 'open',
        'settings' => ['fees' => ['doctorant' => 10000]],
    ]);

    $participant = $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);

    $submission = $edition->submissions()->create([
        'participant_id' => $participant->id,
        'title' => 'Résumé test',
        'authors' => [['name' => 'Awa Kodjo']],
        'status' => 'submitted',
    ]);

    $submission->reviews()->create(['recommendation' => 'accept', 'score' => 16.5]);
    $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 10000, 'status' => 'paid']);
    $atelier = $edition->ateliers()->create(['title' => 'Atelier A', 'tracks' => ['axe1', 'axe2']]);
    $session = $edition->sessions()->create(['atelier_id' => $atelier->id, 'type' => 'atelier', 'label' => 'Session 1']);
    $edition->slots()->create(['session_id' => $session->id, 'submission_id' => $submission->id]);
    $edition->attestations()->create(['uuid' => 'uuid-1', 'type' => 'participation', 'participant_id' => $participant->id]);
    $edition->committeeMembers()->create(['person_name' => 'Pr. Doe', 'role' => 'scientifique']);
    $edition->media()->create(['type' => 'photo', 'url' => 'https://example.tg/p.jpg']);

    // Casts JSON
    expect($edition->fresh()->settings)->toBe(['fees' => ['doctorant' => 10000]]);
    expect($atelier->fresh()->tracks)->toBe(['axe1', 'axe2']);
    expect($submission->fresh()->authors)->toBe([['name' => 'Awa Kodjo']]);

    // Relations inverses / traversée
    expect($edition->colloque->name)->toBe('Colloque de Kara');
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
