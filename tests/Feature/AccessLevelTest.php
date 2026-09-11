<?php

use App\Models\Edition;
use App\Models\User;

/**
 * Phase 3 — niveaux d'accès globaux : super admin · admin de colloque · utilisateur.
 * L'admin de colloque peut créer un colloque (et en devient gérant) ; l'utilisateur
 * simple non.
 */
it('résout les trois niveaux d’accès', function () {
    $super = User::factory()->create(['access_level' => User::ACCESS_SUPER_ADMIN]);
    expect($super->isColloqueSuperAdmin())->toBeTrue();
    expect($super->canCreateColloque())->toBeTrue();

    $ca = User::factory()->create(['access_level' => User::ACCESS_COLLOQUE_ADMIN]);
    expect($ca->isColloqueSuperAdmin())->toBeFalse();
    expect($ca->isColloqueAdmin())->toBeTrue();
    expect($ca->canCreateColloque())->toBeTrue();

    $user = User::factory()->create(['access_level' => User::ACCESS_USER]);
    expect($user->isColloqueAdmin())->toBeFalse();
    expect($user->canCreateColloque())->toBeFalse();
});

it('un administrateur de colloque crée un colloque et en devient gérant', function () {
    $admin = User::factory()->create(['access_level' => User::ACCESS_COLLOQUE_ADMIN]);

    $this->actingAs($admin, 'web')->get(route('admin.editions.create'))->assertOk();

    $response = $this->actingAs($admin, 'web')->post(route('admin.editions.store'), [
        'new_colloque' => 'Colloque de l’admin',
        'title' => 'Édition 2026',
        'status' => 'open',
        'submission_open' => '1',
        'max_communications' => 3,
        'tracks' => "Axe A\nAxe B",
        'fees' => [['label' => 'Doctorant', 'amount' => '15000']],
    ]);
    $response->assertSessionHasNoErrors();

    $edition = Edition::firstWhere('title', 'Édition 2026');
    expect($edition)->not->toBeNull();
    expect($admin->rolesOnEdition($edition))->toContain('gerant');
    expect($admin->fresh()->canOnEdition('editions_write', $edition))->toBeTrue();
});

it('un utilisateur simple ne peut pas créer de colloque', function () {
    $user = User::factory()->create(['access_level' => User::ACCESS_USER]);

    $this->actingAs($user, 'web')->get(route('admin.editions.create'))->assertForbidden();
    $this->actingAs($user, 'web')->post(route('admin.editions.store'), [
        'new_colloque' => 'X', 'title' => 'Y', 'status' => 'open',
    ])->assertForbidden();
});

it('propose gérant, co-gérant et les deux comités comme rôles de colloque', function () {
    $roles = \App\Support\StaffRoles::teamRoles();

    expect($roles)->toHaveKeys(['gerant', 'co_gerant', 'scientific', 'comite_organisation']);
    // Le gérant a les capacités pleines sur le colloque (dont l'écriture).
    expect(\App\Support\StaffRoles::permissionsFor('gerant'))->toContain('editions_write', 'payments', 'final_decision');
    // La relecture n'est PAS une fonction d'équipe (elle passe par l'affectation).
    expect($roles)->not->toHaveKey('reviewer');
    // Comité scientifique et comité d'organisation sont deux rôles distincts.
    expect(\App\Support\StaffRoles::label('scientific'))->toBe('Comité scientifique');
    expect(\App\Support\StaffRoles::label('comite_organisation'))->toBe('Comité d’organisation');
});

it('un co-gérant peut gérer le colloque', function () {
    $edition = openEdition();
    $cogerant = User::factory()->create(['access_level' => User::ACCESS_USER]);
    $edition->team()->attach($cogerant->id, ['role' => 'co_gerant']);

    expect($cogerant->canOnEdition('editions_write', $edition))->toBeTrue();
    expect($cogerant->canOnEdition('payments', $edition))->toBeTrue();
});
