<?php

use App\Models\User;

it('un membre du staff bascule vers son espace participant', function () {
    $staff = User::factory()->create(['access_level' => User::ACCESS_SUPER_ADMIN]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.space.participant'))
        ->assertRedirect(route('account.dashboard'));

    // Désormais authentifié aussi sur le guard « account » (identité unifiée).
    expect(auth('account')->check())->toBeTrue();
    expect(auth('account')->id())->toBe($staff->id);
});

it('affiche « Back-office » dans l’espace participant pour un staff', function () {
    $staff = User::factory()->create(['access_level' => User::ACCESS_SUPER_ADMIN]);
    $staff->markEmailAsVerified();

    $this->actingAs($staff, 'web')->post(route('admin.space.participant'));

    $this->actingAs($staff, 'web')
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Back-office');
});
