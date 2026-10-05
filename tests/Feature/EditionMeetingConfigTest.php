<?php

use App\Enums\MeetingRole;
use App\Meetings\MeetingProviderRegistry;
use App\Meetings\Providers\JitsiProvider;
use App\Meetings\Providers\LiveKitProvider;
use App\Support\EventMeetingConfig;

/**
 * Réglages de visioconférence PROPRES à un événement, sur le modèle du paiement :
 * un colloque peut tenir ses salles sur son propre serveur, et choisir sa qualité
 * vidéo. Tout champ vide retombe sur la configuration de la plateforme.
 */
/** Séance en direct de cet événement (helper local : celui de MeetingTest n'est pas partagé). */
function seanceEnDirect($event, array $attrs = [])
{
    return $event->sessions()->create(array_merge([
        'type' => 'plenary', 'label' => 'Séance', 'meeting_status' => 'live',
        'meeting_visibility' => 'public',
    ], $attrs));
}

function plateformeLiveKit(): void
{
    config()->set('meetings.default', 'livekit');
    config()->set('meetings.livekit.url', 'wss://plateforme.livekit.cloud');
    config()->set('meetings.livekit.api_key', 'APIplateforme');
    config()->set('meetings.livekit.api_secret', str_repeat('p', 40));
    config()->set('meetings.quality', 'md');
}

it('ouvre les salles d’un événement sur SON serveur', function () {
    plateformeLiveKit();
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'meetings' => ['livekit' => [
            'url' => 'wss://colloque.exemple.tg',
            'api_key' => 'APIcolloque',
            'api_secret' => str_repeat('c', 40),
        ]],
    ])]);

    EventMeetingConfig::using($event->fresh(), function () {
        expect(config('meetings.livekit.url'))->toBe('wss://colloque.exemple.tg');
        expect(config('meetings.livekit.api_key'))->toBe('APIcolloque');
    });
});

it('rend la configuration de la plateforme en sortant', function () {
    // ⚠️ C'est la raison d'être de `using()` : `apply()` n'écrase que les champs
    // remplis, donc sans restauration les identifiants d'un événement resteraient
    // actifs pour la suivante - et une salle s'ouvrirait sur le serveur d'un
    // autre colloque.
    plateformeLiveKit();
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'meetings' => ['livekit' => ['url' => 'wss://colloque.exemple.tg']],
    ])]);

    EventMeetingConfig::using($event->fresh(), fn () => null);

    expect(config('meetings.livekit.url'))->toBe('wss://plateforme.livekit.cloud');
});

it('laisse un champ vide retomber sur la plateforme', function () {
    plateformeLiveKit();
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        // Seule l'adresse du serveur est propre à l'événement : les clés restent
        // celles de la plateforme.
        'meetings' => ['livekit' => ['url' => 'wss://colloque.exemple.tg']],
    ])]);

    EventMeetingConfig::using($event->fresh(), function () {
        expect(config('meetings.livekit.url'))->toBe('wss://colloque.exemple.tg');
        expect(config('meetings.livekit.api_key'))->toBe('APIplateforme');
    });
});

it('impose le fournisseur choisi par l’événement', function () {
    plateformeLiveKit();
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'meetings' => ['provider' => 'jitsi'],
    ])]);
    $session = seanceEnDirect($event->fresh());

    $defaut = EventMeetingConfig::using(
        $session->event,
        fn () => app(MeetingProviderRegistry::class)->default(),
    );

    expect($defaut)->toBeInstanceOf(JitsiProvider::class);
    // Hors de l'enveloppe, la plateforme reprend la main.
    expect(app(MeetingProviderRegistry::class)->default())->toBeInstanceOf(LiveKitProvider::class);
});

// --- Qualité vidéo ----------------------------------------------------------

it('retient la qualité choisie par l’événement', function () {
    plateformeLiveKit();
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'meetings' => ['quality' => 'fhd'],
    ])]);

    expect(EventMeetingConfig::quality($event->fresh()))->toBe('fhd');
});

it('retombe sur la plateforme, puis sur « md », devant une qualité inconnue', function () {
    // ⚠️ Un réglage saisi hier et retiré du code demain ne doit pas laisser la
    // salle sans image.
    plateformeLiveKit();
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'meetings' => ['quality' => 'ultra'],
    ])]);

    expect(EventMeetingConfig::quality($event->fresh()))->toBe('md');

    config()->set('meetings.quality', 'n’importe quoi');
    expect(EventMeetingConfig::quality(null))->toBe('md');

    config()->set('meetings.quality', 'sd');
    expect(EventMeetingConfig::quality(null))->toBe('sd');
});

it('porte la qualité jusque dans la salle', function () {
    plateformeLiveKit();
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'meetings' => ['quality' => 'fhd'],
    ])]);
    $session = seanceEnDirect($event->fresh(), ['meeting_provider' => 'livekit']);

    $cfg = $session->liveKitEmbedConfig(null, false);

    expect($cfg['quality'])->toBe('fhd');
});

it('garde « md » comme défaut de la plateforme', function () {
    // ⚠️ Ce cas fige une RÉGRESSION : le défaut était passé en 720p pour toute la
    // plateforme, ce qui a doublé la liaison montante demandée et fait lâcher les
    // connexions modestes. Le défaut est redevenu ce que la salle faisait avant
    // d'être réglable ; monter reste un choix d'événement.
    expect(EventMeetingConfig::QUALITY_DEFAULT)->toBe('md');
    expect(EventMeetingConfig::QUALITIES)->toContain('md');
    expect(config('meetings.quality'))->toBe('md');
});

// --- Enregistrement du formulaire -------------------------------------------

it('n’enregistre que les champs renseignés', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.evenements.meetings', $event), [
        'provider' => 'livekit',
        'quality' => 'sd',
        'livekit' => ['url' => 'wss://colloque.exemple.tg', 'api_key' => '', 'api_secret' => ''],
        'jitsi' => ['domain' => '', 'app_id' => '', 'app_secret' => '', 'room_prefix' => ''],
    ])->assertRedirect();

    $m = $event->fresh()->settings['meetings'];
    expect($m['provider'])->toBe('livekit');
    expect($m['quality'])->toBe('sd');
    expect($m['livekit'])->toBe(['url' => 'wss://colloque.exemple.tg']);
    // ⚠️ Un sac vide ne doit pas être conservé : « vide » veut dire « par défaut »,
    // pas « rien ».
    expect($m)->not->toHaveKey('jitsi');
});

it('efface le réglage quand tout est vidé', function () {
    $staff = adminStaff();
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'meetings' => ['provider' => 'jitsi'],
    ])]);

    $this->actingAs($staff, 'web')->post(route('admin.evenements.meetings', $event), [
        'provider' => '', 'quality' => '', 'livekit' => [], 'jitsi' => [],
    ])->assertRedirect();

    expect($event->fresh()->settings)->not->toHaveKey('meetings');
});

it('refuse une qualité inventée', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.evenements.meetings', $event), ['quality' => 'ultra'])
        ->assertSessionHasErrors('quality');
});

it('garde le reste des réglages de l’événement intact', function () {
    // ⚠️ La fiche d'événement poste tous ses onglets dans des formulaires distincts :
    // enregistrer la visio ne doit pas emporter le paiement.
    $staff = adminStaff();
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'payment' => ['gateway' => 'fedapay'],
    ])]);

    $this->actingAs($staff, 'web')->post(route('admin.evenements.meetings', $event), [
        'provider' => 'jitsi',
    ])->assertRedirect();

    $settings = $event->fresh()->settings;
    expect($settings['payment']['gateway'])->toBe('fedapay');
    expect($settings['meetings']['provider'])->toBe('jitsi');
});

it('réserve ces réglages à qui peut écrire sur l’événement', function () {
    $event = openEdition();

    $this->post(route('admin.evenements.meetings', $event), ['provider' => 'jitsi'])
        ->assertRedirect();

    expect($event->fresh()->settings)->not->toHaveKey('meetings');
});

it('donne au jeton de la salle le serveur de l’événement', function () {
    // Bout en bout : la salle d'une séance de cet événement est signée avec la clé
    // du colloque, pas avec celle de la plateforme.
    plateformeLiveKit();
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'meetings' => ['livekit' => [
            'url' => 'wss://colloque.exemple.tg',
            'api_key' => 'APIcolloque',
            'api_secret' => str_repeat('c', 40),
        ]],
    ])]);
    $session = seanceEnDirect($event->fresh(), ['meeting_provider' => 'livekit']);

    $cfg = $session->liveKitEmbedConfig(null, false);

    expect($cfg['url'])->toBe('wss://colloque.exemple.tg');
    $charge = json_decode(base64_decode(explode('.', $cfg['token'])[1]), true);
    expect($charge['iss'])->toBe('APIcolloque');
    expect($cfg['role'])->toBe(MeetingRole::Attendee->roomRole());
});
