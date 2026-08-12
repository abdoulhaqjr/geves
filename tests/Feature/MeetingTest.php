<?php

use App\Enums\MeetingRole;
use App\Livewire\Admin\Program\Sessions;
use App\Meetings\MeetingProviderRegistry;
use App\Meetings\Providers\JitsiProvider;
use App\Meetings\Providers\LiveKitProvider;
use App\Models\Account;
use App\Models\EventSession;
use App\Support\StreamEmbed;
use Livewire\Livewire;

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

// --- UI : back-office & page « Rejoindre » ---------------------------------

it('le staff pilote l’état et la visibilité de la visio', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $session = $edition->sessions()->create(['type' => 'plenary', 'label' => 'X']);

    Livewire::actingAs($staff, 'web')
        ->test(Sessions::class)
        ->call('setMeetingStatus', $session->id, 'live')
        ->call('setMeetingVisibility', $session->id, 'public');

    $session->refresh();
    expect($session->meeting_status)->toBe('live');
    expect($session->meeting_visibility)->toBe('public');
});

it('la page visio embarque la salle pour un inscrit', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'participants');
    $account = Account::create(['first_name' => 'In', 'last_name' => 'S', 'email' => 'in@example.tg', 'password' => 'pass12345']);
    $edition->participants()->create(['account_id' => $account->id, 'first_name' => 'In', 'last_name' => 'S', 'email' => 'in@example.tg', 'role' => 'author']);

    $this->actingAs($account, 'account')->get(route('session.meeting', $session))
        ->assertOk()
        ->assertSee('EN DIRECT')
        ->assertSee('JitsiMeetExternalAPI');
});

it('la page visio refuse un non-inscrit', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'participants');
    $etranger = Account::create(['first_name' => 'Et', 'last_name' => 'R', 'email' => 'et@example.tg', 'password' => 'pass12345']);

    $this->actingAs($etranger, 'account')->get(route('session.meeting', $session))
        ->assertOk()
        ->assertSee('Accès réservé')
        ->assertDontSee('JitsiMeetExternalAPI');
});

it('la page visio est ouverte à tous pour une session publique', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'public');

    $this->get(route('session.meeting', $session))
        ->assertOk()
        ->assertSee('JitsiMeetExternalAPI');
});

it('la page visio indique quand la visio n’est pas en cours', function () {
    $edition = openEdition();
    $session = $edition->sessions()->create(['type' => 'plenary', 'label' => 'X', 'meeting_status' => 'scheduled', 'meeting_visibility' => 'public']);

    $this->get(route('session.meeting', $session))
        ->assertOk()
        ->assertSee('pas en cours')
        ->assertDontSee('JitsiMeetExternalAPI');
});

// --- LiveKit ----------------------------------------------------------------

/** Configure des identifiants LiveKit factices pour les tests. */
function configureLiveKit(): void
{
    config()->set('meetings.livekit.url', 'wss://demo.livekit.cloud');
    config()->set('meetings.livekit.api_key', 'APIabc');
    config()->set('meetings.livekit.api_secret', str_repeat('s', 40));
    config()->set('meetings.livekit.meet_url', 'https://meet.livekit.io/custom');
}

it('génère un token LiveKit avec les droits dérivés du rôle', function () {
    configureLiveKit();
    $edition = openEdition();
    $session = liveSession($edition, 'public', ['meeting_room' => 'salle-lk']);
    $provider = new LiveKitProvider;

    expect($provider->isAvailable())->toBeTrue();

    $decode = fn (string $t) => json_decode(base64_decode(explode('.', $t)[1]), true);

    $mod = $decode($provider->token($session, MeetingRole::Moderator, 'Modo'));
    expect($mod['iss'])->toBe('APIabc');
    expect($mod['video']['room'])->toBe('salle-lk');
    expect($mod['video']['roomAdmin'])->toBeTrue();
    expect($mod['video']['canPublish'])->toBeTrue();

    $pres = $decode($provider->token($session, MeetingRole::Presenter, 'P'));
    expect($pres['video']['canPublish'])->toBeTrue();
    expect($pres['video']['roomAdmin'])->toBeFalse();

    $att = $decode($provider->token($session, MeetingRole::Attendee, 'A'));
    expect($att['video']['canPublish'])->toBeFalse();
    expect($att['video']['canSubscribe'])->toBeTrue();
});

it('construit l’URL LiveKit Meet avec serveur et token', function () {
    configureLiveKit();
    $edition = openEdition();
    $session = liveSession($edition, 'public');

    $url = (new LiveKitProvider)->joinUrl($session, MeetingRole::Attendee, 'A');

    expect($url)->toStartWith('https://meet.livekit.io/custom?');
    expect($url)->toContain('liveKitUrl=');
    expect($url)->toContain('token=');
});

it('résout LiveKit quand configuré, retombe sur Jitsi sinon', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'public', ['meeting_provider' => 'livekit']);

    // Sans identifiants → repli Jitsi (pas de salle cassée).
    config()->set('meetings.livekit.url', null);
    expect(app(MeetingProviderRegistry::class)->for($session))->toBeInstanceOf(JitsiProvider::class);

    // Configuré → LiveKit (non embarquable → nouvel onglet).
    configureLiveKit();
    expect(app(MeetingProviderRegistry::class)->for($session))->toBeInstanceOf(LiveKitProvider::class);
    expect($session->meetingEmbeddable())->toBeFalse();
});

it('la page visio ouvre la salle LiveKit en nouvel onglet', function () {
    configureLiveKit();
    $edition = openEdition();
    $session = liveSession($edition, 'public', ['meeting_provider' => 'livekit']);

    $this->get(route('session.meeting', $session))
        ->assertOk()
        ->assertSee('Ouvrir la salle')
        ->assertDontSee('JitsiMeetExternalAPI');
});

it('le staff peut choisir LiveKit pour une session', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $session = $edition->sessions()->create(['type' => 'plenary', 'label' => 'X']);

    Livewire::actingAs($staff, 'web')->test(Sessions::class)
        ->call('editMeeting', $session->id)
        ->set('mProvider', 'livekit')->set('mRoom', 'salle-lk')
        ->call('saveMeeting')->assertHasNoErrors();

    $fresh = $session->fresh();
    expect($fresh->meeting_provider)->toBe('livekit');
    expect($fresh->meeting_room)->toBe('salle-lk');
});

// --- Salle / lien custom ----------------------------------------------------

it('utilise le lien externe quand le fournisseur custom est configuré', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'public', ['meeting_provider' => 'custom', 'meeting_url' => 'https://zoom.us/j/123']);

    expect($session->meetingEmbeddable())->toBeFalse();
    expect($session->joinUrl(null))->toBe('https://zoom.us/j/123');
});

it('le staff personnalise fournisseur, salle et lien', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $session = $edition->sessions()->create(['type' => 'plenary', 'label' => 'X']);

    Livewire::actingAs($staff, 'web')->test(Sessions::class)
        ->call('editMeeting', $session->id)
        ->set('mProvider', 'jitsi')->set('mRoom', 'ma-salle')
        ->call('saveMeeting')->assertHasNoErrors();
    expect($session->fresh()->meeting_room)->toBe('ma-salle');

    Livewire::actingAs($staff, 'web')->test(Sessions::class)
        ->call('editMeeting', $session->id)
        ->set('mProvider', 'custom')->set('mUrl', 'https://meet.google.com/abc')
        ->call('saveMeeting')->assertHasNoErrors();
    $fresh = $session->fresh();
    expect($fresh->meeting_provider)->toBe('custom');
    expect($fresh->meeting_url)->toBe('https://meet.google.com/abc');
    expect($fresh->meeting_room)->toBeNull();
});

it('exige un lien pour le fournisseur externe', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $session = $edition->sessions()->create(['type' => 'plenary', 'label' => 'X']);

    Livewire::actingAs($staff, 'web')->test(Sessions::class)
        ->call('editMeeting', $session->id)
        ->set('mProvider', 'custom')->set('mUrl', '')
        ->call('saveMeeting')->assertHasErrors('mUrl');
});

it('la page visio propose un nouvel onglet pour un lien externe', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'public', ['meeting_provider' => 'custom', 'meeting_url' => 'https://zoom.us/j/999']);

    $this->get(route('session.meeting', $session))
        ->assertOk()
        ->assertSee('nouvel onglet')
        ->assertSee('https://zoom.us/j/999')
        ->assertDontSee('JitsiMeetExternalAPI');
});
