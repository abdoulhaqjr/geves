<?php

use App\Models\User;
use App\Support\StaffRoles;
use Database\Seeders\RolesAndAdminSeeder;

// adminStaff() / openEdition() -> Pest.php

/**
 * La fiche d'un compte du back-office : ce qu'elle montre, et ce qu'elle refuse
 * d'enregistrer.
 *
 * ⚠️ Elle proposait douze cases à cocher sous la promesse « la matrice d'accès
 * en découle », sans jamais dire ce que chaque fonction permet - ni montrer les
 * colloques auxquels le compte a réellement accès.
 */
function ficheAdmin(): User
{
    (new RolesAndAdminSeeder)->run();

    $admin = User::where('email', 'admin@univkara.tg')->firstOrFail();
    $admin->forceFill(['is_active' => true])->save();

    return $admin;
}

it('montre les colloques où le compte travaille, et à quel titre', function () {
    $admin = ficheAdmin();
    $event = openEdition();
    $u = User::factory()->create();
    $u->assignedEditions()->attach($event->id, ['role' => 'secretariat']);

    $this->actingAs($admin, 'web')->get(route('admin.users.edit', $u))
        ->assertOk()
        ->assertSee('Accès aux colloques')
        ->assertSee($event->title)
        ->assertSee('Secrétariat')
        // Une affectation se confie depuis l'équipe de l'événement : la fiche
        // y renvoie au lieu de laisser chercher.
        ->assertSee(route('admin.evenements.edit', ['event' => $event->id, 'tab' => 'team']));
});

it('dit qu’un compte sans affectation n’ouvre aucun colloque', function () {
    $admin = ficheAdmin();
    $u = User::factory()->create();

    $this->actingAs($admin, 'web')->get(route('admin.users.edit', $u))
        ->assertOk()
        ->assertSee('aucun colloque');
});

it('annonce la plateforme entière pour un super administrateur', function () {
    $admin = ficheAdmin();
    $u = User::factory()->create(['access_level' => User::ACCESS_SUPER_ADMIN]);

    $this->actingAs($admin, 'web')->get(route('admin.users.edit', $u))
        ->assertOk()
        ->assertSee('Tous les colloques.');
});

it('explique chaque niveau d’accès au lieu d’une liste déroulante', function () {
    // ⚠️ C'était un `<select>` rangé entre « Nouveau mot de passe » et « Notes
    // internes », sous une légende d'une ligne pour les trois choix confondus -
    // alors que c'est lui qui décide de ce que le compte ouvre partout.
    $admin = ficheAdmin();

    $reponse = $this->actingAs($admin, 'web')->get(route('admin.users.edit', $admin))->assertOk();

    foreach (User::accessLevelHints() as $indice) {
        $reponse->assertSee($indice);
    }
});

it('décrit chaque fonction et la range dans sa famille', function () {
    $admin = ficheAdmin();

    $reponse = $this->actingAs($admin, 'web')->get(route('admin.users.edit', $admin))->assertOk();

    $reponse->assertSee('Organisation')->assertSee('Appui');
    foreach (StaffRoles::ROLES as $meta) {
        $reponse->assertSee($meta['description']);
    }
});

it('ne promet plus que les rôles globaux ouvrent des pages', function () {
    // ⚠️ C'est le cœur de cet écran : onze des douze cases n'accordent RIEN.
    $admin = ficheAdmin();

    $this->actingAs($admin, 'web')->get(route('admin.users.edit', $admin))
        ->assertDontSee('matrice d’accès en découle')
        ->assertSee('sans lui ouvrir de page');
});

it('n’accorde une capacité globale qu’à l’administrateur', function () {
    // ⚠️ La règle que la phrase ci-dessus repose : le Gate spatie ne garde que
    // TROIS capacités dans tout le back-office, et seul « Administrateur
    // colloque » les porte. Tout le reste passe par `edition.cap:` →
    // `canOnEdition()`, donc par une affectation dans `event_user`. Qu'on ajoute
    // un `can:` ailleurs, et la promesse de la fiche change avec.
    $parGate = [];
    foreach (file(base_path('routes/web.php')) as $ligne) {
        if (preg_match_all('/can:([a-z_]+)/', $ligne, $m)) {
            $parGate = array_merge($parGate, $m[1]);
        }
    }
    $parGate = array_values(array_unique($parGate));
    sort($parGate);

    expect($parGate)->toBe(['settings', 'testimonials', 'users_roles']);

    $porteurs = [];
    foreach (StaffRoles::ROLES as $nom => $meta) {
        if (array_intersect($parGate, $meta['permissions']) !== []) {
            $porteurs[] = $nom;
        }
    }

    expect($porteurs)->toBe(['administrator']);
});

it('refuse de retirer le rôle au dernier administrateur', function () {
    // ⚠️ `destroy()` gardait la porte depuis toujours ; `update()` menait au même
    // endroit par la fenêtre, et ne vérifiait rien.
    $admin = ficheAdmin();
    $autre = adminStaff();

    $this->actingAs($autre, 'web')->put(route('admin.users.update', $admin), [
        'name' => $admin->name, 'email' => $admin->email,
        'access_level' => User::ACCESS_SUPER_ADMIN, 'roles' => [], 'is_active' => '1',
    ])->assertSessionHas('ukc_error');

    expect($admin->fresh()->hasRole('administrator'))->toBeTrue();
});

it('refuse de rétrograder le dernier super administrateur', function () {
    $admin = ficheAdmin();
    $autre = adminStaff();

    $this->actingAs($autre, 'web')->put(route('admin.users.update', $admin), [
        'name' => $admin->name, 'email' => $admin->email,
        'access_level' => User::ACCESS_USER, 'roles' => ['administrator'], 'is_active' => '1',
    ])->assertSessionHas('ukc_error');

    expect($admin->fresh()->access_level)->toBe(User::ACCESS_SUPER_ADMIN);
});

it('refuse qu’un administrateur se retire ses propres accès', function () {
    // Même esprit que « vous ne pouvez pas supprimer votre propre compte » :
    // un collègue le fait, en connaissance de cause.
    $admin = ficheAdmin();

    $this->actingAs($admin, 'web')->put(route('admin.users.update', $admin), [
        'name' => $admin->name, 'email' => $admin->email,
        'access_level' => User::ACCESS_SUPER_ADMIN, 'roles' => ['administrator'],
    ])->assertSessionHas('ukc_error');

    expect($admin->fresh()->is_active)->toBeTrue();
});

it('enregistre une modification ordinaire', function () {
    // Les gardes ne doivent mordre que sur ce qui ferme la porte.
    $admin = ficheAdmin();
    $u = User::factory()->create();
    $u->assignRole('reception');

    $this->actingAs($admin, 'web')->put(route('admin.users.update', $u), [
        'name' => 'Awa Kodjo', 'email' => $u->email,
        'access_level' => User::ACCESS_USER, 'roles' => ['secretariat'], 'is_active' => '1',
    ])->assertRedirect(route('admin.users.edit', $u));

    expect($u->fresh()->name)->toBe('Awa Kodjo');
    expect($u->fresh()->getRoleNames()->all())->toBe(['secretariat']);
});
