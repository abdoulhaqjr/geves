<?php

use App\Models\EventSeries;
use App\Models\Event;
use App\Models\User;

// adminStaff() et openEdition() sont définis dans tests/Pest.php.

function secondEdition(): Event
{
    $series = EventSeries::create(['name' => 'EventSeries B', 'slug' => 'colloque-b']);

    return $series->events()->create([
        'title' => 'B 2027', 'year' => 2027, 'status' => 'open', 'slug' => 'b-2027',
    ]);
}

it('dérive les capacités du membre de sa fonction, et seulement sur son événement', function () {
    $event = openEdition();
    $other = secondEdition();
    $user = User::factory()->create();
    $event->team()->attach($user->id, ['role' => 'accounting']); // Comptabilité → payments

    expect($user->canOnEdition('payments', $event))->toBeTrue()
        ->and($user->canOnEdition('emails', $event))->toBeFalse()   // pas dans la matrice comptabilité
        ->and($user->canOnEdition('payments', $other))->toBeFalse()   // pas affecté à l'autre événement
        ->and($user->canAccessEdition($event))->toBeTrue()
        ->and($user->canAccessEdition($other))->toBeFalse();
});

it('cumule les capacités de plusieurs fonctions sur une même événement', function () {
    $event = openEdition();
    $user = User::factory()->create();
    $event->team()->attach($user->id, ['role' => 'accounting']);   // payments
    $event->team()->attach($user->id, ['role' => 'secretariat']);  // participants, documents, emails…

    expect($user->rolesOnEdition($event))->toContain('accounting')->toContain('secretariat')
        ->and($user->canOnEdition('payments', $event))->toBeTrue()
        ->and($user->canOnEdition('emails', $event))->toBeTrue();
});

it('donne au super-administrateur l’accès à toutes les événements', function () {
    $admin = adminStaff(); // porte manage_colloques
    $event = openEdition();

    expect($admin->isColloqueSuperAdmin())->toBeTrue()
        ->and($admin->canAccessEdition($event))->toBeTrue()
        ->and($admin->canOnEdition('payments', $event))->toBeTrue();
});

it('affecte un membre à l’équipe de l’événement', function () {
    $staff = adminStaff();
    $event = openEdition();
    $user = User::factory()->create();

    $this->actingAs($staff, 'web')
        ->post(route('admin.evenements.team.attach', $event), ['user_id' => $user->id, 'role' => 'secretariat'])
        ->assertRedirect();

    expect($event->team()->where('users.id', $user->id)->wherePivot('role', 'secretariat')->exists())->toBeTrue();
});

it('refuse une fonction non affectable à une équipe (administrator)', function () {
    $staff = adminStaff();
    $event = openEdition();
    $user = User::factory()->create();

    $this->actingAs($staff, 'web')
        ->post(route('admin.evenements.team.attach', $event), ['user_id' => $user->id, 'role' => 'administrator'])
        ->assertStatus(422);
});

it('retire une fonction d’un membre de l’événement', function () {
    $staff = adminStaff();
    $event = openEdition();
    $user = User::factory()->create();
    $event->team()->attach($user->id, ['role' => 'accounting']);

    $this->actingAs($staff, 'web')
        ->delete(route('admin.evenements.team.detach', $event), ['user_id' => $user->id, 'role' => 'accounting'])
        ->assertRedirect();

    expect($event->team()->where('users.id', $user->id)->exists())->toBeFalse();
});

it('affiche l’onglet Équipe sur la fiche d’événement', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')
        ->get(route('admin.evenements.edit', $event))
        ->assertOk()
        ->assertSee('Équipe du colloque');
});
