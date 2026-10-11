<?php

use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Support\Facades\Hash;

/**
 * **Une mise en ligne ne crée pas un super-administrateur « password ».**
 *
 * ⚠️ `RolesAndAdminSeeder` tourne AUSSI en production - c'est lui qui installe
 * les rôles et le compte à tous les pouvoirs. Il posait le mot de passe
 * « password », celui du jeu de démonstration : le premier `db:seed --force`
 * d'une mise en ligne ouvrait donc le back-office à qui connaît le projet. En
 * production, le mot de passe vient de la configuration, et son absence arrête
 * le déploiement.
 *
 * ⚠️ Les cas passent par `config()`, et non `putenv()`, parce que le seeder lit
 * `config()`. C'est tout le sujet : un `env()` appelé hors d'un fichier de
 * configuration rend `null` dès que la configuration est en cache, ce qui est
 * la règle en production. La première version lisait l'environnement et
 * refusait un mot de passe pourtant renseigné - sur un vrai déploiement.
 */
it('refuse de créer le super-administrateur sans mot de passe en production', function () {
    app()['env'] = 'production';
    config()->set('super-admin.password', '');

    expect(fn () => (new RolesAndAdminSeeder)->run())
        ->toThrow(RuntimeException::class, 'ADMIN_PASSWORD');

    expect(User::where('email', 'admin@univkara.tg')->exists())->toBeFalse();
});

it('dit où chercher quand la configuration en cache masque le mot de passe', function () {
    // ⚠️ Le message doit nommer le remède : sans lui, on relit son `.env` en
    // boucle, où la valeur est pourtant bien là.
    app()['env'] = 'production';
    config()->set('super-admin.password', '');

    expect(fn () => (new RolesAndAdminSeeder)->run())
        ->toThrow(RuntimeException::class, 'config:clear');
});

it('pose en production l’identifiant et le mot de passe de la configuration', function () {
    app()['env'] = 'production';
    config()->set('super-admin.email', 'dri@univkara.tg');
    config()->set('super-admin.password', 'un-mot-de-passe-long-et-unique');

    (new RolesAndAdminSeeder)->run();

    $admin = User::where('email', 'dri@univkara.tg')->first();

    expect($admin)->not->toBeNull();
    expect(Hash::check('un-mot-de-passe-long-et-unique', (string) $admin->password))->toBeTrue();
    expect($admin->hasRole('administrator'))->toBeTrue();
    expect(User::where('email', 'admin@univkara.tg')->exists())->toBeFalse();
});

it('ne retouche pas le mot de passe d’un administrateur déjà en place', function () {
    // ⚠️ Le seeder se rejoue à chaque déploiement. S'il réécrivait le mot de
    // passe, tout `db:seed` ramènerait celui du fichier .env - y compris après
    // que l'administrateur l'a changé depuis son profil.
    (new RolesAndAdminSeeder)->run();
    $admin = User::where('email', 'admin@univkara.tg')->firstOrFail();
    $admin->forceFill(['password' => Hash::make('choisi-par-la-personne')])->save();

    (new RolesAndAdminSeeder)->run();

    expect(Hash::check('choisi-par-la-personne', (string) $admin->fresh()->password))->toBeTrue();
});
