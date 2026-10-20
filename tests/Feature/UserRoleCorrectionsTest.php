<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Support\StaffRoles;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

function delegatedUserAdmin(): User
{
    (new RolesAndAdminSeeder)->run();
    $admin = User::factory()->create(['access_level' => User::ACCESS_COLLOQUE_ADMIN]);
    $admin->givePermissionTo('users_roles');

    return $admin;
}

it('refuse la creation et la promotion super administrateur par un administrateur delegue', function () {
    $admin = delegatedUserAdmin();
    $target = User::factory()->create();

    $this->actingAs($admin, 'web')->post(route('admin.users.store'), [
        'name' => 'Nouveau Super', 'email' => 'nouveau.super@example.tg',
        'access_level' => User::ACCESS_SUPER_ADMIN, 'is_active' => '1',
    ])->assertSessionHasErrors('access_level');
    expect(User::where('email', 'nouveau.super@example.tg')->exists())->toBeFalse();

    $this->put(route('admin.users.update', $target), [
        'name' => $target->name, 'email' => $target->email,
        'access_level' => User::ACCESS_SUPER_ADMIN, 'is_active' => '1',
    ])->assertSessionHasErrors('access_level');
    expect($target->fresh()->access_level)->toBe(User::ACCESS_USER);

    $this->get(route('admin.users.create'))->assertOk()
        ->assertDontSee('name="access_level" value="super_admin"', false);
});

it('cache aussi les super administrateurs issus des anciens roles et permissions', function () {
    $admin = delegatedUserAdmin();
    $legacy = User::factory()->create(['access_level' => User::ACCESS_USER]);
    $legacy->assignRole('administrator');
    $direct = User::factory()->create(['access_level' => User::ACCESS_USER]);
    $direct->givePermissionTo('manage_colloques');

    expect($legacy->isColloqueSuperAdmin())->toBeTrue();
    expect($direct->isColloqueSuperAdmin())->toBeTrue();
    expect(User::query()->visiblesPar($admin)->whereKey($legacy->id)->exists())->toBeFalse();
    expect(User::query()->visiblesPar($admin)->whereKey($direct->id)->exists())->toBeFalse();

    $this->actingAs($admin, 'web')->get(route('admin.users.edit', $legacy))->assertNotFound();
    $this->post(route('admin.users.impersonate', $legacy))->assertNotFound();
    $this->post(route('admin.users.impersonate', $direct))->assertNotFound();
});

it('refuse la connexion et les sessions des comptes desactives', function () {
    $account = User::factory()->create([
        'email' => 'compte.inactif@example.tg', 'password' => Hash::make('secret1234'),
        'is_active' => false,
    ]);

    $this->post(route('account.login.store'), [
        'email' => $account->email, 'password' => 'secret1234',
    ])->assertSessionHasErrors('email');
    expect(Auth::guard('account')->check())->toBeFalse();

    $this->actingAs($account, 'account')->get(route('account.dashboard'))
        ->assertRedirect(route('account.login'));
    expect(Auth::guard('account')->check())->toBeFalse();
});

it('ferme aussi une session back office apres desactivation', function () {
    $admin = delegatedUserAdmin();
    $admin->forceFill(['is_active' => false])->save();

    $this->actingAs($admin, 'web')->get(route('admin.dashboard'))
        ->assertRedirect(route('account.login'));
    expect(Auth::guard('web')->check())->toBeFalse();
});

it('applique la matrice aux fonctions des evenements et preserve ses modifications au reseeding', function () {
    (new RolesAndAdminSeeder)->run();
    $super = User::where('email', 'admin@univkara.tg')->firstOrFail();
    $event = openEdition();
    $member = User::factory()->create();
    $member->assignedEditions()->attach($event->id, ['role' => 'secretariat']);
    expect($member->canOnEdition('participants', $event))->toBeTrue();

    $this->actingAs($super, 'web')->put(route('admin.users.access'), [
        'matrix' => ['secretariat' => ['view_dashboard']],
    ])->assertRedirect();

    expect($member->fresh()->canOnEdition('participants', $event))->toBeFalse();
    expect(StaffRoles::permissionsFor('secretariat'))->toBe(['view_dashboard']);
    (new RolesAndAdminSeeder)->run();
    expect($member->fresh()->canOnEdition('participants', $event))->toBeFalse();
});

it('reserve la modification de la matrice au super administrateur', function () {
    $admin = delegatedUserAdmin();
    $event = openEdition();
    $member = User::factory()->create();
    $member->assignedEditions()->attach($event->id, ['role' => 'secretariat']);

    $this->actingAs($admin, 'web')->put(route('admin.users.access'), [
        'matrix' => ['secretariat' => ['view_dashboard']],
    ])->assertForbidden();
    expect($member->canOnEdition('participants', $event))->toBeTrue();
});

it('compte les personnes selon leurs niveaux et affectations sans doublons', function () {
    (new RolesAndAdminSeeder)->run();
    $super = User::where('email', 'admin@univkara.tg')->firstOrFail();
    $admin = User::factory()->create(['access_level' => User::ACCESS_COLLOQUE_ADMIN]);
    $admin->givePermissionTo('users_roles');
    $reviewer = User::factory()->create();
    $organizer = User::factory()->create();
    $legacy = User::factory()->create();
    $legacy->assignRole('organizer');
    $first = Event::factory()->create();
    $second = Event::factory()->create();
    foreach ([$first, $second] as $event) {
        Participant::factory()->reviewer()->create(['event_id' => $event->id, 'user_id' => $reviewer->id]);
        $organizer->assignedEditions()->attach($event->id, ['role' => 'organizer']);
    }

    $html = $this->actingAs($super, 'web')->get(route('admin.users.index'))->assertOk()->getContent();
    preg_match('/<div class="lbl">Admins<\/div><div class="val">(\d+)<\/div>/', $html, $admins);
    preg_match('/<div class="lbl">Évaluateurs<\/div><div class="val">(\d+)<\/div>/', $html, $reviewers);
    preg_match('/<div class="lbl">Organisateurs<\/div><div class="val">(\d+)<\/div>/', $html, $organizers);

    expect((int) ($admins[1] ?? -1))->toBe(2)
        ->and((int) ($reviewers[1] ?? -1))->toBe(1)
        ->and((int) ($organizers[1] ?? -1))->toBe(1);
    expect($html)->toContain('1 personne sur 2 événements');

    $this->get(route('admin.users.index', ['role' => 'reviewer']))
        ->assertOk()->assertSee($reviewer->email)->assertDontSee($organizer->email);
    $this->get(route('admin.users.index', ['role' => 'organizer']))
        ->assertOk()->assertSee($organizer->email)->assertDontSee($legacy->email);

    $html = $this->actingAs($admin, 'web')->get(route('admin.users.index'))->assertOk()->getContent();
    preg_match('/<div class="lbl">Admins<\/div><div class="val">(\d+)<\/div>/', $html, $visibleAdmins);
    expect((int) ($visibleAdmins[1] ?? -1))->toBe(1);
});
