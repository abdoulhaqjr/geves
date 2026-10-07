<?php

use App\Models\SessionQuestion;

// sessionFor() / participantAccount() / openEdition() / adminStaff() : tests/Pest.php.

it('refuse l’accès à un invité pour une séance réservée aux participants', function () {
    // ⚠️ L'accès à une séance est PUBLIC par défaut : on exerce ici le cas où le
    // gestionnaire a restreint aux inscrits (sinon un invité y aurait accès).
    // Les échanges n'ont plus de page autonome : la garde vit sur les endpoints JSON.
    $session = sessionFor(openEdition());
    $session->update(['meeting_visibility' => 'participants']);

    $this->getJson(route('session.engagement.questions', $session))->assertForbidden();
});

it('permet à un participant inscrit de poser une question', function () {
    $event = openEdition();
    $session = sessionFor($event);
    $account = participantAccount($event);

    $this->actingAs($account, 'account')
        ->getJson(route('session.engagement.questions', $session))->assertOk();

    $this->actingAs($account, 'account')
        ->postJson(route('session.engagement.ask', $session), ['body' => 'Comment gérez-vous les biais ?'])
        ->assertOk()->assertJsonPath('ok', true);

    expect(SessionQuestion::where('session_id', $session->id)->count())->toBe(1);
    expect(SessionQuestion::first()->body)->toBe('Comment gérez-vous les biais ?');
});

it('bascule le vote sur une question et compte les votes', function () {
    $event = openEdition();
    $session = sessionFor($event);
    $account = participantAccount($event);
    $question = SessionQuestion::create([
        'session_id' => $session->id, 'event_id' => $event->id,
        'user_id' => $account->id, 'author_name' => 'Awa Kodjo', 'body' => 'Q', 'status' => 'pending',
    ]);

    $up = $this->actingAs($account, 'account')->postJson(route('session.engagement.vote', [$session, $question]));
    $up->assertOk()->assertJsonPath('voted', true)->assertJsonPath('votes', 1);

    $down = $this->actingAs($account, 'account')->postJson(route('session.engagement.vote', [$session, $question]));
    $down->assertJsonPath('voted', false)->assertJsonPath('votes', 0);
});

it('interdit la modération à un participant', function () {
    $event = openEdition();
    $session = sessionFor($event);
    $account = participantAccount($event);
    $question = SessionQuestion::create([
        'session_id' => $session->id, 'event_id' => $event->id,
        'user_id' => $account->id, 'author_name' => 'Awa Kodjo', 'body' => 'Q', 'status' => 'pending',
    ]);

    $this->actingAs($account, 'account')
        ->postJson(route('session.engagement.moderate', [$session, $question]), ['status' => 'hidden'])
        ->assertForbidden();
    expect($question->fresh()->status)->toBe('pending');
});

it('masque les questions cachées au public mais les garde pour le modérateur', function () {
    $event = openEdition();
    $session = sessionFor($event);
    $account = participantAccount($event);
    SessionQuestion::create([
        'session_id' => $session->id, 'event_id' => $event->id,
        'user_id' => $account->id, 'author_name' => 'Awa Kodjo', 'body' => 'Question sensible', 'status' => 'hidden',
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

it('fait descendre les questions traitées sous celles qui attendent', function () {
    // ⚠️ Une question répondue il y a dix minutes mais portée par vingt votes
    // restait en tête de file pour le reste de la séance, au-dessus de celles qui
    // attendaient encore. L'animateur lit la file de haut en bas : ce qui est fait
    // doit descendre. Les masquées, que seul le modérateur voit, descendent plus
    // bas encore - ce sont des déchets, pas des archives.
    $event = openEdition();
    $session = sessionFor($event);
    $account = participantAccount($event);
    $staff = adminStaff();

    $faire = function (string $corps, string $statut) use ($session, $event, $account) {
        return SessionQuestion::create([
            'session_id' => $session->id, 'event_id' => $event->id,
            'user_id' => $account->id, 'author_name' => 'Awa Kodjo',
            'body' => $corps, 'status' => $statut,
        ]);
    };
    // La plus votée est traitée : elle ne doit plus ouvrir la file.
    $traitee = $faire('Déjà répondue', 'answered');
    $attend = $faire('Attend encore', 'pending');
    $masquee = $faire('Masquée', 'hidden');

    $this->actingAs($account, 'account')
        ->postJson(route('session.engagement.vote', [$session, $traitee]))->assertOk();

    $corps = $this->actingAs($staff, 'web')
        ->getJson(route('session.engagement.questions', $session))->json('questions');

    expect(array_column($corps, 'id'))->toBe([$attend->id, $traitee->id, $masquee->id]);
    // Et le vote reste bien compté : on ne fait que déplacer, pas effacer.
    expect($corps[1]['votes'])->toBe(1);
});

it('classe par votes à l’intérieur de ce qui attend', function () {
    $event = openEdition();
    $session = sessionFor($event);
    $account = participantAccount($event);

    $faire = fn (string $corps) => SessionQuestion::create([
        'session_id' => $session->id, 'event_id' => $event->id,
        'user_id' => $account->id, 'author_name' => 'Awa Kodjo',
        'body' => $corps, 'status' => 'pending',
    ]);
    $premiere = $faire('Posée en premier');
    $votee = $faire('Posée après, mais votée');

    $this->actingAs($account, 'account')
        ->postJson(route('session.engagement.vote', [$session, $votee]))->assertOk();

    $corps = $this->actingAs($account, 'account')
        ->getJson(route('session.engagement.questions', $session))->json('questions');

    expect(array_column($corps, 'id'))->toBe([$votee->id, $premiere->id]);
});
