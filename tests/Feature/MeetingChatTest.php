<?php

use App\Models\MeetingMessage;
use App\Models\MeetingMessageReaction;
use App\Models\User;

/**
 * Fil de discussion de la salle de visio.
 *
 * ⚠️ Le chat ne vivait que sur le canal de données de LiveKit : qui arrivait en
 * cours de séance, ou rechargeait sa page, trouvait une salle muette - les
 * questions posées avant lui n'existaient plus nulle part. Ces tests tiennent le
 * fil archivé, sa règle d'accès et ses réactions.
 */

/** Un message déjà dans le fil d'une séance. */
function messageDansLeFil($session, string $uid = 'abc123', string $corps = 'Bonjour la salle'): MeetingMessage
{
    $m = new MeetingMessage(['uid' => $uid, 'body' => $corps]);
    $m->author_name = 'Awa Kodjo';
    $m->meetable()->associate($session);
    $m->save();

    return $m;
}

it('rejoue le fil à qui peut rejoindre la salle', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'public');
    messageDansLeFil($session);

    $this->getJson(route('meeting.chat.list', ['session', $session->id]))
        ->assertOk()
        ->assertJsonPath('messages.0.who', 'Awa Kodjo')
        ->assertJsonPath('messages.0.body', 'Bonjour la salle');
});

it('refuse le fil à qui ne peut pas entrer dans la salle', function () {
    $edition = openEdition();
    // Visibilité « participants » : un visiteur sans compte n'entre pas, donc ne
    // lit pas ce qui se dit.
    $session = liveSession($edition, 'participants');
    messageDansLeFil($session);

    $this->getJson(route('meeting.chat.list', ['session', $session->id]))->assertForbidden();
    $this->postJson(route('meeting.chat.post', ['session', $session->id]), [
        'uid' => 'zzz999', 'body' => 'Je force la porte',
    ])->assertForbidden();
});

it('ne résout que les types de salle connus', function () {
    $edition = openEdition();
    liveSession($edition, 'public');

    $this->getJson(route('meeting.chat.list', ['atelier', 1]))->assertNotFound();
});

it('signe le message côté serveur et conserve la citation', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'public');
    $compte = User::factory()->create(['name' => 'Kossi Mensah']);

    $this->actingAs($compte, 'account')
        ->postJson(route('meeting.chat.post', ['session', $session->id]), [
            'uid' => 'msg001',
            'body' => 'Une question sur la méthode',
            'reply_uid' => 'abc123',
            'reply_author' => 'Awa Kodjo',
            'reply_excerpt' => 'Bonjour la salle',
            // ⚠️ Un nom d'auteur envoyé par le client ne doit RIEN changer :
            // sinon chacun signerait ses messages du nom d'un autre.
            'author_name' => 'Le Recteur',
        ])
        ->assertOk();

    $m = MeetingMessage::where('uid', 'msg001')->firstOrFail();
    expect($m->author_name)->toBe('Kossi Mensah');
    expect($m->user_id)->toBe($compte->id);
    expect($m->reply_author)->toBe('Awa Kodjo');
    expect($m->reply_excerpt)->toBe('Bonjour la salle');
});

it('pose et retire une réaction du même geste, sans jamais compter deux fois', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'public');
    messageDansLeFil($session, 'r00001');
    $compte = User::factory()->create();

    $poser = fn () => $this->actingAs($compte, 'account')
        ->postJson(route('meeting.chat.react', ['session', $session->id, 'r00001']), ['emoji' => '👍']);

    $poser()->assertOk()->assertJsonPath('on', true)->assertJsonPath('reactions.👍.count', 1);
    // Le même geste retire : une réaction n'a pas de bouton « annuler » séparé.
    $poser()->assertOk()->assertJsonPath('on', false);

    expect(MeetingMessage::where('uid', 'r00001')->firstOrFail()->reactions()->count())->toBe(0);
});

it('remplace sa réaction quand on en choisit une autre', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'public');
    $message = messageDansLeFil($session, 'r00003');
    $compte = User::factory()->create();
    $autre = User::factory()->create();

    $reagir = fn (User $qui, string $emoji) => $this->actingAs($qui, 'account')
        ->postJson(route('meeting.chat.react', ['session', $session->id, 'r00003']), ['emoji' => $emoji]);

    $reagir($compte, '👍')->assertOk();
    // ⚠️ Une seule réaction par personne : le 👍 doit DISPARAÎTRE, pas s'ajouter.
    $reagir($compte, '❤️')->assertOk()
        ->assertJsonPath('replaced', '👍')
        ->assertJsonPath('reactions.❤️.count', 1)
        ->assertJsonMissingPath('reactions.👍');

    // Celle d'une autre personne n'est pas touchée pour autant.
    $reagir($autre, '👍')->assertOk()->assertJsonPath('reactions.👍.count', 1);

    expect($message->reactions()->count())->toBe(2);
});

it('ne confond pas deux visiteurs anonymes qui réagissent', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'public');
    $message = messageDansLeFil($session, 'r00004');

    $reagir = fn () => $this->postJson(
        route('meeting.chat.react', ['session', $session->id, 'r00004']),
        ['emoji' => '👍'],
    );

    $reagir()->assertOk()->assertJsonPath('reactions.👍.count', 1);
    $premier = MeetingMessageReaction::firstOrFail()->reactor;

    // ⚠️ Tous les visiteurs sans compte partageaient la clé « anon » : le second
    // RETIRAIT la réaction du premier au lieu d'ajouter la sienne.
    expect($premier)->toStartWith('a:')->not->toBe('anon');

    // Un autre visiteur, donc une autre session.
    $this->flushSession();
    $reagir()->assertOk();

    expect($message->reactions()->pluck('reactor')->unique())->toHaveCount(2);
});

it('n’enregistre que les réactions proposées', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'public');
    messageDansLeFil($session, 'r00002');

    $this->postJson(route('meeting.chat.react', ['session', $session->id, 'r00002']), ['emoji' => '💣'])
        ->assertStatus(422);
});

it('n’écrit pas dans le fil d’une séance qui n’est pas en cours', function () {
    $edition = openEdition();
    // Lisible (le rôle le permet), mais la visio est terminée : on ne l'alimente
    // plus. ⚠️ L'API ne vérifiait que le rôle, alors que la salle exige les deux.
    $session = liveSession($edition, 'public', ['meeting_status' => 'ended']);
    messageDansLeFil($session, 'r00005');

    $this->getJson(route('meeting.chat.list', ['session', $session->id]))->assertOk();
    $this->postJson(route('meeting.chat.post', ['session', $session->id]), [
        'uid' => 'tard01', 'body' => 'Après la bataille',
    ])->assertForbidden();
    $this->postJson(route('meeting.chat.react', ['session', $session->id, 'r00005']), ['emoji' => '👍'])
        ->assertForbidden();
});

it('ne crée pas deux fois le même message quand l’envoi est rejoué', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'public');

    $envoi = fn () => $this->postJson(route('meeting.chat.post', ['session', $session->id]), [
        'uid' => 'rejeu01', 'body' => 'Un seul exemplaire',
    ]);

    $envoi()->assertOk();
    // ⚠️ Un réseau qui bégaie rejoue l'envoi : deux lignes porteraient le même
    // identifiant, et une réaction ne saurait plus laquelle viser.
    $envoi()->assertOk();

    expect(MeetingMessage::where('uid', 'rejeu01')->count())->toBe(1);
});

it('délivre un nouveau jeton à qui peut rejoindre la salle en cours', function () {
    config()->set('meetings.default', 'livekit');
    config()->set('meetings.livekit.url', 'wss://exemple.livekit.cloud');
    config()->set('meetings.livekit.api_key', 'cle');
    config()->set('meetings.livekit.api_secret', str_repeat('s', 40));

    $edition = openEdition();
    $enCours = liveSession($edition, 'public');
    $terminee = liveSession($edition, 'public', ['meeting_status' => 'ended']);

    $this->getJson(route('meeting.token', ['session', $enCours->id]))
        ->assertOk()
        ->assertJsonStructure(['token']);

    // ⚠️ Le renouvellement n'est pas une porte dérobée : il suit les mêmes
    // conditions que l'entrée dans la salle.
    $this->getJson(route('meeting.token', ['session', $terminee->id]))->assertForbidden();
});

it('ne laisse que le modérateur retirer un message du fil', function () {
    $edition = openEdition();
    $moderateur = User::factory()->create(['email' => 'mod@example.tg']);
    $session = liveSession($edition, 'public', ['moderator_email' => 'mod@example.tg']);
    messageDansLeFil($session, 'mod001', 'Message déplacé');

    $simple = User::factory()->create();
    $this->actingAs($simple, 'account')
        ->deleteJson(route('meeting.chat.delete', ['session', $session->id, 'mod001']))
        ->assertForbidden();

    // ⚠️ Le fil est CONSERVÉ : un message déplacé resterait là pour toutes les
    // prochaines arrivées.
    $this->actingAs($moderateur, 'account')
        ->deleteJson(route('meeting.chat.delete', ['session', $session->id, 'mod001']))
        ->assertOk();

    expect(MeetingMessage::where('uid', 'mod001')->exists())->toBeFalse();
});

it('emporte le fil en texte pour le compte rendu, au modérateur seul', function () {
    $edition = openEdition();
    $moderateur = User::factory()->create(['email' => 'mod@example.tg']);
    $session = liveSession($edition, 'public', ['moderator_email' => 'mod@example.tg']);
    messageDansLeFil($session, 'pv0001', 'Une question sur la méthode');

    $this->get(route('meeting.chat.export', ['session', $session->id]))->assertForbidden();

    $reponse = $this->actingAs($moderateur, 'account')
        ->get(route('meeting.chat.export', ['session', $session->id]))
        ->assertOk();

    expect($reponse->headers->get('content-type'))->toContain('text/plain');
    expect($reponse->getContent())
        ->toContain('Une question sur la méthode')
        ->toContain('Awa Kodjo');
});

it('annonce l’identifiant de compte dans la métadonnée du jeton', function () {
    config()->set('meetings.default', 'livekit');
    config()->set('meetings.livekit.url', 'wss://exemple.livekit.cloud');
    config()->set('meetings.livekit.api_key', 'cle');
    config()->set('meetings.livekit.api_secret', str_repeat('s', 40));

    $edition = openEdition();
    $session = liveSession($edition, 'public');
    $compte = User::factory()->create();

    $this->actingAs($compte, 'account')->get(route('session.meeting', $session))->assertOk();

    $jeton = $session->liveKitEmbedConfig($compte, false)['token'];
    $claims = json_decode(base64_decode(explode('.', $jeton)[1]), true);

    // ⚠️ C'est un identifiant, et non le nom de famille, qui doit rattacher une
    // communication à la personne présente : deux homonymes suffisaient à donner
    // la parole à la mauvaise.
    expect(json_decode((string) $claims['metadata'], true)['accountId'])->toBe($compte->id);
});

it('donne à la salle les adresses de son fil', function () {
    config()->set('meetings.default', 'livekit');
    config()->set('meetings.livekit.url', 'wss://exemple.livekit.cloud');
    config()->set('meetings.livekit.api_key', 'cle');
    config()->set('meetings.livekit.api_secret', str_repeat('s', 40));

    $edition = openEdition();
    $session = liveSession($edition, 'public');

    // L'adresse voyage dans le JSON de configuration du lecteur : les barres
    // obliques y sont échappées, d'où le passage par json_encode.
    $attendue = trim((string) json_encode(route('meeting.chat.list', ['session', $session->id])), '"');

    $this->get(route('session.meeting', $session))
        ->assertOk()
        ->assertSee($attendue, false);
});
