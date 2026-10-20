<?php

use App\Models\Event;
use App\Models\User;
use App\Support\StaffRoles;

/**
 * Phase 3 - niveaux d'accès globaux : super administrateur - administrateur -
 * utilisateur. L'administrateur crée les événements ; l'utilisateur simple non.
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

/**
 * ⚠️⚠️ **IL CRÉE L'ÉVÉNEMENT, IL N'EN DEVIENT PAS L'ÉQUIPE.** Ce cas exigeait
 * l'inverse : l'administrateur était attaché en `gerant` à chaque événement
 * qu'il ouvrait, faute de quoi il n'y aurait plus eu accès. Depuis qu'il a
 * accès à TOUS les événements (2026-10-20), cette affectation ne lui apporte
 * plus rien et salit la liste de l'équipe - il crée l'événement pour y attacher
 * SON gestionnaire. Le geste reste pour les autres : un gestionnaire qui crée
 * un événement en devient bien le gestionnaire, sinon il perdrait ce qu'il
 * vient d'ouvrir.
 */
it('un administrateur crée un événement sans s’inscrire dans son équipe', function () {
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
    expect($admin->rolesOnEdition($event))->toBe([]);
    expect($admin->fresh()->canOnEdition('editions_write', $event))->toBeTrue();
});

/** Le pendant : qui n'a pas le niveau garde la main sur ce qu'il vient de créer. */
it('un gestionnaire qui crée un événement en devient le gestionnaire', function () {
    $deja = openEdition();
    $gerant = User::factory()->create(['access_level' => User::ACCESS_USER]);
    $gerant->assignedEditions()->attach($deja->id, ['role' => 'gerant']);

    $this->actingAs($gerant->fresh(), 'web')->post(route('admin.evenements.store'), [
        'new_colloque' => 'Série du gestionnaire',
        'title' => 'Journées 2026',
        'status' => 'open',
        'submission_open' => '1',
        'max_communications' => 3,
        'tracks' => "Axe A\nAxe B",
        'fees' => [['label' => 'Doctorant', 'amount' => '15000']],
    ])->assertSessionHasNoErrors();

    $event = Event::firstWhere('title', 'Journées 2026');
    expect($gerant->fresh()->rolesOnEdition($event))->toContain('gerant');
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
