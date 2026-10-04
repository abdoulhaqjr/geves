<?php

use App\Enums\MeetingRole;
use App\Meetings\MeetingProviderRegistry;
use App\Meetings\Providers\JitsiProvider;
use App\Meetings\Providers\LiveKitProvider;
use App\Support\EditionMeetingConfig;

/**
 * Réglages de visioconférence PROPRES à une édition, sur le modèle du paiement :
 * un colloque peut tenir ses salles sur son propre serveur, et choisir sa qualité
 * vidéo. Tout champ vide retombe sur la configuration de la plateforme.
 */
/** Séance en direct de cette édition (helper local : celui de MeetingTest n'est pas partagé). */
function seanceEnDirect($edition, array $attrs = [])
{
    return $edition->sessions()->create(array_merge([
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

it('ouvre les salles d’une édition sur SON serveur', function () {
    plateformeLiveKit();
    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'meetings' => ['livekit' => [
            'url' => 'wss://colloque.exemple.tg',
            'api_key' => 'APIcolloque',
            'api_secret' => str_repeat('c', 40),
        ]],
    ])]);

    EditionMeetingConfig::using($edition->fresh(), function () {
        expect(config('meetings.livekit.url'))->toBe('wss://colloque.exemple.tg');
        expect(config('meetings.livekit.api_key'))->toBe('APIcolloque');
    });
});

it('rend la configuration de la plateforme en sortant', function () {
    // ⚠️ C'est la raison d'être de `using()` : `apply()` n'écrase que les champs
    // remplis, donc sans restauration les identifiants d'une édition resteraient
    // actifs pour la suivante - et une salle s'ouvrirait sur le serveur d'un
    // autre colloque.
    plateformeLiveKit();
    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'meetings' => ['livekit' => ['url' => 'wss://colloque.exemple.tg']],
    ])]);

    EditionMeetingConfig::using($edition->fresh(), fn () => null);

    expect(config('meetings.livekit.url'))->toBe('wss://plateforme.livekit.cloud');
});

it('laisse un champ vide retomber sur la plateforme', function () {
    plateformeLiveKit();
    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        // Seule l'adresse du serveur est propre à l'édition : les clés restent
        // celles de la plateforme.
        'meetings' => ['livekit' => ['url' => 'wss://colloque.exemple.tg']],
    ])]);

    EditionMeetingConfig::using($edition->fresh(), function () {
        expect(config('meetings.livekit.url'))->toBe('wss://colloque.exemple.tg');
        expect(config('meetings.livekit.api_key'))->toBe('APIplateforme');
    });
});

it('impose le fournisseur choisi par l’édition', function () {
    plateformeLiveKit();
    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'meetings' => ['provider' => 'jitsi'],
    ])]);
    $session = seanceEnDirect($edition->fresh());

    $defaut = EditionMeetingConfig::using(
        $session->edition,
        fn () => app(MeetingProviderRegistry::class)->default(),
    );

    expect($defaut)->toBeInstanceOf(JitsiProvider::class);
    // Hors de l'enveloppe, la plateforme reprend la main.
    expect(app(MeetingProviderRegistry::class)->default())->toBeInstanceOf(LiveKitProvider::class);
});

// --- Qualité vidéo ----------------------------------------------------------

it('retient la qualité choisie par l’édition', function () {
    plateformeLiveKit();
    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'meetings' => ['quality' => 'fhd'],
    ])]);

    expect(EditionMeetingConfig::quality($edition->fresh()))->toBe('fhd');
});

it('retombe sur la plateforme, puis sur « md », devant une qualité inconnue', function () {
    // ⚠️ Un réglage saisi hier et retiré du code demain ne doit pas laisser la
    // salle sans image.
    plateformeLiveKit();
    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'meetings' => ['quality' => 'ultra'],
    ])]);

    expect(EditionMeetingConfig::quality($edition->fresh()))->toBe('md');

    config()->set('meetings.quality', 'n’importe quoi');
    expect(EditionMeetingConfig::quality(null))->toBe('md');

    config()->set('meetings.quality', 'sd');
    expect(EditionMeetingConfig::quality(null))->toBe('sd');
});

it('porte la qualité jusque dans la salle', function () {
    plateformeLiveKit();
    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'meetings' => ['quality' => 'fhd'],
    ])]);
    $session = seanceEnDirect($edition->fresh(), ['meeting_provider' => 'livekit']);

    $cfg = $session->liveKitEmbedConfig(null, false);

    expect($cfg['quality'])->toBe('fhd');
});

it('garde « md » comme défaut de la plateforme', function () {
    // ⚠️ Ce cas fige une RÉGRESSION : le défaut était passé en 720p pour toute la
    // plateforme, ce qui a doublé la liaison montante demandée et fait lâcher les
    // connexions modestes. Le défaut est redevenu ce que la salle faisait avant
    // d'être réglable ; monter reste un choix d'édition.
    expect(EditionMeetingConfig::QUALITY_DEFAULT)->toBe('md');
    expect(EditionMeetingConfig::QUALITIES)->toContain('md');
    expect(config('meetings.quality'))->toBe('md');
});

// --- Enregistrement du formulaire -------------------------------------------

it('n’enregistre que les champs renseignés', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.editions.meetings', $edition), [
        'provider' => 'livekit',
        'quality' => 'sd',
        'livekit' => ['url' => 'wss://colloque.exemple.tg', 'api_key' => '', 'api_secret' => ''],
        'jitsi' => ['domain' => '', 'app_id' => '', 'app_secret' => '', 'room_prefix' => ''],
    ])->assertRedirect();

    $m = $edition->fresh()->settings['meetings'];
    expect($m['provider'])->toBe('livekit');
    expect($m['quality'])->toBe('sd');
    expect($m['livekit'])->toBe(['url' => 'wss://colloque.exemple.tg']);
    // ⚠️ Un sac vide ne doit pas être conservé : « vide » veut dire « par défaut »,
    // pas « rien ».
    expect($m)->not->toHaveKey('jitsi');
});

it('efface le réglage quand tout est vidé', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'meetings' => ['provider' => 'jitsi'],
    ])]);

    $this->actingAs($staff, 'web')->post(route('admin.editions.meetings', $edition), [
        'provider' => '', 'quality' => '', 'livekit' => [], 'jitsi' => [],
    ])->assertRedirect();

    expect($edition->fresh()->settings)->not->toHaveKey('meetings');
});

it('refuse une qualité inventée', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.editions.meetings', $edition), ['quality' => 'ultra'])
        ->assertSessionHasErrors('quality');
});

it('garde le reste des réglages de l’édition intact', function () {
    // ⚠️ La fiche d'édition poste tous ses onglets dans des formulaires distincts :
    // enregistrer la visio ne doit pas emporter le paiement.
    $staff = adminStaff();
    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'payment' => ['gateway' => 'fedapay'],
    ])]);

    $this->actingAs($staff, 'web')->post(route('admin.editions.meetings', $edition), [
        'provider' => 'jitsi',
    ])->assertRedirect();

    $settings = $edition->fresh()->settings;
    expect($settings['payment']['gateway'])->toBe('fedapay');
    expect($settings['meetings']['provider'])->toBe('jitsi');
});

it('réserve ces réglages à qui peut écrire sur l’édition', function () {
    $edition = openEdition();

    $this->post(route('admin.editions.meetings', $edition), ['provider' => 'jitsi'])
        ->assertRedirect();

    expect($edition->fresh()->settings)->not->toHaveKey('meetings');
});

it('donne au jeton de la salle le serveur de l’édition', function () {
    // Bout en bout : la salle d'une séance de cette édition est signée avec la clé
    // du colloque, pas avec celle de la plateforme.
    plateformeLiveKit();
    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'meetings' => ['livekit' => [
            'url' => 'wss://colloque.exemple.tg',
            'api_key' => 'APIcolloque',
            'api_secret' => str_repeat('c', 40),
        ]],
    ])]);
    $session = seanceEnDirect($edition->fresh(), ['meeting_provider' => 'livekit']);

    $cfg = $session->liveKitEmbedConfig(null, false);

    expect($cfg['url'])->toBe('wss://colloque.exemple.tg');
    $charge = json_decode(base64_decode(explode('.', $cfg['token'])[1]), true);
    expect($charge['iss'])->toBe('APIcolloque');
    expect($cfg['role'])->toBe(MeetingRole::Attendee->roomRole());
});
