<?php

use App\Models\SessionPoll;
use App\Models\SessionPollVote;

// sessionFor() / participantAccount() : définis dans SessionQaTest.php (chargés globalement).
// openEdition() / adminStaff() : helpers partagés (tests/Pest.php).

it('permet au modérateur de lancer un sondage', function () {
    $edition = openEdition();
    $session = sessionFor($edition);

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('session.engagement.polls.create', $session), [
            'question' => 'Quel format préférez-vous ?',
            'options' => ['Présentiel', 'Distanciel', ''],
        ])
        ->assertOk()->assertJsonPath('ok', true);

    $poll = SessionPoll::first();
    expect($poll->question)->toBe('Quel format préférez-vous ?');
    expect($poll->options)->toBe(['Présentiel', 'Distanciel']); // options vides retirées
    expect($poll->status)->toBe('open');
});

it('refuse la création de sondage à un participant', function () {
    $edition = openEdition();
    $session = sessionFor($edition);
    $account = participantAccount($edition);

    $this->actingAs($account, 'account')
        ->postJson(route('session.engagement.polls.create', $session), [
            'question' => 'Q', 'options' => ['A', 'B'],
        ])
        ->assertForbidden();
});

it('enregistre le vote et l’actualise (un choix par compte)', function () {
    $edition = openEdition();
    $session = sessionFor($edition);
    $account = participantAccount($edition);
    $poll = SessionPoll::create([
        'session_id' => $session->id, 'edition_id' => $edition->id,
        'question' => 'Format ?', 'options' => ['A', 'B', 'C'], 'status' => 'open',
    ]);

    $this->actingAs($account, 'account')
        ->postJson(route('session.engagement.polls.vote', [$session, $poll]), ['choice' => 1])
        ->assertOk();
    expect(SessionPollVote::where('poll_id', $poll->id)->count())->toBe(1);

    // Changement de vote : toujours un seul enregistrement, choix mis à jour.
    $this->actingAs($account, 'account')
        ->postJson(route('session.engagement.polls.vote', [$session, $poll]), ['choice' => 2])
        ->assertOk();
    expect(SessionPollVote::where('poll_id', $poll->id)->count())->toBe(1);
    expect(SessionPollVote::first()->choice)->toBe(2);
});

it('agrège les résultats et expose le choix du visiteur', function () {
    $edition = openEdition();
    $session = sessionFor($edition);
    $account = participantAccount($edition);
    $poll = SessionPoll::create([
        'session_id' => $session->id, 'edition_id' => $edition->id,
        'question' => 'Format ?', 'options' => ['A', 'B'], 'status' => 'open',
    ]);
    SessionPollVote::create(['poll_id' => $poll->id, 'user_id' => $account->id, 'choice' => 0]);

    $resp = $this->actingAs($account, 'account')->getJson(route('session.engagement.polls', $session));
    $resp->assertOk()
        ->assertJsonPath('polls.0.total', 1)
        ->assertJsonPath('polls.0.counts.0', 1)
        ->assertJsonPath('polls.0.counts.1', 0)
        ->assertJsonPath('polls.0.mine', 0);
});

it('refuse le vote sur un sondage clos', function () {
    $edition = openEdition();
    $session = sessionFor($edition);
    $account = participantAccount($edition);
    $poll = SessionPoll::create([
        'session_id' => $session->id, 'edition_id' => $edition->id,
        'question' => 'Format ?', 'options' => ['A', 'B'], 'status' => 'closed',
    ]);

    $this->actingAs($account, 'account')
        ->postJson(route('session.engagement.polls.vote', [$session, $poll]), ['choice' => 0])
        ->assertStatus(422);
    expect(SessionPollVote::count())->toBe(0);
});
