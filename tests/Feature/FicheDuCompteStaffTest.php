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

it('ne propose AUCUN rôle global', function () {
    // ⚠️⚠️ Le modèle du projet n'a que TROIS rôles globaux - les trois niveaux
    // d'accès. La fiche en offrait douze de plus, à l'échelle de la plateforme :
    // gestionnaire, comités, secrétariat, comptabilité, accueil… qui se confient
    // tous PAR ÉVÉNEMENT. Les cases ne faisaient rien d'autre que poser une
    // étiquette, et elles racontaient un système qui n'existe pas.
    $admin = ficheAdmin();

    $html = $this->actingAs($admin, 'web')->get(route('admin.users.edit', $admin))
        ->assertOk()
        ->assertDontSee('Rôles globaux')
        ->assertDontSee('matrice d’accès en découle')
        ->getContent();

    expect($html)->not->toContain('name="roles[]"');
});

it('ignore un rôle global qu’on lui posterait quand même', function () {
    // Retirer le champ ne suffit pas : une requête se forge.
    $admin = ficheAdmin();
    $u = User::factory()->create();

    $this->actingAs($admin, 'web')->put(route('admin.users.update', $u), [
        'name' => $u->name, 'email' => $u->email,
        'access_level' => User::ACCESS_USER, 'is_active' => '1', 'roles' => ['administrator'],
    ])->assertRedirect(route('admin.users.edit', $u));

    expect($u->fresh()->getRoleNames()->all())->toBe([]);
});

it('ne laisse le niveau d’accès ouvrir que trois capacités de plateforme', function () {
    // ⚠️ Ce qui rend la règle tenable : le Gate spatie ne garde que TROIS
    // capacités dans tout le back-office, et aucune FONCTION d'équipe ne les
    // porte - seul `administrator`, qui double le niveau super-administrateur.
    // Tout le reste passe par `edition.cap:` → `canOnEdition()`, donc par une
    // affectation dans `event_user`. Qu'on ajoute un `can:` ailleurs, et une
    // fonction d'équipe se mettrait à ouvrir une page de plateforme.
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

it('pose les actions du compte À CÔTÉ du formulaire, pas après lui', function () {
    // ⚠️ Vérification, mot de passe et suppression vivaient SOUS le bouton
    // « Enregistrer » : après la fin apparente de la page, donc lues comme une
    // suite du formulaire. Elles ne peuvent pas y entrer - chacune a le sien, et
    // deux formulaires ne s'imbriquent pas - alors elles passent en colonne.
    // (L'allure se juge au navigateur ; ce cas tient la STRUCTURE.)
    $admin = ficheAdmin();

    $html = $this->actingAs($admin, 'web')->get(route('admin.users.edit', $admin))
        ->assertOk()->getContent();

    expect($html)->toContain('class="uf-grid');

    $debutFormulaire = (int) mb_strpos($html, 'class="uf-main"');
    $finFormulaire = (int) mb_strpos($html, '</form>', $debutFormulaire);
    $panneau = (int) mb_strpos($html, 'class="uf-side"');
    $suppression = (int) mb_strpos($html, 'Supprimer le compte');

    // Le panneau est hors du formulaire, et la suppression est dedans.
    expect($panneau)->toBeGreaterThan($finFormulaire);
    expect($suppression)->toBeGreaterThan($panneau);
    // Et le formulaire garde son propre bouton d'enregistrement.
    expect(mb_substr($html, $debutFormulaire, $finFormulaire - $debutFormulaire))->toContain('Enregistrer');
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
        'access_level' => User::ACCESS_USER, 'is_active' => '1',
    ])->assertRedirect(route('admin.users.edit', $u));

    expect($u->fresh()->name)->toBe('Awa Kodjo');
    // ⚠️ Un rôle global DÉJÀ POSÉ en base n'est pas balayé au passage : la fiche
    // ne les distribue plus, elle ne se met pas à les retirer dans le dos de
    // qui corrige une adresse e-mail.
    expect($u->fresh()->getRoleNames()->all())->toBe(['reception']);
});
