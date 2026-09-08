<?php

use App\Models\Account;
use App\Models\Edition;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\SessionQuestion;

// openEdition() / verifiedAccount() / adminStaff() : helpers partagés (tests/Pest.php).

function sessionFor(Edition $edition): EventSession
{
    return EventSession::create([
        'edition_id' => $edition->id, 'label' => 'Séance plénière',
        'session_day' => '2026-09-03', 'starts_at' => '2026-09-03 09:00', 'ends_at' => '2026-09-03 11:00',
    ]);
}

function participantAccount(Edition $edition): Account
{
    $account = verifiedAccount();
    Participant::create([
        'edition_id' => $edition->id, 'account_id' => $account->id,
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email, 'role' => 'author',
    ]);

    return $account;
}

it('refuse l’accès à un invité pour une séance réservée aux participants', function () {
    $session = sessionFor(openEdition());

    $this->get(route('session.engagement', $session))->assertForbidden();
});

it('permet à un participant inscrit de poser une question', function () {
    $edition = openEdition();
    $session = sessionFor($edition);
    $account = participantAccount($edition);

    $this->actingAs($account, 'account')
        ->get(route('session.engagement', $session))->assertOk();

    $this->actingAs($account, 'account')
        ->postJson(route('session.engagement.ask', $session), ['body' => 'Comment gérez-vous les biais ?'])
        ->assertOk()->assertJsonPath('ok', true);

    expect(SessionQuestion::where('session_id', $session->id)->count())->toBe(1);
    expect(SessionQuestion::first()->body)->toBe('Comment gérez-vous les biais ?');
});

it('bascule le vote sur une question et compte les votes', function () {
    $edition = openEdition();
    $session = sessionFor($edition);
    $account = participantAccount($edition);
    $question = SessionQuestion::create([
        'session_id' => $session->id, 'edition_id' => $edition->id,
        'account_id' => $account->id, 'author_name' => 'Awa Kodjo', 'body' => 'Q', 'status' => 'pending',
    ]);

    $up = $this->actingAs($account, 'account')->postJson(route('session.engagement.vote', [$session, $question]));
    $up->assertOk()->assertJsonPath('voted', true)->assertJsonPath('votes', 1);

    $down = $this->actingAs($account, 'account')->postJson(route('session.engagement.vote', [$session, $question]));
    $down->assertJsonPath('voted', false)->assertJsonPath('votes', 0);
});

it('interdit la modération à un participant', function () {
    $edition = openEdition();
    $session = sessionFor($edition);
    $account = participantAccount($edition);
    $question = SessionQuestion::create([
        'session_id' => $session->id, 'edition_id' => $edition->id,
        'account_id' => $account->id, 'author_name' => 'Awa Kodjo', 'body' => 'Q', 'status' => 'pending',
    ]);

    $this->actingAs($account, 'account')
        ->postJson(route('session.engagement.moderate', [$session, $question]), ['status' => 'hidden'])
        ->assertForbidden();
    expect($question->fresh()->status)->toBe('pending');
});

it('masque les questions cachées au public mais les garde pour le modérateur', function () {
    $edition = openEdition();
    $session = sessionFor($edition);
    $account = participantAccount($edition);
    SessionQuestion::create([
        'session_id' => $session->id, 'edition_id' => $edition->id,
        'account_id' => $account->id, 'author_name' => 'Awa Kodjo', 'body' => 'Question sensible', 'status' => 'hidden',
    ]);

    // Vue participant d'abord (aucune session staff active) : la question masquée n'apparaît pas.
    $list = $this->actingAs($account, 'account')->getJson(route('session.engagement.questions', $session));
    expect($list->json('questions'))->toBe([]);

    // Le modérateur (staff) voit la question masquée et peut la marquer répondue.
    $staff = adminStaff();
    $question = SessionQuestion::first();
    $this->actingAs($staff, 'web')
        ->postJson(route('session.engagement.moderate', [$session, $question]), ['status' => 'answered'])
        ->assertOk();
    expect($question->fresh()->status)->toBe('answered');

    $modList = $this->actingAs($staff, 'web')->getJson(route('session.engagement.questions', $session));
    expect(count($modList->json('questions')))->toBe(1);
});
