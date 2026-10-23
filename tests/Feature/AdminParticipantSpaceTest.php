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

    $this->actingAs($staff, 'web')->get(route('account.dashboard'))
        ->assertOk()->assertSee('Back-office');
});

it('ouvre directement /compte pour un membre du staff déjà connecté', function () {
    $staff = User::factory()->create(['access_level' => User::ACCESS_SUPER_ADMIN]);
    $staff->markEmailAsVerified();

    $this->actingAs($staff, 'web')->get(route('account.dashboard'))
        ->assertOk()->assertSee('Back-office');

    expect(auth('account')->id())->toBe($staff->id);
});

it('relie directement le menu du back-office à /compte', function () {
    $staff = User::factory()->create(['access_level' => User::ACCESS_SUPER_ADMIN]);

    $this->actingAs($staff, 'web')->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('account.dashboard').'"', false)
        ->assertDontSee('action="'.route('admin.space.participant').'"', false);
});

it('garde la session du staff lors du passage direct du back-office à /compte', function () {
    $staff = User::factory()->create(['access_level' => User::ACCESS_SUPER_ADMIN]);
    $staff->markEmailAsVerified();

    $this->post(route('account.login.store'), [
        'email' => $staff->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

    $this->get(route('account.dashboard'))->assertOk()->assertSee('Back-office');

    expect(auth('web')->id())->toBe($staff->id)
        ->and(auth('account')->id())->toBe($staff->id);
});

it('ne donne pas accès à /compte à un visiteur anonyme', function () {
    $this->get(route('account.dashboard'))->assertRedirect(route('account.login'));
});

it('préserve le compte emprunté pendant une impersonation', function () {
    $staff = User::factory()->create(['access_level' => User::ACCESS_SUPER_ADMIN]);
    $account = User::factory()->create();

    $this->actingAs($staff, 'web')
        ->post(route('admin.users.impersonate', $account))
        ->assertRedirect(route('account.dashboard'));

    $this->get(route('account.dashboard'))->assertOk()->assertSee('Impersonation en cours');

    expect(auth('account')->id())->toBe($account->id);
});
