<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('affiche la page profil du staff', function () {
    $staff = adminStaff();

    $this->actingAs($staff, 'web')
        ->get(route('admin.profile'))
        ->assertOk()
        ->assertSee('Mon profil')
        ->assertSee('Identité')
        ->assertSee($staff->email);
});

it('met à jour l’identité du staff', function () {
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->put(route('admin.profile.update'), [
        'first_name' => 'Kofi', 'last_name' => 'Mensah', 'grade' => 'Secrétaire général',
        'affiliation' => 'Université de Kara', 'phone' => '+228 90 00 00 00',
    ])->assertSessionHasNoErrors();

    $staff->refresh();
    expect($staff->first_name)->toBe('Kofi');
    expect($staff->last_name)->toBe('Mensah');
    expect($staff->affiliation)->toBe('Université de Kara');
});

it('change le mot de passe avec le mot de passe actuel correct', function () {
    $staff = User::factory()->create(['password' => Hash::make('secret-actuel')]);

    // Mauvais mot de passe actuel → erreur.
    $this->actingAs($staff, 'web')->put(route('admin.profile.password'), [
        'current_password' => 'faux', 'password' => 'nouveau-mot-de-passe', 'password_confirmation' => 'nouveau-mot-de-passe',
    ])->assertSessionHasErrors('current_password');

    // Bon mot de passe actuel → changé.
    $this->actingAs($staff, 'web')->put(route('admin.profile.password'), [
        'current_password' => 'secret-actuel', 'password' => 'nouveau-mot-de-passe', 'password_confirmation' => 'nouveau-mot-de-passe',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('nouveau-mot-de-passe', $staff->fresh()->password))->toBeTrue();
});
