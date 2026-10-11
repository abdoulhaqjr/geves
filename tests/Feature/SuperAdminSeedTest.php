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
 * production, le mot de passe vient de l'environnement, et son absence arrête
 * le déploiement.
 */
it('refuse de créer le super-administrateur sans mot de passe en production', function () {
    app()['env'] = 'production';
    putenv('ADMIN_PASSWORD');

    expect(fn () => (new RolesAndAdminSeeder)->run())
        ->toThrow(RuntimeException::class, 'ADMIN_PASSWORD');

    expect(User::where('email', 'admin@univkara.tg')->exists())->toBeFalse();
});

it('pose en production l’identifiant et le mot de passe de l’environnement', function () {
    app()['env'] = 'production';
    putenv('ADMIN_EMAIL=dri@univkara.tg');
    putenv('ADMIN_PASSWORD=un-mot-de-passe-long-et-unique');

    (new RolesAndAdminSeeder)->run();

    $admin = User::where('email', 'dri@univkara.tg')->first();

    expect($admin)->not->toBeNull();
    expect(Hash::check('un-mot-de-passe-long-et-unique', (string) $admin->password))->toBeTrue();
    expect($admin->hasRole('administrator'))->toBeTrue();
    expect(User::where('email', 'admin@univkara.tg')->exists())->toBeFalse();

    putenv('ADMIN_EMAIL');
    putenv('ADMIN_PASSWORD');
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
