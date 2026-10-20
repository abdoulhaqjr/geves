<?php

use App\Models\Event;
use App\Models\User;
use Database\Seeders\ColloqueEauTogoSeeder;
use Database\Seeders\RolesAndAdminSeeder;

/**
 * **Le colloque sur l'eau a son gestionnaire et son co-gestionnaire.**
 *
 * ⚠️ Ce qui ouvre l'accès, c'est la ligne dans `event_user`, pas le rôle
 * global : `User::capabilitiesOnEdition()` lit le pivot et rien d'autre. Un
 * rôle `gerant` posé sans affectation ne donnerait donc accès à RIEN, et une
 * affectation sans limite donnerait accès à TOUT. Les deux travers sont
 * vérifiés ici.
 */
beforeEach(function () {
    $this->seed(RolesAndAdminSeeder::class);
    $this->seed(ColloqueEauTogoSeeder::class);

    $this->colloque = Event::where('slug', 'premier-colloque-eau-togo-2026')->firstOrFail();
});

it('installe le gestionnaire et le co-gestionnaire sur ce colloque', function () {
    $attendu = [
        'seyf_laye@yahoo.com' => 'gerant',
        'ourobitasse2001@yahoo.fr' => 'co_gerant',
    ];

    foreach ($attendu as $email => $fonction) {
        $membre = User::whereRaw('LOWER(email) = ?', [$email])->first();

        expect($membre)->not->toBeNull("Compte absent : {$email}");
        expect($membre->rolesOnEdition($this->colloque))->toBe([$fonction]);
        expect($membre->canAccessEdition($this->colloque))->toBeTrue();
        expect($membre->canOnEdition('final_decision', $this->colloque))->toBeTrue();
        expect($membre->hasRole($fonction))->toBeTrue();
        // ⚠️ Niveau ORDINAIRE : la fonction vient de `event_user`, pas du
        // compte. Nommé « administrateur », ce compte ouvrirait toute la
        // plateforme - cf. `DroitsDeLAdministrateurTest`.
        expect($membre->access_level)->toBe(User::ACCESS_USER);
    }
});

it('ne leur ouvre AUCUN autre colloque', function () {
    // ⚠️ Le travers inverse : un gestionnaire nommé pour un colloque ne doit pas
    // voir les soumissions, les paiements ni les décisions d'un autre.
    $autre = Event::factory()->create();

    foreach (['seyf_laye@yahoo.com', 'ourobitasse2001@yahoo.fr'] as $email) {
        $membre = User::whereRaw('LOWER(email) = ?', [$email])->firstOrFail();

        expect($membre->canAccessEdition($autre))->toBeFalse();
        expect($membre->rolesOnEdition($autre))->toBe([]);
    }
});

/**
 * La migration qui redescend les comptes DÉJÀ importés. ⚠️ Chargée une seule
 * fois : un `require` déjà exécuté rend `true`, pas la migration (même piège
 * que `ClassementDesFichiersTest`).
 */
function migrationDeRetrogradation(): object
{
    static $migration = null;

    if ($migration === null) {
        $migration = require database_path('migrations/2026_10_20_000100_retirer_le_niveau_administrateur_aux_comptes_importes.php');
    }

    return $migration;
}

/**
 * ⚠️⚠️ **LE SEEDER NE RETOUCHE PAS UN COMPTE EXISTANT**, donc corriger le seeder
 * ne corrige RIEN en base : les deux comptes déjà importés gardaient le niveau
 * administrateur, c'est-à-dire - depuis le 2026-10-20 - les comptes, les rôles
 * et les réglages de toute la plateforme.
 */
it('redescend les comptes déjà importés au niveau ordinaire', function () {
    // On rejoue l'ancien état : le niveau que posait le seeder d'alors.
    foreach (['seyf_laye@yahoo.com', 'ourobitasse2001@yahoo.fr'] as $email) {
        User::whereRaw('LOWER(email) = ?', [$email])->firstOrFail()
            ->forceFill(['access_level' => User::ACCESS_COLLOQUE_ADMIN])->save();
    }

    migrationDeRetrogradation()->up();

    foreach (['seyf_laye@yahoo.com', 'ourobitasse2001@yahoo.fr'] as $email) {
        $membre = User::whereRaw('LOWER(email) = ?', [$email])->firstOrFail();

        expect($membre->access_level)->toBe(User::ACCESS_USER);
        // ⚠️ Leur pouvoir ne bouge pas : il vient du pivot, pas du niveau.
        expect($membre->rolesOnEdition($this->colloque))->not->toBe([]);
        expect($membre->canOnEdition('final_decision', $this->colloque))->toBeTrue();
    }

    // Rejouable : un second passage ne trouve plus rien et ne casse pas.
    migrationDeRetrogradation()->up();
    expect(User::whereRaw('LOWER(email) = ?', ['seyf_laye@yahoo.com'])->firstOrFail()->access_level)
        ->toBe(User::ACCESS_USER);
});

/**
 * ⚠️ **Elle ne défait pas une décision humaine.** Un compte promu depuis l'écran
 * des niveaux ne porte pas la marque de l'import : le redescendre reviendrait à
 * rétrograder quelqu'un dans le dos de celui qui l'a nommé.
 */
it('ne touche pas un administrateur nommé à la main', function () {
    $nomme = User::create([
        'first_name' => 'Ama', 'last_name' => 'Nommee',
        'email' => 'ama.nommee@example.tg', 'password' => 'pass12345',
        'access_level' => User::ACCESS_COLLOQUE_ADMIN,
    ]);

    migrationDeRetrogradation()->up();

    expect($nomme->fresh()->access_level)->toBe(User::ACCESS_COLLOQUE_ADMIN);
});

it('ne leur pose aucun mot de passe connu', function () {
    // ⚠️ Un seeder versionné ne doit jamais contenir le mot de passe d'une
    // personne réelle : le compte s'ouvre par réinitialisation.
    foreach (['seyf_laye@yahoo.com', 'ourobitasse2001@yahoo.fr'] as $email) {
        $membre = User::whereRaw('LOWER(email) = ?', [$email])->firstOrFail();

        expect($membre->must_reset_password)->toBeTrue();
    }
});

it('se rejoue sans dupliquer les fonctions', function () {
    // ⚠️ Le seeder est relancé à chaque import corrigé. Le pivot porte une
    // unicité (event_id, user_id, role) : un second passage non gardé lèverait
    // une violation de contrainte et ferait échouer tout l'import.
    $this->seed(ColloqueEauTogoSeeder::class);

    $membre = User::whereRaw('LOWER(email) = ?', ['seyf_laye@yahoo.com'])->firstOrFail();

    expect($membre->rolesOnEdition($this->colloque))->toBe(['gerant']);
    expect(User::whereRaw('LOWER(email) = ?', ['seyf_laye@yahoo.com'])->count())->toBe(1);
});
