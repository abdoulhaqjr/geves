<?php

use App\Models\Colloque;
use App\Models\Edition;
use App\Models\User;

// adminStaff() et openEdition() sont définis dans tests/Pest.php.

function secondEdition(): Edition
{
    $colloque = Colloque::create(['name' => 'Colloque B', 'slug' => 'colloque-b']);

    return $colloque->editions()->create([
        'title' => 'B 2027', 'year' => 2027, 'status' => 'open', 'slug' => 'b-2027',
    ]);
}

it('dérive les capacités du membre de sa fonction, et seulement sur son édition', function () {
    $edition = openEdition();
    $other = secondEdition();
    $user = User::factory()->create();
    $edition->team()->attach($user->id, ['role' => 'accounting']); // Comptabilité → payments

    expect($user->canOnEdition('payments', $edition))->toBeTrue()
        ->and($user->canOnEdition('emails', $edition))->toBeFalse()   // pas dans la matrice comptabilité
        ->and($user->canOnEdition('payments', $other))->toBeFalse()   // pas affecté à l'autre édition
        ->and($user->canAccessEdition($edition))->toBeTrue()
        ->and($user->canAccessEdition($other))->toBeFalse();
});

it('cumule les capacités de plusieurs fonctions sur une même édition', function () {
    $edition = openEdition();
    $user = User::factory()->create();
    $edition->team()->attach($user->id, ['role' => 'accounting']);   // payments
    $edition->team()->attach($user->id, ['role' => 'secretariat']);  // participants, documents, emails…

    expect($user->rolesOnEdition($edition))->toContain('accounting')->toContain('secretariat')
        ->and($user->canOnEdition('payments', $edition))->toBeTrue()
        ->and($user->canOnEdition('emails', $edition))->toBeTrue();
});

it('donne au super-administrateur l’accès à toutes les éditions', function () {
    $admin = adminStaff(); // porte manage_colloques
    $edition = openEdition();

    expect($admin->isColloqueSuperAdmin())->toBeTrue()
        ->and($admin->canAccessEdition($edition))->toBeTrue()
        ->and($admin->canOnEdition('payments', $edition))->toBeTrue();
});

it('affecte un membre à l’équipe de l’édition', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $user = User::factory()->create();

    $this->actingAs($staff, 'web')
        ->post(route('admin.editions.team.attach', $edition), ['user_id' => $user->id, 'role' => 'secretariat'])
        ->assertRedirect();

    expect($edition->team()->where('users.id', $user->id)->wherePivot('role', 'secretariat')->exists())->toBeTrue();
});

it('refuse une fonction non affectable à une équipe (administrator)', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $user = User::factory()->create();

    $this->actingAs($staff, 'web')
        ->post(route('admin.editions.team.attach', $edition), ['user_id' => $user->id, 'role' => 'administrator'])
        ->assertStatus(422);
});

it('retire une fonction d’un membre de l’édition', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $user = User::factory()->create();
    $edition->team()->attach($user->id, ['role' => 'accounting']);

    $this->actingAs($staff, 'web')
        ->delete(route('admin.editions.team.detach', $edition), ['user_id' => $user->id, 'role' => 'accounting'])
        ->assertRedirect();

    expect($edition->team()->where('users.id', $user->id)->exists())->toBeFalse();
});

it('affiche l’onglet Équipe sur la fiche d’édition', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')
        ->get(route('admin.editions.edit', $edition))
        ->assertOk()
        ->assertSee('Équipe du colloque');
});
