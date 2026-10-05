<?php

use App\Models\User;

// openEdition() / adminStaff() : helpers partagés (tests/Pest.php).

it('publie une annonce depuis l’admin (super-admin)', function () {
    $event = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.announcements.store', $event), [
            'title' => 'Changement de salle - Atelier 2',
            'text' => 'La session se tient en Salle C.',
            'urgent' => '1',
        ])
        ->assertRedirect();

    $list = $event->fresh()->settings['announcements'] ?? [];
    expect($list)->toHaveCount(1);
    expect($list[0]['title'])->toBe('Changement de salle - Atelier 2');
    expect($list[0]['urgent'])->toBeTrue();
    expect($list[0]['id'])->not->toBeEmpty();
});

it('retire une annonce par son identifiant', function () {
    $event = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.announcements.store', $event), ['title' => 'À retirer']);

    $id = $event->fresh()->settings['announcements'][0]['id'];

    $this->actingAs(adminStaff(), 'web')
        ->delete(route('admin.announcements.destroy', [$event, $id]))
        ->assertRedirect();

    expect($event->fresh()->settings['announcements'] ?? [])->toBeEmpty();
});

it('autorise le secrétariat à publier une annonce', function () {
    $event = openEdition();
    $staff = User::create(['first_name' => 'Sara', 'last_name' => 'Secrétaire', 'email' => 'sec@example.tg', 'password' => 'pass12345']);
    $staff->assignedEditions()->attach($event->id, ['role' => 'secretariat']);

    $this->actingAs($staff, 'web')
        ->post(route('admin.announcements.store', $event), ['title' => 'Accueil badges au hall'])
        ->assertRedirect();

    expect($event->fresh()->settings['announcements'])->toHaveCount(1);
});

it('refuse une fonction sans la capacité annonces', function () {
    $event = openEdition();
    $staff = User::create(['first_name' => 'Carl', 'last_name' => 'Comptable', 'email' => 'compta2@example.tg', 'password' => 'pass12345']);
    $staff->assignedEditions()->attach($event->id, ['role' => 'accounting']); // pas de capacité « announcements »

    $this->actingAs($staff, 'web')
        ->post(route('admin.announcements.store', $event), ['title' => 'Tentative'])
        ->assertStatus(403);

    expect($event->fresh()->settings['announcements'] ?? [])->toBeEmpty();
});

it('affiche la page d’annonces', function () {
    openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.announcements'))
        ->assertOk()
        ->assertSee('Nouvelle annonce');
});
