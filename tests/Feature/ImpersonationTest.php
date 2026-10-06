<?php

use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// staffWithPermission() -> AdminEditionsTest

it('permet au staff d’impersonifier un compte participant', function () {
    $staff = staffWithPermission();
    $account = User::factory()->create();
    $participant = Participant::factory()->create(['user_id' => $account->id]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.participants.impersonate', $participant))
        ->assertRedirect(route('account.dashboard'));

    expect(app('impersonate')->isImpersonating())->toBeTrue();
    expect(Auth::guard('account')->id())->toBe($account->id);
});

it('affiche la bannière d’impersonation dans l’espace participant', function () {
    $staff = staffWithPermission();
    $account = User::factory()->create();
    $participant = Participant::factory()->create(['user_id' => $account->id]);

    $this->actingAs($staff, 'web')->post(route('admin.participants.impersonate', $participant));

    $this->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Impersonation en cours')
        ->assertSee('Quitter l’impersonation', false);
});

it('quitte l’impersonation et rend la main au staff', function () {
    $staff = staffWithPermission();
    $account = User::factory()->create();
    $participant = Participant::factory()->create(['user_id' => $account->id]);

    $this->actingAs($staff, 'web')->post(route('admin.participants.impersonate', $participant));
    expect(app('impersonate')->isImpersonating())->toBeTrue();

    $this->post(route('impersonation.leave'))
        ->assertRedirect(route('admin.participants.index'));

    expect(app('impersonate')->isImpersonating())->toBeFalse();
    expect(Auth::guard('web')->id())->toBe($staff->id);
});

it('la déconnexion en impersonation QUITTE l’impersonation sans déconnecter l’admin', function () {
    $staff = staffWithPermission();
    $account = User::factory()->create();
    $participant = Participant::factory()->create(['user_id' => $account->id]);

    $this->actingAs($staff, 'web')->post(route('admin.participants.impersonate', $participant));
    expect(app('impersonate')->isImpersonating())->toBeTrue();

    // Le bouton de déconnexion de l'espace participant poste sur account.logout :
    // en impersonation, on quitte seulement, on ne vide pas la session de l'admin.
    $this->post(route('account.logout'))
        ->assertRedirect(route('admin.participants.index'));

    expect(app('impersonate')->isImpersonating())->toBeFalse();
    expect(Auth::guard('web')->id())->toBe($staff->id);
});

it('n’autorise que l’admin de colloque et le super-admin à impersonifier', function () {
    expect(User::factory()->create(['access_level' => User::ACCESS_SUPER_ADMIN])->canImpersonate())->toBeTrue()
        ->and(User::factory()->create(['access_level' => User::ACCESS_COLLOQUE_ADMIN])->canImpersonate())->toBeTrue()
        ->and(User::factory()->create(['access_level' => User::ACCESS_USER])->canImpersonate())->toBeFalse();
});

it('refuse l’impersonation d’un participant sans compte lié', function () {
    $staff = staffWithPermission();
    $participant = Participant::factory()->create(); // pas de account_id

    $this->actingAs($staff, 'web')
        ->post(route('admin.participants.impersonate', $participant))
        ->assertRedirect();

    expect(app('impersonate')->isImpersonating())->toBeFalse();
});

it('interdit l’impersonation sans la permission manage_colloques', function () {
    $user = User::factory()->create();
    $account = User::factory()->create();
    $participant = Participant::factory()->create(['user_id' => $account->id]);

    $this->actingAs($user, 'web')
        ->post(route('admin.participants.impersonate', $participant))
        ->assertForbidden();

    expect(app('impersonate')->isImpersonating())->toBeFalse();
});
