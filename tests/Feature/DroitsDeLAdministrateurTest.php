<?php

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;

/**
 * **L'administrateur a accès à TOUT, sauf nommer son semblable.**
 *
 * Décision du 2026-10-20. Jusque-là, deux niveaux se disaient distincts et
 * l'étaient surtout par accident : `colloque_admin` tombait en **403 sur les
 * trois groupes gardés par `can:`** (Réglages, Utilisateurs & rôles,
 * Témoignages) et ne voyait **que les événements où il était affecté**, alors
 * que le modèle du projet annonce « il crée les événements et y attache son
 * gestionnaire ».
 *
 * La ligne est désormais ailleurs, et il n'y en a qu'une :
 *
 * - **la portée** ({@see User::isColloqueAdmin()}) ouvre toute la plateforme -
 *   les 3 groupes `can:` ET les 16 groupes `edition.cap:` ;
 * - **la réserve** ({@see User::isColloqueSuperAdmin()}) ne garde plus que deux
 *   gestes : **nommer un super administrateur**
 *   ({@see User::niveauxAttribuablesPar()}) et **voir ces comptes**
 *   ({@see User::voitLeCompte()}, éprouvé par `FicheDuCompteStaffTest`).
 *
 * ⚠️ Les deux prédicats se ressemblent à une syllabe près et faisaient le même
 * travail hier : c'est la confusion la plus facile du module, d'où le dernier
 * cas du fichier, qui lit les sources.
 */

/** Un administrateur ORDINAIRE : le niveau, et rien d'autre - ni rôle spatie, ni permission. */
function administrateurSimple(): User
{
    (new RolesAndAdminSeeder)->run();

    // ⚠️ Aucun `givePermissionTo()` ici, et c'est tout l'objet du fichier : ce
    // qui lui ouvre les portes doit être son NIVEAU, pas une permission cochée
    // à la main dans la matrice d'accès.
    return User::factory()->create([
        'first_name' => 'Kossi', 'last_name' => 'Admin',
        'access_level' => User::ACCESS_COLLOQUE_ADMIN,
    ])->fresh();
}

function superAdministrateur(): User
{
    (new RolesAndAdminSeeder)->run();

    return User::factory()->create([
        'first_name' => 'Sylvie', 'last_name' => 'Super',
        'access_level' => User::ACCESS_SUPER_ADMIN,
    ])->fresh();
}

/** Un second événement, sans toucher aux fabriques partagées de `tests/Pest.php`. */
function autreEvenement(): Event
{
    $series = EventSeries::create(['name' => 'Séminaire UK', 'slug' => 'sem-uk']);

    return $series->events()->create([
        'title' => 'Séminaire 2026', 'year' => 2026, 'status' => 'open', 'slug' => 'sem-2026',
    ]);
}

// ---------------------------------------------------------------- LA PORTÉE --

/**
 * ⚠️ Les TROIS groupes, pas un échantillon : ce sont exactement les trois que
 * `routes/web.php` garde par `can:`, et c'est sur eux que les deux niveaux se
 * distinguaient. En éprouver deux laisserait le troisième se refermer.
 */
it('ouvre à l’administrateur les trois groupes gardés par can:', function () {
    $admin = administrateurSimple();

    foreach (['admin.settings', 'admin.users.index', 'admin.testimonials.index'] as $route) {
        $this->actingAs($admin, 'web')->get(route($route))
            ->assertOk("L'administrateur devrait ouvrir {$route}.");
    }
});

it('laisse dehors un utilisateur ordinaire', function () {
    $simple = User::factory()->create(['access_level' => User::ACCESS_USER]);

    foreach (['admin.settings', 'admin.users.index', 'admin.testimonials.index'] as $route) {
        $this->actingAs($simple, 'web')->get(route($route))
            ->assertForbidden("Un utilisateur ordinaire ne doit pas ouvrir {$route}.");
    }
});

/**
 * ⚠️ **Le cas qui porte « il a accès à TOUS les événements ».** `event_user` est
 * vide pour ce compte : avant, `accessibleEditionIds()` rendait donc `[]` et le
 * middleware refusait - l'administrateur ne voyait que ce qu'on lui avait
 * explicitement confié.
 */
it('donne à l’administrateur un événement auquel il n’est pas affecté', function () {
    $admin = administrateurSimple();
    $event = openEdition();

    expect($admin->rolesOnEdition($event))->toBe([]);
    expect($admin->canOnEdition('editions_write', $event))->toBeTrue();
    expect($admin->accessibleEditionIds())->toContain($event->id);

    $this->actingAs($admin, 'web')->get(route('admin.evenements.edit', $event))->assertOk();
});

/**
 * ⚠️ Contre-épreuve indispensable : ouvrir tout à l'administrateur ne doit pas
 * ouvrir l'événement du voisin à un gestionnaire.
 */
it('ne donne pas à un gestionnaire l’événement d’à côté', function () {
    $sien = openEdition();
    $autre = autreEvenement();

    $gerant = User::factory()->create(['access_level' => User::ACCESS_USER]);
    $gerant->assignedEditions()->attach($sien->id, ['role' => 'gerant']);
    $gerant = $gerant->fresh();

    expect($gerant->canOnEdition('editions_write', $sien))->toBeTrue();
    expect($gerant->canOnEdition('editions_write', $autre))->toBeFalse();

    $this->actingAs($gerant, 'web')->get(route('admin.evenements.edit', $autre))->assertForbidden();
});

// --------------------------------------------------------------- LA RÉSERVE --

it('ne propose pas « Super administrateur » à un administrateur', function () {
    $admin = administrateurSimple();

    $html = $this->actingAs($admin, 'web')->get(route('admin.users.create'))->assertOk()->getContent();

    expect($html)
        ->toContain('value="'.User::ACCESS_COLLOQUE_ADMIN.'"')
        ->not->toContain('value="'.User::ACCESS_SUPER_ADMIN.'"');
});

it('le propose à un super administrateur', function () {
    $super = superAdministrateur();

    $html = $this->actingAs($super, 'web')->get(route('admin.users.create'))->assertOk()->getContent();

    expect($html)->toContain('value="'.User::ACCESS_SUPER_ADMIN.'"');
});

/**
 * ⚠️⚠️ **RETIRER UNE CASE NE FERME AUCUNE PORTE.** Les deux cas précédents ne
 * disent rien de ce qui arrive quand le niveau est posté à la main - et c'est
 * le seul chemin qui compte : sans cette garde, un administrateur se promeut
 * en une requête et le niveau au-dessus du sien ne veut plus rien dire.
 */
it('refuse à un administrateur de créer un super administrateur', function () {
    $admin = administrateurSimple();

    $this->actingAs($admin, 'web')->post(route('admin.users.store'), [
        'name' => 'Promu Trop Vite',
        'email' => 'promu@example.tg',
        'access_level' => User::ACCESS_SUPER_ADMIN,
    ])->assertSessionHasErrors('access_level');

    expect(User::where('email', 'promu@example.tg')->exists())->toBeFalse();
});

it('refuse à un administrateur de promouvoir un compte existant', function () {
    $admin = administrateurSimple();
    $cible = User::factory()->create([
        'first_name' => 'Komi', 'last_name' => 'Ordinaire', 'access_level' => User::ACCESS_USER,
    ]);

    $this->actingAs($admin, 'web')->put(route('admin.users.update', $cible), [
        'name' => $cible->name,
        'email' => $cible->email,
        'access_level' => User::ACCESS_SUPER_ADMIN,
    ])->assertSessionHasErrors('access_level');

    expect($cible->fresh()->access_level)->toBe(User::ACCESS_USER);
});

/** Et il distribue bien tout le reste : « c'est lui qui donne les rôles ». */
it('laisse l’administrateur nommer un autre administrateur', function () {
    $admin = administrateurSimple();
    $cible = User::factory()->create([
        'first_name' => 'Ama', 'last_name' => 'Suivante', 'access_level' => User::ACCESS_USER,
    ]);

    $this->actingAs($admin, 'web')->put(route('admin.users.update', $cible), [
        'name' => $cible->name,
        'email' => $cible->email,
        'is_active' => '1',
        'access_level' => User::ACCESS_COLLOQUE_ADMIN,
    ])->assertSessionHasNoErrors();

    expect($cible->fresh()->access_level)->toBe(User::ACCESS_COLLOQUE_ADMIN);
});

it('laisse un super administrateur en nommer un autre', function () {
    $super = superAdministrateur();
    $cible = User::factory()->create([
        'first_name' => 'Yao', 'last_name' => 'Futur', 'access_level' => User::ACCESS_USER,
    ]);

    $this->actingAs($super, 'web')->put(route('admin.users.update', $cible), [
        'name' => $cible->name,
        'email' => $cible->email,
        'is_active' => '1',
        'access_level' => User::ACCESS_SUPER_ADMIN,
    ])->assertSessionHasNoErrors();

    expect($cible->fresh()->access_level)->toBe(User::ACCESS_SUPER_ADMIN);
});

// ------------------------------------------------------- LA GARDE DE SOURCE --

/**
 * ⚠️⚠️ **LE PRÉDICAT RÉSERVÉ NE SORT PLUS DU MODÈLE.** Tout le code écrivait
 * `isColloqueSuperAdmin()` quand il voulait dire « il a tous les droits » -
 * c'était vrai hier, et chaque occurrence oubliée referme aujourd'hui une porte
 * qu'on vient d'ouvrir. Le défaut ne se voit pas : une page répond 403 à un
 * seul niveau d'accès, sur un seul écran.
 *
 * La règle est donc topologique et se vérifie à la source : hors de `User`, on
 * écrit `isColloqueAdmin()`. Les emplois légitimes - la définition, la
 * délégation, et les trois gardes de la réserve - vivent tous dans le modèle.
 */
it('n’interroge le super administrateur que depuis le modèle', function () {
    $fautifs = [];

    $fichiers = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(app_path(), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($fichiers as $f) {
        if (! $f instanceof SplFileInfo || $f->getExtension() !== 'php') {
            continue;
        }

        // ⚠️ On lit le CODE, commentaires retirés : trois fichiers EXPLIQUENT la
        // règle en citant le nom de la méthode (`AppServiceProvider`,
        // `EventFormController`, `StaffRoles`). Documenter un piège ne doit pas
        // faire tomber la garde qui le tient - même leçon que `DegradeHerosTest`.
        $code = '';
        foreach (token_get_all((string) file_get_contents($f->getPathname())) as $t) {
            if (is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $code .= is_array($t) ? $t[1] : $t;
        }

        if (str_contains($code, 'isColloqueSuperAdmin') && $f->getFilename() !== 'User.php') {
            $fautifs[] = $f->getFilename();
        }
    }

    sort($fautifs);

    expect($fautifs)->toBe([]);
});
