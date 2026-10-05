<?php

use App\Models\EventSeries;
use App\Models\User;
use App\Notifications\StaffVerifyEmail;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Staff administrateur (rôles + permissions UKC seedés). */
function usersAdmin(): User
{
    (new RolesAndAdminSeeder)->run();

    return User::where('email', 'admin@univkara.tg')->firstOrFail();
}

it('protège la page Utilisateurs derrière la permission manage_colloques', function () {
    $u = User::factory()->create();
    $this->actingAs($u, 'web')->get(route('admin.users.index'))->assertForbidden();
});

it('affiche la liste, les rôles et la matrice d’accès', function () {
    $admin = usersAdmin();

    $this->actingAs($admin, 'web')->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Utilisateurs')
        ->assertSee('Comité scientifique')
        ->assertSee('Matrice d’accès', false)
        ->assertSee('Décision finale');
});

it('crée un utilisateur staff, l’associe à un rôle et envoie la vérification', function () {
    Notification::fake();
    $admin = usersAdmin();

    $this->actingAs($admin, 'web')->post(route('admin.users.store'), [
        'name' => 'Awa Kodjo', 'email' => 'awa.staff@univkara.tg',
        'roles' => ['reviewer'], 'access_level' => 'user', 'is_active' => '1', 'send_verification' => '1',
    ])->assertRedirect();

    $u = User::where('email', 'awa.staff@univkara.tg')->firstOrFail();
    expect($u->hasRole('reviewer'))->toBeTrue();
    expect($u->is_active)->toBeTrue();
    Notification::assertSentTo($u, StaffVerifyEmail::class);
});

it('met à jour les rôles d’un utilisateur depuis la page détail', function () {
    $admin = usersAdmin();
    $u = User::factory()->create();
    $u->assignRole('reception');

    $this->actingAs($admin, 'web')->put(route('admin.users.update', $u), [
        'name' => $u->name, 'email' => $u->email, 'access_level' => 'user', 'roles' => ['organizer', 'accounting'],
    ])->assertRedirect(route('admin.users.edit', $u));

    expect($u->fresh()->getRoleNames()->sort()->values()->all())->toBe(['accounting', 'organizer']);
});

it('renvoie le mail de vérification à un utilisateur non vérifié', function () {
    Notification::fake();
    $admin = usersAdmin();
    $u = User::factory()->create(['email_verified_at' => null]);

    $this->actingAs($admin, 'web')->post(route('admin.users.resend', $u))->assertRedirect();
    Notification::assertSentTo($u, StaffVerifyEmail::class);
});

it('vérifie l’adresse via le lien signé', function () {
    usersAdmin();
    $u = User::factory()->create(['email_verified_at' => null]);

    $url = URL::temporarySignedRoute('admin.verification.verify', now()->addHour(), [
        'id' => $u->id, 'hash' => sha1($u->email),
    ]);

    $this->get($url)->assertRedirect(route('account.login'));
    expect($u->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('empêche de supprimer le dernier administrateur et son propre compte', function () {
    $admin = usersAdmin();

    $this->actingAs($admin, 'web')->delete(route('admin.users.destroy', $admin))->assertRedirect();
    expect(User::whereKey($admin->id)->exists())->toBeTrue();
});

it('modifie la matrice d’accès : ajoute et retire des permissions par rôle', function () {
    $admin = usersAdmin();
    $reg = app(PermissionRegistrar::class);

    expect(Role::findByName('reviewer', 'web')->hasPermissionTo('payments'))->toBeFalse();

    $this->actingAs($admin, 'web')->put(route('admin.users.access'), [
        'matrix' => [
            'reviewer' => ['view_dashboard', 'submissions_list', 'evaluate', 'payments'], // + payments
            // 'organizer' non soumis → ses permissions granulaires sont retirées
        ],
    ])->assertRedirect(route('admin.users.index', ['tab' => 'access']));

    $reg->forgetCachedPermissions();
    // Ajout appliqué. Les rôles non-admin ne portent PAS `manage_colloques`
    // (gouvernés par leurs permissions granulaires).
    expect(Role::findByName('reviewer', 'web')->hasPermissionTo('payments'))->toBeTrue();
    expect(Role::findByName('reviewer', 'web')->hasPermissionTo('manage_colloques'))->toBeFalse();
    // Retrait appliqué pour organizer (non soumis).
    $org = Role::findByName('organizer', 'web');
    expect($org->hasPermissionTo('participants'))->toBeFalse();
    expect($org->hasPermissionTo('manage_colloques'))->toBeFalse();
    // L'administrateur garde toutes les permissions + la super-capacité.
    expect(Role::findByName('administrator', 'web')->hasPermissionTo('settings'))->toBeTrue();
    expect(Role::findByName('administrator', 'web')->hasPermissionTo('manage_colloques'))->toBeTrue();
});

it('applique l’étanchéité par événement (affectation → accès, modèle pur)', function () {
    usersAdmin(); // seed des rôles + permissions
    $event = openEdition();
    $other = EventSeries::create(['name' => 'EventSeries C', 'slug' => 'colloque-c'])
        ->events()->create(['title' => 'C 2027', 'year' => 2027, 'status' => 'open', 'slug' => 'c-2027']);

    // Comptable affecté à $event : paiements de SON événement uniquement.
    $accountant = User::factory()->create();
    $event->team()->attach($accountant->id, ['role' => 'accounting']);
    $this->actingAs($accountant, 'web')->get(route('admin.payments.index', ['event_id' => $event->id]))->assertOk();
    $this->actingAs($accountant, 'web')->get(route('admin.payments.index', ['event_id' => $other->id]))->assertForbidden();
    $this->actingAs($accountant, 'web')->get(route('admin.payments.index'))->assertOk(); // liste scopée à ses événements
    $this->actingAs($accountant, 'web')->get(route('admin.submissions.index'))->assertForbidden(); // hors de sa fonction
    $this->actingAs($accountant, 'web')->get(route('admin.settings'))->assertForbidden(); // zone globale
    $this->actingAs($accountant, 'web')->get(route('admin.users.index'))->assertForbidden();

    // Secrétariat affecté : soumissions + participants, mais pas paiements.
    $secretary = User::factory()->create();
    $event->team()->attach($secretary->id, ['role' => 'secretariat']);
    $this->actingAs($secretary, 'web')->get(route('admin.submissions.index', ['event_id' => $event->id]))->assertOk();
    $this->actingAs($secretary, 'web')->get(route('admin.payments.index', ['event_id' => $event->id]))->assertForbidden();

    // Staff sans affectation : shell OK, mais aucune zone par événement.
    $orphan = User::factory()->create();
    $this->actingAs($orphan, 'web')->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($orphan, 'web')->get(route('admin.payments.index'))->assertForbidden();

    // Administrateur : accès partout (super-capacité).
    $admin = usersAdmin();
    $this->actingAs($admin, 'web')->get(route('admin.settings'))->assertOk();
    $this->actingAs($admin, 'web')->get(route('admin.users.index'))->assertOk();
});
