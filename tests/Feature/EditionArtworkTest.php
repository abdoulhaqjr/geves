<?php

use App\Models\Colloque;
use App\Models\Edition;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Images d'habillage d'une édition : bandeau (hero) et bannière officielle,
 * réglées dans l'onglet **Apparence** de la fiche d'édition.
 *
 * Les deux acceptent un téléversement **ou** une URL : une partie des éditions
 * pointe vers une affiche hébergée ailleurs.
 */

// adminStaff() -> tests/Pest.php

/** Le minimum que la fiche d'édition exige, pour ne poster que l'habillage. */
function editionBase(Edition $edition): array
{
    return [
        'title' => $edition->title,
        'status' => 'open',
        'colloque_id' => $edition->colloque_id,
        'max_communications' => 3,
    ];
}

function artworkEdition(): Edition
{
    $colloque = Colloque::create(['name' => 'UK', 'slug' => 'uk']);

    return $colloque->editions()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026']);
}

it('téléverse le bandeau et la bannière depuis l’onglet Apparence', function () {
    Storage::fake('public');
    $edition = artworkEdition();

    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.editions.update', $edition), editionBase($edition) + [
            'hero_image_file' => UploadedFile::fake()->image('hero.jpg', 1920, 800),
            'banner_image_file' => UploadedFile::fake()->image('banniere.jpg', 1680, 720),
        ])
        ->assertSessionHasNoErrors();

    $edition->refresh();

    expect($edition->settings['hero_image'])->toContain('editions/');
    expect($edition->banner_url)->toContain('editions/');

    foreach ([$edition->settings['hero_image'], $edition->banner_url] as $url) {
        Storage::disk('public')->assertExists(storagePath($url));
    }
});

it('remplace le fichier précédent au lieu de l’empiler', function () {
    Storage::fake('public');
    $edition = artworkEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->put(route('admin.editions.update', $edition), editionBase($edition) + [
        'banner_image_file' => UploadedFile::fake()->image('premiere.jpg'),
    ]);
    $first = $edition->refresh()->banner_url;

    $this->actingAs($staff, 'web')->put(route('admin.editions.update', $edition), editionBase($edition) + [
        'banner_image' => $first,   // le champ URL renvoie la valeur affichée
        'banner_image_file' => UploadedFile::fake()->image('seconde.jpg'),
    ]);
    $second = $edition->refresh()->banner_url;

    // ⚠️ Une bannière n'a pas d'historique : l'ancienne ne doit plus être servie.
    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing(storagePath($first));
    Storage::disk('public')->assertExists(storagePath($second));
});

it('accepte une URL externe et ne cherche pas à la supprimer', function () {
    Storage::fake('public');
    $edition = artworkEdition();
    $staff = adminStaff();

    $externe = 'https://cdn.example.org/affiche.jpg';

    $this->actingAs($staff, 'web')->put(route('admin.editions.update', $edition), editionBase($edition) + [
        'banner_image' => $externe,
    ])->assertSessionHasNoErrors();

    expect($edition->refresh()->banner_url)->toBe($externe);

    // Ré-enregistrer sans toucher au champ ne doit rien changer.
    $this->actingAs($staff, 'web')->put(route('admin.editions.update', $edition), editionBase($edition) + [
        'banner_image' => $externe,
    ]);

    expect($edition->refresh()->banner_url)->toBe($externe);
});

it('supprime l’image quand l’admin le demande', function () {
    Storage::fake('public');
    $edition = artworkEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->put(route('admin.editions.update', $edition), editionBase($edition) + [
        'hero_image_file' => UploadedFile::fake()->image('hero.jpg'),
    ]);
    $hero = $edition->refresh()->settings['hero_image'];

    $this->actingAs($staff, 'web')->put(route('admin.editions.update', $edition), editionBase($edition) + [
        'hero_image_remove' => '1',
    ]);

    $edition->refresh();
    expect($edition->settings['hero_image'])->toBeNull();
    Storage::disk('public')->assertMissing(storagePath($hero));
});

it('garde l’habillage quand on enregistre un autre onglet', function () {
    Storage::fake('public');
    $edition = artworkEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->put(route('admin.editions.update', $edition), editionBase($edition) + [
        'banner_image_file' => UploadedFile::fake()->image('banniere.jpg'),
    ]);
    $banner = $edition->refresh()->banner_url;

    // ⚠️ La fiche poste tous les onglets d'un coup : un enregistrement qui ne
    // renvoie pas le champ image ne doit pas effacer la bannière.
    $this->actingAs($staff, 'web')->put(route('admin.editions.update', $edition), editionBase($edition) + [
        'subtitle' => 'Nouveau sous-titre',
    ]);

    expect($edition->refresh()->banner_url)->toBe($banner);
    Storage::disk('public')->assertExists(storagePath($banner));
});

it('refuse un fichier qui n’est pas une image', function () {
    Storage::fake('public');
    $edition = artworkEdition();

    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.editions.update', $edition), editionBase($edition) + [
            'banner_image_file' => UploadedFile::fake()->create('programme.pdf', 60, 'application/pdf'),
        ])
        ->assertSessionHasErrors('banner_image_file');

    expect($edition->refresh()->banner_url)->toBeNull();
});

it('affiche la bannière pleine largeur sur la page publique', function () {
    Storage::fake('public');
    $edition = artworkEdition();

    $this->actingAs(adminStaff(), 'web')->put(route('admin.editions.update', $edition), editionBase($edition) + [
        'banner_image_file' => UploadedFile::fake()->image('banniere.jpg', 1680, 720),
        'banner_enabled' => '1',
    ]);

    auth('web')->logout();

    $this->get(route('public.colloque', $edition->refresh()->slug))
        ->assertOk()
        ->assertSee($edition->banner_url, false);
});

/** Chemin disque d'une URL produite par le disque public. */
function storagePath(string $url): string
{
    return trim(str_replace('/storage/', '', (string) parse_url($url, PHP_URL_PATH)), '/');
}
