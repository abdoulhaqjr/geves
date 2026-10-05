<?php

use App\Models\Event;
use App\Models\EventSeries;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Images d'habillage d'une événement : bandeau (hero) et bannière officielle,
 * réglées dans l'onglet **Apparence** de la fiche d'événement.
 *
 * Les deux acceptent un téléversement **ou** une URL : une partie des événements
 * pointe vers une affiche hébergée ailleurs.
 */

// adminStaff() -> tests/Pest.php

/** Le minimum que la fiche d'événement exige, pour ne poster que l'habillage. */
function editionBase(Event $event): array
{
    return [
        'title' => $event->title,
        'status' => 'open',
        'event_series_id' => $event->event_series_id,
        'max_communications' => 3,
    ];
}

function artworkEdition(): Event
{
    $series = EventSeries::create(['name' => 'UK', 'slug' => 'uk']);

    return $series->events()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026']);
}

it('téléverse le bandeau et la bannière depuis l’onglet Apparence', function () {
    Storage::fake('public');
    $event = artworkEdition();

    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'hero_image_file' => UploadedFile::fake()->image('hero.jpg', 1920, 800),
            'banner_image_file' => UploadedFile::fake()->image('banniere.jpg', 1680, 720),
        ])
        ->assertSessionHasNoErrors();

    $event->refresh();

    expect($event->settings['hero_image'])->toContain('events/');
    expect($event->banner_url)->toContain('events/');

    foreach ([$event->settings['hero_image'], $event->banner_url] as $url) {
        Storage::disk('public')->assertExists(storagePath($url));
    }
});

it('remplace le fichier précédent au lieu de l’empiler', function () {
    Storage::fake('public');
    $event = artworkEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->put(route('admin.evenements.update', $event), editionBase($event) + [
        'banner_image_file' => UploadedFile::fake()->image('premiere.jpg'),
    ]);
    $first = $event->refresh()->banner_url;

    $this->actingAs($staff, 'web')->put(route('admin.evenements.update', $event), editionBase($event) + [
        'banner_image' => $first,   // le champ URL renvoie la valeur affichée
        'banner_image_file' => UploadedFile::fake()->image('seconde.jpg'),
    ]);
    $second = $event->refresh()->banner_url;

    // ⚠️ Une bannière n'a pas d'historique : l'ancienne ne doit plus être servie.
    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing(storagePath($first));
    Storage::disk('public')->assertExists(storagePath($second));
});

it('accepte une URL externe et ne cherche pas à la supprimer', function () {
    Storage::fake('public');
    $event = artworkEdition();
    $staff = adminStaff();

    $externe = 'https://cdn.example.org/affiche.jpg';

    $this->actingAs($staff, 'web')->put(route('admin.evenements.update', $event), editionBase($event) + [
        'banner_image' => $externe,
    ])->assertSessionHasNoErrors();

    expect($event->refresh()->banner_url)->toBe($externe);

    // Ré-enregistrer sans toucher au champ ne doit rien changer.
    $this->actingAs($staff, 'web')->put(route('admin.evenements.update', $event), editionBase($event) + [
        'banner_image' => $externe,
    ]);

    expect($event->refresh()->banner_url)->toBe($externe);
});

it('supprime l’image quand l’admin le demande', function () {
    Storage::fake('public');
    $event = artworkEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->put(route('admin.evenements.update', $event), editionBase($event) + [
        'hero_image_file' => UploadedFile::fake()->image('hero.jpg'),
    ]);
    $hero = $event->refresh()->settings['hero_image'];

    $this->actingAs($staff, 'web')->put(route('admin.evenements.update', $event), editionBase($event) + [
        'hero_image_remove' => '1',
    ]);

    $event->refresh();
    expect($event->settings['hero_image'])->toBeNull();
    Storage::disk('public')->assertMissing(storagePath($hero));
});

it('garde l’habillage quand on enregistre un autre onglet', function () {
    Storage::fake('public');
    $event = artworkEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->put(route('admin.evenements.update', $event), editionBase($event) + [
        'banner_image_file' => UploadedFile::fake()->image('banniere.jpg'),
    ]);
    $banner = $event->refresh()->banner_url;

    // ⚠️ La fiche poste tous les onglets d'un coup : un enregistrement qui ne
    // renvoie pas le champ image ne doit pas effacer la bannière.
    $this->actingAs($staff, 'web')->put(route('admin.evenements.update', $event), editionBase($event) + [
        'subtitle' => 'Nouveau sous-titre',
    ]);

    expect($event->refresh()->banner_url)->toBe($banner);
    Storage::disk('public')->assertExists(storagePath($banner));
});

it('active et désactive la bannière officielle', function () {
    Storage::fake('public');
    $event = artworkEdition();
    $staff = adminStaff();

    $poser = fn (array $extra) => $this->actingAs($staff, 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + $extra + [
            'banner_image' => 'https://cdn.example.org/affiche.jpg',
        ]);

    $poser(['banner_enabled' => '1']);
    expect($event->refresh()->settings['banner_enabled'])->toBeTrue();

    auth('web')->logout();
    $this->get(route('public.colloque', $event->slug))->assertOk()->assertSee('id="banniere"', false);

    // Décochée, la case n'est pas postée du tout : c'est bien l'absence qui vaut « non ».
    $poser([]);
    expect($event->refresh()->settings['banner_enabled'])->toBeFalse();

    auth('web')->logout();
    $this->get(route('public.colloque', $event->slug))->assertOk()->assertDontSee('id="banniere"', false);
});

it('téléverse la vidéo de fond et n’en sert le flux que si elle est activée', function () {
    Storage::fake('public');
    $event = artworkEdition();
    $staff = adminStaff();

    // ⚠️ La règle est `mimetypes:` (type réel, pas l'extension) : le faux
    // fichier doit donc annoncer son type, un .mp4 vide n'étant pas reconnu.
    $mp4 = UploadedFile::fake()->create('fond.mp4', 300, 'video/mp4');

    $this->actingAs($staff, 'web')->put(route('admin.evenements.update', $event), editionBase($event) + [
        'hero_video_file' => $mp4,
        'hero_video_enabled' => '1',
    ])->assertSessionHasNoErrors();

    $event->refresh();
    $video = $event->settings['hero_video'];
    expect($video)->toContain('events/');
    Storage::disk('public')->assertExists(storagePath($video));

    auth('web')->logout();
    $this->get(route('public.colloque', $event->slug))->assertOk()->assertSee('<video', false);

    // ⚠️ Décochée, la case doit réellement couper la vidéo : le fichier reste
    // enregistré, mais la page publique ne le sert plus.
    $this->actingAs($staff, 'web')->put(route('admin.evenements.update', $event), editionBase($event) + [
        'hero_video' => $video,
    ]);

    expect($event->refresh()->settings['hero_video'])->toBe($video);

    auth('web')->logout();
    $this->get(route('public.colloque', $event->slug))->assertOk()->assertDontSee('<video', false);
});

it('règle le bandeau promotionnel depuis l’onglet Apparence', function () {
    $event = artworkEdition();

    // ⚠️ Le bandeau s'affichait déjà côté public mais n'avait AUCUN champ : il
    // était inatteignable. L'encart « actualité » qui occupait sa place dans le
    // formulaire, lui, n'était lu nulle part.
    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'promo_text' => 'Soumissions ouvertes jusqu’au 15 juillet 2026',
            'promo_url' => 'https://geves.test/soumettre',
            'promo_cta' => 'Déposer un résumé',
        ])
        ->assertSessionHasNoErrors();

    auth('web')->logout();

    $this->get(route('public.colloque', $event->refresh()->slug))
        ->assertOk()
        ->assertSee('Soumissions ouvertes jusqu’au 15 juillet 2026', false)
        ->assertSee('Déposer un résumé', false);
});

it('refuse un fichier qui n’est pas une vidéo', function () {
    Storage::fake('public');
    $event = artworkEdition();

    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'hero_video_file' => UploadedFile::fake()->image('affiche.jpg'),
        ])
        ->assertSessionHasErrors('hero_video_file');

    expect($event->refresh()->settings['hero_video'] ?? null)->toBeNull();
});

it('refuse un fichier qui n’est pas une image', function () {
    Storage::fake('public');
    $event = artworkEdition();

    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'banner_image_file' => UploadedFile::fake()->create('programme.pdf', 60, 'application/pdf'),
        ])
        ->assertSessionHasErrors('banner_image_file');

    expect($event->refresh()->banner_url)->toBeNull();
});

it('affiche la bannière pleine largeur sur la page publique', function () {
    Storage::fake('public');
    $event = artworkEdition();

    $this->actingAs(adminStaff(), 'web')->put(route('admin.evenements.update', $event), editionBase($event) + [
        'banner_image_file' => UploadedFile::fake()->image('banniere.jpg', 1680, 720),
        'banner_enabled' => '1',
    ]);

    auth('web')->logout();

    $this->get(route('public.colloque', $event->refresh()->slug))
        ->assertOk()
        ->assertSee($event->banner_url, false);
});

/** Chemin disque d'une URL produite par le disque public. */
function storagePath(string $url): string
{
    return trim(str_replace('/storage/', '', (string) parse_url($url, PHP_URL_PATH)), '/');
}

/**
 * Logos des partenaires (onglet **Partenaires**) : même règle que l'habillage,
 * mais dans un **répéteur** - la liste se réordonne, se vide et se complète.
 */
it('téléverse le logo d’un partenaire, fichier ou URL', function () {
    Storage::fake('public');
    $event = artworkEdition();

    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'partners' => [
                ['name' => 'Université de Kara', 'logo_file' => UploadedFile::fake()->image('uk.png', 400, 200)],
                ['name' => 'Ministère', 'logo_url' => 'https://ailleurs.tg/logo.png'],
            ],
        ])
        ->assertSessionHasNoErrors();

    $partners = $event->refresh()->settings['partners'];

    expect($partners)->toHaveCount(2);
    expect($partners[0]['logo_url'])->toContain('events/');
    Storage::disk('public')->assertExists(storagePath($partners[0]['logo_url']));
    // Une URL externe reste telle quelle : elle ne nous appartient pas.
    expect($partners[1]['logo_url'])->toBe('https://ailleurs.tg/logo.png');
});

/**
 * ⚠️ L'ordre de la liste EST celui du bandeau public : il doit survivre à
 * l'enregistrement tel que l'admin l'a rangé, sans tri d'aucune sorte.
 */
it('garde l’ordre des partenaires tel qu’il a été rangé', function () {
    Storage::fake('public');
    $event = artworkEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'partners' => [['name' => 'Alpha'], ['name' => 'Beta'], ['name' => 'Gamma']],
        ])->assertSessionHasNoErrors();

    // L'admin remonte Gamma en tête.
    $this->actingAs($staff, 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'partners' => [['name' => 'Gamma'], ['name' => 'Alpha'], ['name' => 'Beta']],
        ])->assertSessionHasNoErrors();

    expect(array_column($event->refresh()->settings['partners'], 'name'))
        ->toBe(['Gamma', 'Alpha', 'Beta']);
});

/**
 * ⚠️ Le ménage se fait sur les DEUX listes entières, jamais ligne à ligne :
 * après un déplacement, l'index 0 ne désigne plus le même partenaire, et un
 * rapprochement par position effacerait le logo du voisin.
 */
it('ne supprime pas le logo d’un partenaire qu’on déplace', function () {
    Storage::fake('public');
    $event = artworkEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'partners' => [
                ['name' => 'Alpha', 'logo_file' => UploadedFile::fake()->image('a.png')],
                ['name' => 'Beta', 'logo_file' => UploadedFile::fake()->image('b.png')],
            ],
        ])->assertSessionHasNoErrors();

    $avant = array_column($event->refresh()->settings['partners'], 'logo_url', 'name');

    // Beta passe devant : les deux lignes repostent l'URL qu'elles portent.
    $this->actingAs($staff, 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'partners' => [
                ['name' => 'Beta', 'logo_url' => $avant['Beta']],
                ['name' => 'Alpha', 'logo_url' => $avant['Alpha']],
            ],
        ])->assertSessionHasNoErrors();

    $apres = array_column($event->refresh()->settings['partners'], 'logo_url', 'name');

    expect($apres)->toBe(['Beta' => $avant['Beta'], 'Alpha' => $avant['Alpha']]);
    Storage::disk('public')->assertExists(storagePath($avant['Alpha']));
    Storage::disk('public')->assertExists(storagePath($avant['Beta']));
});

/** Le fichier d'un partenaire retiré ne reste pas servi publiquement. */
it('efface le logo d’un partenaire retiré de la liste', function () {
    Storage::fake('public');
    $event = artworkEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'partners' => [
                ['name' => 'Alpha', 'logo_file' => UploadedFile::fake()->image('a.png')],
                ['name' => 'Beta', 'logo_file' => UploadedFile::fake()->image('b.png')],
            ],
        ])->assertSessionHasNoErrors();

    $avant = array_column($event->refresh()->settings['partners'], 'logo_url', 'name');

    $this->actingAs($staff, 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'partners' => [['name' => 'Alpha', 'logo_url' => $avant['Alpha']]],
        ])->assertSessionHasNoErrors();

    Storage::disk('public')->assertExists(storagePath($avant['Alpha']));
    Storage::disk('public')->assertMissing(storagePath($avant['Beta']));
});

/** Remplacer le logo d'une ligne n'empile pas les fichiers. */
it('remplace le logo d’un partenaire sans garder l’ancien', function () {
    Storage::fake('public');
    $event = artworkEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'partners' => [['name' => 'Alpha', 'logo_file' => UploadedFile::fake()->image('a.png')]],
        ])->assertSessionHasNoErrors();

    $ancien = $event->refresh()->settings['partners'][0]['logo_url'];

    $this->actingAs($staff, 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'partners' => [[
                'name' => 'Alpha',
                'logo_url' => $ancien,
                'logo_file' => UploadedFile::fake()->image('a2.png'),
            ]],
        ])->assertSessionHasNoErrors();

    $nouveau = $event->refresh()->settings['partners'][0]['logo_url'];

    expect($nouveau)->not->toBe($ancien);
    Storage::disk('public')->assertExists(storagePath($nouveau));
    Storage::disk('public')->assertMissing(storagePath($ancien));
});

/**
 * ⚠️ Pas de SVG : il serait servi depuis notre domaine et peut porter du
 * script - un logo de partenaire est fourni par un tiers.
 */
it('refuse un logo de partenaire au format SVG', function () {
    Storage::fake('public');
    $event = artworkEdition();

    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.evenements.update', $event), editionBase($event) + [
            'partners' => [['name' => 'Alpha', 'logo_file' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml')]],
        ])
        ->assertSessionHasErrors('partners.0.logo_file');
});
