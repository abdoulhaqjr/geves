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
        expect($membre->access_level)->toBe(User::ACCESS_COLLOQUE_ADMIN);
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
