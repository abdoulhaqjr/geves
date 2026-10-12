<?php

use App\Models\Event;
use App\Models\User;
use App\Support\StaffRoles;

/**
 * Phase 3 - niveaux d'accès globaux : super admin - admin de colloque - utilisateur.
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

    $this->actingAs($admin, 'web')->get(route('admin.evenements.create'))->assertOk();

    $response = $this->actingAs($admin, 'web')->post(route('admin.evenements.store'), [
        'new_colloque' => 'EventSeries de l’admin',
        'title' => 'Événement 2026',
        'status' => 'open',
        'submission_open' => '1',
        'max_communications' => 3,
        'tracks' => "Axe A\nAxe B",
        'fees' => [['label' => 'Doctorant', 'amount' => '15000']],
    ]);
    $response->assertSessionHasNoErrors();

    $event = Event::firstWhere('title', 'Événement 2026');
    expect($event)->not->toBeNull();
    expect($admin->rolesOnEdition($event))->toContain('gerant');
    expect($admin->fresh()->canOnEdition('editions_write', $event))->toBeTrue();
});

it('un utilisateur simple ne peut pas créer de colloque', function () {
    $user = User::factory()->create(['access_level' => User::ACCESS_USER]);

    $this->actingAs($user, 'web')->get(route('admin.evenements.create'))->assertForbidden();
    $this->actingAs($user, 'web')->post(route('admin.evenements.store'), [
        'new_colloque' => 'X', 'title' => 'Y', 'status' => 'open',
    ])->assertForbidden();
});

it('propose gérant, co-gérant et les deux comités comme rôles de colloque', function () {
    $roles = StaffRoles::teamRoles();

    expect($roles)->toHaveKeys(['gerant', 'co_gerant', 'scientific', 'comite_organisation']);
    // Le gérant a les capacités pleines sur le colloque (dont l'écriture).
    expect(StaffRoles::permissionsFor('gerant'))->toContain('editions_write', 'payments', 'final_decision');
    // La relecture n'est PAS une fonction d'équipe (elle passe par l'affectation).
    expect($roles)->not->toHaveKey('reviewer');
    // Comité scientifique et comité d'organisation sont deux rôles distincts.
    expect(StaffRoles::label('scientific'))->toBe('Comité scientifique');
    expect(StaffRoles::label('comite_organisation'))->toBe('Comité d’organisation');
});

it('un co-gérant peut gérer le colloque', function () {
    $event = openEdition();
    $cogerant = User::factory()->create(['access_level' => User::ACCESS_USER]);
    $event->team()->attach($cogerant->id, ['role' => 'co_gerant']);

    expect($cogerant->canOnEdition('editions_write', $event))->toBeTrue();
    expect($cogerant->canOnEdition('payments', $event))->toBeTrue();
});
