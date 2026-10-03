<?php

use App\Enums\MeetingRole;
use App\Meetings\LiveKitEgress;
use App\Meetings\MeetingProviderRegistry;
use App\Meetings\Providers\JitsiProvider;
use App\Meetings\Providers\LiveKitProvider;
use App\Models\EventSession;
use App\Models\User;
use App\Support\Branding;
use App\Support\StreamEmbed;
use Illuminate\Support\Facades\Http;

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
    $modo = User::create(['first_name' => 'Mo', 'last_name' => 'Do', 'email' => 'modo@example.tg', 'password' => 'pass12345']);
    $session = liveSession($edition, 'participants', ['moderator_email' => 'modo@example.tg']);

    expect($session->meetingRoleFor(null, isStaff: true))->toBe(MeetingRole::Moderator);
    expect($session->meetingRoleFor($modo))->toBe(MeetingRole::Moderator);
});

it('donne le rôle présentateur au communicant programmé dans la session', function () {
    $edition = openEdition();
    $account = User::create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'password' => 'pass12345']);
    $participant = $edition->participants()->create(['user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
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

    $inscrit = User::create(['first_name' => 'In', 'last_name' => 'Scrit', 'email' => 'in@example.tg', 'password' => 'pass12345']);
    $edition->participants()->create(['user_id' => $inscrit->id, 'first_name' => 'In', 'last_name' => 'Scrit', 'email' => 'in@example.tg', 'role' => 'author']);
    $etranger = User::create(['first_name' => 'Et', 'last_name' => 'Ranger', 'email' => 'et@example.tg', 'password' => 'pass12345']);

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

    $this->actingAs($staff, 'web')->post(route('admin.program.sessions.meeting-status', $session), ['status' => 'live']);
    $this->actingAs($staff, 'web')->post(route('admin.program.sessions.meeting-visibility', $session), ['visibility' => 'public']);

    $session->refresh();
    expect($session->meeting_status)->toBe('live');
    expect($session->meeting_visibility)->toBe('public');
});

it('la page visio embarque la salle pour un inscrit', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'participants');
    $account = User::create(['first_name' => 'In', 'last_name' => 'S', 'email' => 'in@example.tg', 'password' => 'pass12345']);
    $edition->participants()->create(['user_id' => $account->id, 'first_name' => 'In', 'last_name' => 'S', 'email' => 'in@example.tg', 'role' => 'author']);

    $this->actingAs($account, 'account')->get(route('session.meeting', $session))
        ->assertOk()
        ->assertSee('EN DIRECT')
        ->assertSee('JitsiMeetExternalAPI');
});

it('la page visio refuse un non-inscrit', function () {
    $edition = openEdition();
    $session = liveSession($edition, 'participants');
    $etranger = User::create(['first_name' => 'Et', 'last_name' => 'R', 'email' => 'et@example.tg', 'password' => 'pass12345']);

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

    // Configuré → LiveKit (embarqué via notre lecteur inline).
    configureLiveKit();
    expect(app(MeetingProviderRegistry::class)->for($session))->toBeInstanceOf(LiveKitProvider::class);
    expect($session->meetingEmbeddable())->toBeTrue();
});

it('la page visio embarque le lecteur LiveKit inline', function () {
    configureLiveKit();
    $edition = openEdition();
    $session = liveSession($edition, 'public', ['meeting_provider' => 'livekit']);

    $this->get(route('session.meeting', $session))
        ->assertOk()
        ->assertSee('lk-config', false)    // config lue par le bundle Vite (SDK livekit-client)
        ->assertSee('visio-room', false)   // salle inline (topbar/scène/toolbar)
        ->assertSee('stageGrid', false)
        ->assertDontSee('Ouvrir la salle')
        ->assertDontSee('JitsiMeetExternalAPI');
});

it('le staff peut choisir LiveKit pour une session', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $session = $edition->sessions()->create(['type' => 'plenary', 'label' => 'X']);

    $this->actingAs($staff, 'web')->post(route('admin.program.sessions.meeting', $session), [
        'mProvider' => 'livekit', 'mRoom' => 'salle-lk',
    ])->assertSessionHasNoErrors();

    $fresh = $session->fresh();
    expect($fresh->meeting_provider)->toBe('livekit');
    expect($fresh->meeting_room)->toBe('salle-lk');
});

it('liste les communications programmées de la session pour la salle', function () {
    $edition = openEdition();
    $account = User::create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'password' => 'pass12345']);
    $participant = $edition->participants()->create(['user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $sub = $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-001', 'title' => 'Ma communication', 'abstract' => 'x',
        'status' => 'accepted', 'authors' => [['name' => 'Awa Kodjo', 'corresponding' => true]],
    ]);
    $session = liveSession($edition);
    $session->slots()->create(['edition_id' => $edition->id, 'submission_id' => $sub->id, 'participant_id' => 0]);

    $comms = $session->scheduledCommunications();
    expect($comms)->toHaveCount(1);
    expect($comms[0]['title'])->toBe('Ma communication');
    expect($comms[0]['accountId'])->toBe($account->id);
});

it('la config LiveKit porte le rôle et le nom de salle', function () {
    configureLiveKit();
    $edition = openEdition();
    $session = liveSession($edition, 'public', ['meeting_provider' => 'livekit']);

    $cfg = $session->liveKitEmbedConfig(null, false);
    expect($cfg['role'])->toBe('audience');       // anonyme sur session publique
    expect($cfg['roomName'])->not->toBeEmpty();
    expect($cfg['canPublish'])->toBeFalse();
});

// --- Enregistrement (Egress) -----------------------------------------------

/** Configure une destination de stockage S3 factice pour l'Egress. */
function configureEgress(): void
{
    config()->set('meetings.livekit.egress.s3_bucket', 'gesco-rec');
    config()->set('meetings.livekit.egress.s3_region', 'eu-west-3');
    config()->set('meetings.livekit.egress.s3_access_key', 'AKIA');
    config()->set('meetings.livekit.egress.s3_secret', 'sekret');
    config()->set('meetings.livekit.egress.s3_endpoint', null);
}

it('sait si l’enregistrement est exploitable', function () {
    config()->set('meetings.livekit.egress.s3_bucket', null);
    expect(app(LiveKitEgress::class)->isConfigured())->toBeFalse();

    configureEgress();
    expect(app(LiveKitEgress::class)->isConfigured())->toBeTrue();
});

it('le modérateur démarre puis arrête l’enregistrement Egress', function () {
    configureLiveKit();
    configureEgress();
    Http::fake([
        '*livekit.Egress/StartRoomCompositeEgress' => Http::response(['egress_id' => 'EG_123']),
        '*livekit.Egress/StopEgress' => Http::response(['egress_id' => 'EG_123']),
    ]);

    $staff = adminStaff();
    $edition = openEdition();
    $session = liveSession($edition, 'public', ['meeting_provider' => 'livekit', 'meeting_room' => 'salle-lk']);

    $this->actingAs($staff, 'web')
        ->postJson(route('session.egress.start', $session))
        ->assertOk()->assertJson(['ok' => true, 'egressId' => 'EG_123']);

    $this->actingAs($staff, 'web')
        ->postJson(route('session.egress.stop', $session))
        ->assertOk()->assertJson(['ok' => true, 'egressId' => 'EG_123']);

    // La requête de démarrage vise le bon endpoint HTTP (wss→https), la bonne
    // salle et porte la destination S3.
    Http::assertSent(function ($request) use ($session) {
        return str_contains($request->url(), 'https://demo.livekit.cloud/twirp/livekit.Egress/StartRoomCompositeEgress')
            && $request['room_name'] === $session->meetingRoom()
            && $request['file_outputs'][0]['s3']['bucket'] === 'gesco-rec';
    });
});

it('refuse l’enregistrement à un non-modérateur', function () {
    configureLiveKit();
    configureEgress();
    Http::fake();

    $edition = openEdition();
    $session = liveSession($edition, 'public', ['meeting_provider' => 'livekit']);

    $this->postJson(route('session.egress.start', $session))->assertStatus(403);
    Http::assertNothingSent();
});

it('signale un stockage d’enregistrement non configuré', function () {
    configureLiveKit();
    config()->set('meetings.livekit.egress.s3_bucket', null); // incomplet
    Http::fake();

    $staff = adminStaff();
    $edition = openEdition();
    $session = liveSession($edition, 'public', ['meeting_provider' => 'livekit']);

    $this->actingAs($staff, 'web')
        ->postJson(route('session.egress.start', $session))
        ->assertStatus(422)->assertJsonPath('ok', false);
    Http::assertNothingSent();
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

    $this->actingAs($staff, 'web')->post(route('admin.program.sessions.meeting', $session), [
        'mProvider' => 'jitsi', 'mRoom' => 'ma-salle',
    ])->assertSessionHasNoErrors();
    expect($session->fresh()->meeting_room)->toBe('ma-salle');

    $this->actingAs($staff, 'web')->post(route('admin.program.sessions.meeting', $session), [
        'mProvider' => 'custom', 'mUrl' => 'https://meet.google.com/abc',
    ])->assertSessionHasNoErrors();
    $fresh = $session->fresh();
    expect($fresh->meeting_provider)->toBe('custom');
    expect($fresh->meeting_url)->toBe('https://meet.google.com/abc');
    expect($fresh->meeting_room)->toBeNull();
});

it('exige un lien pour le fournisseur externe', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $session = $edition->sessions()->create(['type' => 'plenary', 'label' => 'X']);

    $this->actingAs($staff, 'web')->post(route('admin.program.sessions.meeting', $session), [
        'mProvider' => 'custom', 'mUrl' => '',
    ])->assertSessionHasErrors('mUrl');
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

/**
 * ⚠️ La salle porte le logo RÉGLÉ de la plateforme, pas un monogramme écrit en
 * dur : un « UK » figé dans le gabarit faisait mentir le logo choisi dans
 * Réglages › Général partout ailleurs. Barre sombre → variante fond sombre.
 */
it('affiche dans la salle le logo réglé de la plateforme', function () {
    config()->set('meetings.default', 'livekit');
    config()->set('meetings.livekit.url', 'wss://exemple.livekit.cloud');
    config()->set('meetings.livekit.api_key', 'cle');
    config()->set('meetings.livekit.api_secret', str_repeat('s', 40)); // HS256 exige une clé longue

    $edition = openEdition();
    $session = liveSession($edition, 'public');

    $this->get(route('session.meeting', $session))
        ->assertOk()
        ->assertSee(Branding::markWhite(), false)
        ->assertDontSee('<div class="v-mark">UK</div>', false);
});

/**
 * ⚠️ La salle se tient aussi au téléphone : le panneau latéral y devient un
 * tiroir (voile + croix) et les commandes secondaires une feuille « Plus
 * d'options ». Sans ces trois crochets dans le gabarit, le script ne peut plus
 * ni fermer le tiroir ni déplacer les boutons : la barre déborde de l'écran et
 * le panneau recouvre les visages, exactement l'état d'avant.
 */
it('porte dans la salle les commandes de la version téléphone', function () {
    config()->set('meetings.default', 'livekit');
    config()->set('meetings.livekit.url', 'wss://exemple.livekit.cloud');
    config()->set('meetings.livekit.api_key', 'cle');
    config()->set('meetings.livekit.api_secret', str_repeat('s', 40));

    $edition = openEdition();
    $session = liveSession($edition, 'public');

    $this->get(route('session.meeting', $session))
        ->assertOk()
        ->assertSee('id="sideScrim"', false)
        ->assertSee('id="btnCloseSide"', false)
        ->assertSee('id="btnMore"', false)
        ->assertSee('id="moreSlot"', false)
        // ⚠️ Un `@endverbatim` sans ouvrant traînait dans le gabarit : Blade
        // l'écrivait tel quel, en premier texte de la page.
        ->assertDontSee('endverbatim', false);
});

/**
 * ⚠️ Le rôle doit voyager en MÉTADONNÉE du jeton : c'est la seule chose que les
 * AUTRES participants sauront de lui. Sans elle, chaque salle retombait sur
 * « auditeur » pour tout le monde - le modérateur de la séance apparaissait
 * auditeur dans la liste, et la scène, n'ayant aucun présentateur à montrer,
 * annonçait « en attente de la caméra » pendant que les caméras tournaient.
 */
it('annonce le rôle de chacun dans la métadonnée du jeton LiveKit', function (MeetingRole $role, string $attendu) {
    config()->set('meetings.default', 'livekit');
    config()->set('meetings.livekit.url', 'wss://exemple.livekit.cloud');
    config()->set('meetings.livekit.api_key', 'cle');
    config()->set('meetings.livekit.api_secret', str_repeat('s', 40));

    $edition = openEdition();
    $session = liveSession($edition, 'public');

    $jeton = app(LiveKitProvider::class)->token($session, $role, 'Quelqu’un');
    $claims = json_decode(base64_decode(explode('.', $jeton)[1]), true);

    expect(json_decode((string) $claims['metadata'], true))->toBe(['role' => $attendu]);
})->with([
    'modérateur' => [MeetingRole::Moderator, 'moderator'],
    'communicant' => [MeetingRole::Presenter, 'presenter'],
    // ⚠️ La salle ne connaît pas « attendee » : elle dit « audience ».
    'auditeur' => [MeetingRole::Attendee, 'audience'],
]);
