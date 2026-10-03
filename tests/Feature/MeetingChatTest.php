<?php

use App\Models\MeetingMessage;
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

it('n’enregistre que les réactions proposées', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'public');
    messageDansLeFil($session, 'r00002');

    $this->postJson(route('meeting.chat.react', ['session', $session->id, 'r00002']), ['emoji' => '💣'])
        ->assertStatus(422);
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
