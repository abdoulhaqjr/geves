<?php

use App\Enums\MeetingRole;
use App\Meetings\MeetingProviderRegistry;
use App\Meetings\Providers\JitsiProvider;
use App\Models\Account;
use App\Models\EventSession;
use App\Support\StreamEmbed;

// openEdition() -> AccountSubmissionTest

/** Session de visio prête (statut live, visibilité donnée). */
function liveSession($edition, string $visibility = 'participants', array $attrs = [])
{
    return $edition->sessions()->create(array_merge([
        'type' => 'plenary', 'label' => 'Séance', 'meeting_status' => 'live', 'meeting_visibility' => $visibility,
    ], $attrs));
}

// --- StreamEmbed (replay YouTube/Vimeo) ------------------------------------

it('normalise un lien de replay YouTube', function () {
    expect(StreamEmbed::toEmbedUrl('https://youtu.be/dQw4w9WgXcQ'))
        ->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ');
    expect(StreamEmbed::provider('https://vimeo.com/123456789'))->toBe('vimeo');
    expect(StreamEmbed::toEmbedUrl(''))->toBeNull();
});

// --- Fournisseur Jitsi ------------------------------------------------------

it('résout Jitsi comme fournisseur par défaut', function () {
    $registry = new MeetingProviderRegistry;
    expect($registry->default())->toBeInstanceOf(JitsiProvider::class);
    expect($registry->default()->key())->toBe('jitsi');
});

it('construit une URL Jitsi publique avec le nom d’affichage', function () {
    config()->set('meetings.jitsi.domain', 'meet.jit.si');
    config()->set('meetings.jitsi.app_secret', null);

    $edition = openEdition();
    $session = liveSession($edition, 'public', ['meeting_room' => 'salle-plenary']);

    $url = (new JitsiProvider)->joinUrl($session, MeetingRole::Attendee, 'Awa Kodjo');

    expect($url)->toStartWith('https://meet.jit.si/salle-plenary');
    expect($url)->toContain('displayName');
});

it('émet un JWT Jitsi quand un secret self-host est configuré', function () {
    config()->set('meetings.jitsi.app_id', 'gesco');
    config()->set('meetings.jitsi.app_secret', str_repeat('k', 40));

    $edition = openEdition();
    $session = liveSession($edition, 'public', ['meeting_room' => 'salle-1']);
    $provider = new JitsiProvider;

    expect($provider->usesJwt())->toBeTrue();
    $url = $provider->joinUrl($session, MeetingRole::Moderator, 'Modo');
    expect($url)->toContain('?jwt=');

    // Le jeton porte bien le drapeau modérateur.
    $jwt = explode('?jwt=', $url)[1];
    $claims = json_decode(base64_decode(explode('.', $jwt)[1]), true);
    expect($claims['context']['user']['moderator'])->toBeTrue();
    expect($claims['room'])->toBe('salle-1');
});

// --- EventSession : état & salle -------------------------------------------

it('reflète l’état de la visio', function () {
    $edition = openEdition();

    $none = $edition->sessions()->create(['type' => 'plenary', 'label' => 'Sans visio']);
    expect($none->hasMeeting())->toBeFalse();
    expect($none->isLive())->toBeFalse();

    $live = liveSession($edition);
    expect($live->hasMeeting())->toBeTrue();
    expect($live->isLive())->toBeTrue();
    expect($live->meetingRoom())->not->toBeEmpty(); // auto-généré
});

it('le scope live ne retourne que les sessions en direct', function () {
    $edition = openEdition();
    liveSession($edition);
    $edition->sessions()->create(['type' => 'plenary', 'label' => 'B', 'meeting_status' => 'scheduled']);

    expect(EventSession::query()->live()->count())->toBe(1);
});

// --- Rôles & accès ----------------------------------------------------------

it('donne le rôle modérateur au staff et au modérateur assigné', function () {
    $edition = openEdition();
    $modo = Account::create(['first_name' => 'Mo', 'last_name' => 'Do', 'email' => 'modo@example.tg', 'password' => 'pass12345']);
    $session = liveSession($edition, 'participants', ['moderator_email' => 'modo@example.tg']);

    expect($session->meetingRoleFor(null, isStaff: true))->toBe(MeetingRole::Moderator);
    expect($session->meetingRoleFor($modo))->toBe(MeetingRole::Moderator);
});

it('donne le rôle présentateur au communicant programmé dans la session', function () {
    $edition = openEdition();
    $account = Account::create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'password' => 'pass12345']);
    $participant = $edition->participants()->create(['account_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $submission = $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-001', 'title' => 'Com', 'abstract' => 'x',
        'status' => 'accepted', 'authors' => [['name' => 'Awa Kodjo', 'corresponding' => true]],
    ]);
    $session = liveSession($edition);
    $session->slots()->create(['edition_id' => $edition->id, 'submission_id' => $submission->id, 'participant_id' => 0]);

    expect($session->presenterAccountIds())->toContain($account->id);
    expect($session->meetingRoleFor($account))->toBe(MeetingRole::Presenter);
});

it('réserve la visio « participants » aux inscrits et fournit une URL de connexion', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'participants');

    $inscrit = Account::create(['first_name' => 'In', 'last_name' => 'Scrit', 'email' => 'in@example.tg', 'password' => 'pass12345']);
    $edition->participants()->create(['account_id' => $inscrit->id, 'first_name' => 'In', 'last_name' => 'Scrit', 'email' => 'in@example.tg', 'role' => 'author']);
    $etranger = Account::create(['first_name' => 'Et', 'last_name' => 'Ranger', 'email' => 'et@example.tg', 'password' => 'pass12345']);

    expect($session->meetingRoleFor($inscrit))->toBe(MeetingRole::Attendee);
    expect($session->canJoin($inscrit))->toBeTrue();
    expect($session->joinUrl($inscrit))->toStartWith('https://');

    expect($session->meetingRoleFor($etranger))->toBe(MeetingRole::None);
    expect($session->canJoin($etranger))->toBeFalse();
    expect($session->joinUrl($etranger))->toBeNull();
});

it('autorise tout le monde sur une visio publique', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'public');

    expect($session->meetingRoleFor(null))->toBe(MeetingRole::Attendee);
    expect($session->canJoin(null))->toBeTrue();
});
