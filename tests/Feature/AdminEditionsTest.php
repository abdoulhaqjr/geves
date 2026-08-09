<?php

use App\Livewire\Admin\Editions\Form;
use App\Livewire\Admin\Editions\Index;
use App\Models\Colloque;
use App\Models\Edition;
use App\Models\User;
use Livewire\Livewire;
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

    Livewire::actingAs($user, 'web')
        ->test(Form::class)
        ->set('new_colloque', 'Colloque test')
        ->set('title', 'Édition inaugurale 2026')
        ->set('status', 'open')
        ->set('submission_open', true)
        ->set('tracks', "Axe A\nAxe B")
        ->set('fees', [['label' => 'Doctorant', 'amount' => '15000']])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.editions.index'));

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

    Livewire::actingAs($user, 'web')
        ->test(Form::class, ['edition' => $edition])
        ->assertSet('title', 'Ancien titre')
        ->set('title', 'Nouveau titre')
        ->call('save')
        ->assertHasNoErrors();

    expect($edition->fresh()->title)->toBe('Nouveau titre');
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
        ->assertSee('Axes thématiques');
});

it('renvoie 404 pour une édition en brouillon', function () {
    $colloque = Colloque::create(['name' => 'Colloque UK', 'slug' => 'uk']);
    $colloque->editions()->create(['title' => 'Brouillon', 'status' => 'draft', 'slug' => 'brouillon']);

    $this->get(route('public.colloque', 'brouillon'))->assertNotFound();
});
