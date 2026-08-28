<?php

use App\Models\Colloque;
use App\Models\Edition;
use App\Models\User;
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
    $this->get('/admin')->assertRedirect(route('admin.login'));
});

it('refuse l’accès sans la permission manage_colloques', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get('/admin')->assertForbidden();
});

it('autorise le staff habilité à voir la liste des éditions', function () {
    $user = staffWithPermission();

    $this->actingAs($user, 'web')->get(route('admin.editions.index'))->assertOk();
});

it('crée une édition avec un nouveau colloque via le formulaire', function () {
    $user = staffWithPermission();

    $this->actingAs($user, 'web')->post(route('admin.editions.store'), [
        'new_colloque' => 'Colloque test',
        'title' => 'Édition inaugurale 2026',
        'status' => 'open',
        'submission_open' => '1',
        'max_communications' => 3,
        'tracks' => "Axe A\nAxe B",
        'fees' => [['label' => 'Doctorant', 'amount' => '15000']],
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.editions.index'));

    $edition = Edition::firstWhere('title', 'Édition inaugurale 2026');
    expect($edition)->not->toBeNull();
    expect($edition->slug)->toBe('edition-inaugurale-2026');
    expect($edition->settings['tracks'])->toBe(['Axe A', 'Axe B']);
    expect((float) $edition->settings['fees'][0]['amount'])->toBe(15000.0);
    expect(Colloque::where('name', 'Colloque test')->exists())->toBeTrue();
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
