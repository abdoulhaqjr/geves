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
