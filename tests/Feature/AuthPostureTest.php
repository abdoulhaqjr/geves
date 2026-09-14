<?php

use App\Models\User;

it('un visiteur voit le formulaire de connexion', function () {
    $this->get(route('account.login'))->assertOk()->assertSee('Connexion');
});

it('un staff déjà connecté est redirigé hors de la page de connexion', function () {
    $staff = User::factory()->create(['access_level' => User::ACCESS_SUPER_ADMIN]);

    $this->actingAs($staff, 'web')->get(route('account.login'))
        ->assertRedirect(route('admin.dashboard'));
    $this->actingAs($staff, 'web')->get(route('account.register'))
        ->assertRedirect(route('admin.dashboard'));
});

it('un participant déjà connecté est redirigé hors de la page de connexion', function () {
    $account = User::factory()->create();
    $account->markEmailAsVerified();

    $this->actingAs($account, 'account')->get(route('account.login'))
        ->assertRedirect();
});
