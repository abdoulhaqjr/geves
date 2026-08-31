<?php

use App\Models\Colloque;
use App\Models\Edition;
use App\Models\User;
use Illuminate\Http\Testing\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

function staffWithPermission(): User
{
    Permission::firstOrCreate(['name' => 'manage_colloques', 'guard_name' => 'web']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    $user->givePermissionTo('manage_colloques');

    return $user;
}

it('redirige les invités du back-office vers la connexion admin', function () {
    $this->get('/admin')->assertRedirect(route('account.login'));
});

it('refuse l’accès sans la permission manage_colloques', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get('/admin')->assertForbidden();
});

it('autorise le staff habilité à voir la liste des éditions', function () {
    $user = staffWithPermission();

    $this->actingAs($user, 'web')->get(route('admin.editions.index'))->assertOk();
});

it('liste les éditions de la plus récente à la plus ancienne (par année)', function () {
    $user = staffWithPermission();
    $colloque = Colloque::create(['name' => 'C', 'slug' => 'c']);
    $colloque->editions()->create(['title' => 'E 2024', 'status' => 'open', 'slug' => 'e-2024', 'year' => 2024]);
    $colloque->editions()->create(['title' => 'E 2026', 'status' => 'open', 'slug' => 'e-2026', 'year' => 2026]);
    $colloque->editions()->create(['title' => 'E 2025', 'status' => 'open', 'slug' => 'e-2025', 'year' => 2025]);

    $this->actingAs($user, 'web')->get(route('admin.editions.index'))
        ->assertOk()
        ->assertSeeInOrder(['E 2026', 'E 2025', 'E 2024']);
});

it('exporte une édition donnée en JSON', function () {
    $user = staffWithPermission();
    $colloque = Colloque::create(['name' => 'UK', 'slug' => 'uk']);
    $edition = $colloque->editions()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026', 'year' => 2026, 'settings' => ['tracks' => ['A']]]);

    $res = $this->actingAs($user, 'web')->get(route('admin.editions.export', $edition));
    $res->assertOk();
    $res->assertHeader('content-type', 'application/json');
    $json = json_decode($res->streamedContent(), true);
    expect($json['editions'])->toHaveCount(1);
    expect($json['editions'][0]['title'])->toBe('UK 2026');
    expect($json['editions'][0]['settings']['tracks'])->toBe(['A']);
});

it('importe une configuration dans l’édition courante (identité conservée)', function () {
    $user = staffWithPermission();
    $colloque = Colloque::create(['name' => 'UK', 'slug' => 'uk']);
    $edition = $colloque->editions()->create(['title' => 'Ancien titre', 'status' => 'draft', 'slug' => 'uk-2026', 'year' => 2026]);

    $payload = json_encode(['editions' => [
        ['title' => 'Config importée', 'slug' => 'autre-slug', 'year' => 2027, 'status' => 'open', 'settings' => ['tracks' => ['X', 'Y']]],
    ]]);
    $file = File::createWithContent('edition.json', $payload);

    $this->actingAs($user, 'web')->post(route('admin.editions.import', $edition), ['file' => $file])
        ->assertSessionHasNoErrors();

    $edition->refresh();
    expect($edition->title)->toBe('Config importée');
    expect($edition->settings['tracks'])->toBe(['X', 'Y']);
    expect($edition->status)->toBe('open');
    // Identité conservée : le slug de l'édition n'est PAS écrasé par le fichier.
    expect($edition->slug)->toBe('uk-2026');
});

it('crée une édition avec un nouveau colloque via le formulaire', function () {
    $user = staffWithPermission();

    $response = $this->actingAs($user, 'web')->post(route('admin.editions.store'), [
        'new_colloque' => 'Colloque test',
        'title' => 'Édition inaugurale 2026',
        'status' => 'open',
        'submission_open' => '1',
        'max_communications' => 3,
        'tracks' => "Axe A\nAxe B",
        'fees' => [['label' => 'Doctorant', 'amount' => '15000']],
    ]);
    $response->assertSessionHasNoErrors();

    $edition = Edition::firstWhere('title', 'Édition inaugurale 2026');
    expect($edition)->not->toBeNull();
    // Après enregistrement, on reste sur la fiche de l'édition (pas de retour à la liste).
    $response->assertRedirect(route('admin.editions.edit', $edition));
    expect($edition->slug)->toBe('edition-inaugurale-2026');
    expect($edition->settings['tracks'])->toBe(['Axe A', 'Axe B']);
    expect((float) $edition->settings['fees'][0]['amount'])->toBe(15000.0);
    expect(Colloque::where('name', 'Colloque test')->exists())->toBeTrue();
});

it('affiche un toast de confirmation après enregistrement (reste sur la fiche)', function () {
    $user = staffWithPermission();
    $colloque = Colloque::create(['name' => 'C', 'slug' => 'c']);
    $edition = $colloque->editions()->create(['title' => 'Ed', 'status' => 'draft', 'slug' => 'ed']);

    $this->actingAs($user, 'web')->followingRedirects()
        ->put(route('admin.editions.update', $edition), [
            'title' => 'Ed', 'status' => 'draft', 'colloque_id' => $edition->colloque_id, 'max_communications' => 3,
            'active_tab' => 'programme',
        ])
        ->assertOk()
        ->assertSee('ukc-toasts', false)   // conteneur de toasts présent
        ->assertSee("tab: 'programme'", false); // onglet actif restauré
});

it('modifie une édition existante', function () {
    $user = staffWithPermission();
    $colloque = Colloque::create(['name' => 'C', 'slug' => 'c']);
    $edition = $colloque->editions()->create(['title' => 'Ancien titre', 'status' => 'draft', 'slug' => 'ancien']);

    $this->actingAs($user, 'web')->put(route('admin.editions.update', $edition), [
        'title' => 'Nouveau titre',
        'status' => 'draft',
        'colloque_id' => $edition->colloque_id,
        'max_communications' => 3,
    ])->assertSessionHasNoErrors();

    expect($edition->fresh()->title)->toBe('Nouveau titre');
});

it('enregistre tous les champs du mockup (programme, langues, apparence, options…)', function () {
    $user = staffWithPermission();
    $colloque = Colloque::create(['name' => 'C', 'slug' => 'c']);
    $edition = $colloque->editions()->create([
        'title' => 'Édition', 'status' => 'open', 'slug' => 'ed',
        'settings' => ['practical' => ['venue' => 'Campus Sud']],
    ]);

    $this->actingAs($user, 'web')->put(route('admin.editions.update', $edition), [
        'title' => 'Édition', 'status' => 'open', 'colloque_id' => $edition->colloque_id, 'max_communications' => 3,
        'format' => 'Présentiel & hybride',
        'short_name' => 'CSIUK 2026',
        'languages' => ['Français', 'Anglais'],
        'main_language' => 'Français',
        'axes' => [['label' => 'Sciences'], ['label' => 'Santé'], ['label' => '']],
        'keyDates' => [['date' => '2026-07-15', 'label' => 'Limite de soumission']],
        'programme' => [['day' => 'Jour 1', 'time' => '09:00', 'type' => 'Conférence', 'title' => 'Ouverture', 'speaker' => 'X', 'room' => 'Amphi A']],
        'why' => [['title' => 'Visibilité', 'text' => 'Devant un large public.']],
        'infoCards' => [['title' => 'Lieu', 'text' => 'Kara, Togo']],
        'ref_prefix_mode' => 'global',
        'ref_prefix' => 'UKC-S-',
        'attestation_max' => 5,
        'coauthors_max' => 8,
        'gen_participation' => '1',
        'stats' => [['label' => 'Soumissions', 'value' => '142']],
        'stats_enabled' => '1',
        'color_primary' => '#223ea4',
        'opt_livret' => '1',
    ])->assertSessionHasNoErrors();

    $s = $edition->fresh()->settings;
    expect($s['format'])->toBe('Présentiel & hybride');
    expect($s['short_name'])->toBe('CSIUK 2026');
    expect($s['languages'])->toBe(['Français', 'Anglais']);
    expect($s['tracks'])->toBe(['Sciences', 'Santé']); // axes vides ignorés
    expect($s['key_dates'][0]['label'])->toBe('Limite de soumission');
    expect($s['programme'][0]['title'])->toBe('Ouverture');
    expect($s['why'][0]['title'])->toBe('Visibilité');
    expect($s['info_cards'][0]['title'])->toBe('Lieu');
    expect($s['ref_prefix'])->toBe('UKC-S-');
    expect($s['attestation_max'])->toBe(5);
    expect($s['gen_participation'])->toBeTrue();
    expect($s['gen_communication'])->toBeFalse(); // non coché
    expect($s['appearance']['stats'][0]['value'])->toBe('142');
    expect($s['appearance']['stats_enabled'])->toBeTrue();
    expect($s['appearance']['color_primary'])->toBe('#223ea4');
    expect($s['options']['livret'])->toBeTrue();
    expect($s['options']['badges'])->toBeFalse(); // non coché
    // Le practical existant est préservé (non écrasé).
    expect($s['practical']['venue'])->toBe('Campus Sud');
});

it('préserve submission_form et enregistre hero/contact au save', function () {
    $user = staffWithPermission();
    $colloque = Colloque::create(['name' => 'C', 'slug' => 'c']);
    $edition = $colloque->editions()->create([
        'title' => 'Édition', 'status' => 'open', 'slug' => 'ed',
        'settings' => ['submission_form' => ['fields' => ['title' => ['label' => 'Intitulé']], 'custom' => []]],
    ]);

    $this->actingAs($user, 'web')->put(route('admin.editions.update', $edition), [
        'title' => $edition->title,
        'status' => 'open',
        'colloque_id' => $edition->colloque_id,
        'max_communications' => 3,
        'contact_email' => 'colloque@univkara.tg',
        'hero_image' => 'https://example.tg/banniere.jpg',
    ])->assertSessionHasNoErrors();

    $settings = $edition->fresh()->settings;
    // La config du formulaire (gérée ailleurs) survit au save de l'édition.
    expect($settings['submission_form']['fields']['title']['label'])->toBe('Intitulé');
    expect($settings['contact']['email'])->toBe('colloque@univkara.tg');
    expect($settings['hero_image'])->toBe('https://example.tg/banniere.jpg');
});

it('affiche la page publique d’une édition publiée', function () {
    $colloque = Colloque::create(['name' => 'Colloque UK', 'slug' => 'uk']);
    $colloque->editions()->create([
        'title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026',
        'settings' => ['tracks' => ['Sciences'], 'fees' => []],
    ]);

    $this->get(route('public.colloque', 'uk-2026'))
        ->assertOk()
        ->assertSee('UK 2026')
        ->assertSee('Ateliers disciplinaires');
});

it('renvoie 404 pour une édition en brouillon', function () {
    $colloque = Colloque::create(['name' => 'Colloque UK', 'slug' => 'uk']);
    $colloque->editions()->create(['title' => 'Brouillon', 'status' => 'draft', 'slug' => 'brouillon']);

    $this->get(route('public.colloque', 'brouillon'))->assertNotFound();
});
