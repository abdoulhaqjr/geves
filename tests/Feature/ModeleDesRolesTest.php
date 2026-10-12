<?php

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\User;
use App\Support\StaffRoles;

/**
 * **Le modèle des rôles, écrit une fois pour toutes.**
 *
 * Un utilisateur porte des rôles de DEUX natures, et ne pas les confondre est
 * tout l'enjeu :
 *
 * 1. **Trois niveaux d'accès SYSTÈME**, sur la plateforme entière, portés par
 *    `users.access_level` : **super-admin**, **admin** (il crée les événements
 *    et y associe gestionnaire et co-gestionnaire) et **utilisateur**. Il n'y en
 *    a pas d'autres, et il ne doit pas y en avoir d'autres.
 *
 * 2. **Tous les autres rôles sont RELATIFS À UN ÉVÉNEMENT.** Trois familles :
 *    - **l'équipe de l'événement** : stockée dans `event_user.role` (gestionnaire,
 *      co-gestionnaire, comités, secrétariat, comptabilité…), elle donne des
 *      capacités via `StaffRoles::permissionsFor()` ;
 *    - **le programme** : modérateur ou rapporteur d'une séance. ⚠️ Ces rôles ne
 *      sont PAS stockés comme rôles : ils se DÉDUISENT des affectations du
 *      programme ;
 *    - **l'évaluation** : relecteur, déduit des relectures confiées.
 *
 * ⚠️ Un même compte peut être « utilisateur » au niveau système et gestionnaire
 * d'un événement, relecteur sur un autre, et rien du tout sur un troisième.
 */
it('n’a que trois niveaux d’accès système', function () {
    expect(array_keys(User::accessLevels()))->toBe([
        User::ACCESS_SUPER_ADMIN,
        User::ACCESS_COLLOQUE_ADMIN,
        User::ACCESS_USER,
    ]);

    // ⚠️ La colonne ne doit pas se peupler d'autres valeurs en silence : c'est le
    // genre de dérive qui transforme un modèle clair en devinette.
    expect(User::accessLevels())->toHaveCount(3);
});

it('réserve la création d’un événement aux deux niveaux d’administration', function () {
    $faire = function (string $niveau): bool {
        $u = User::create([
            'first_name' => 'Niveau', 'last_name' => $niveau,
            'email' => 'niveau-'.$niveau.'@example.tg', 'password' => 'pass12345',
            'access_level' => $niveau,
        ]);

        return $u->canCreateColloque();
    };

    expect($faire(User::ACCESS_SUPER_ADMIN))->toBeTrue();
    expect($faire(User::ACCESS_COLLOQUE_ADMIN))->toBeTrue();
    expect($faire(User::ACCESS_USER))->toBeFalse();
});

/**
 * ⚠️ **Le niveau système et le portail `can:` doivent dire la même chose.**
 *
 * Deux gardes cohabitent dans les routes : `edition.cap:` (16 groupes), qui
 * passe par `canOnEdition()` donc par `isColloqueSuperAdmin()`, et `can:` (3
 * groupes : Réglages, Utilisateurs & rôles, Témoignages), qui passe par le Gate
 * spatie. Ce Gate n'interrogeait QUE le rôle spatie `administrator` et la
 * permission `manage_colloques` : un compte `access_level = super_admin` sans
 * rôle spatie ouvrait donc 16 groupes sur 19 et tombait en 403 sur les 3 autres.
 *
 * Le défaut ne se voyait pas, le seul super-admin de la base portant aussi le
 * rôle `administrator`. Il aurait surgi au premier compte créé par le seul
 * niveau d'accès.
 */
it('ouvre au super-admin système les pages gardées par le Gate', function () {
    $u = User::create([
        'first_name' => 'Supra', 'last_name' => 'Admin',
        'email' => 'supra.roles@example.tg', 'password' => 'pass12345',
        'access_level' => User::ACCESS_SUPER_ADMIN,
    ]);

    expect($u->isColloqueSuperAdmin())->toBeTrue();

    // Les trois capacités gardées par `can:` dans les routes.
    $refuses = [];
    foreach (['users_roles', 'settings', 'testimonials'] as $capacite) {
        if (! $u->can($capacite)) {
            $refuses[] = $capacite;
        }
    }

    expect($refuses)->toBe([]);
});

/**
 * Le pendant : le niveau « administrateur de colloque » n'ouvre PAS ces pages.
 * Il crée des événements et y nomme son équipe ; la gestion des comptes et les
 * réglages de la plateforme restent au super-admin (ou se délèguent en cochant
 * la permission dans la matrice d'accès).
 */
it('n’ouvre pas les réglages de la plateforme à un administrateur de colloque', function () {
    $u = User::create([
        'first_name' => 'Admin', 'last_name' => 'Colloque',
        'email' => 'admin.colloque.roles@example.tg', 'password' => 'pass12345',
        'access_level' => User::ACCESS_COLLOQUE_ADMIN,
    ]);

    expect($u->canCreateColloque())->toBeTrue();

    expect($u->can('users_roles'))->toBeFalse();
    expect($u->can('settings'))->toBeFalse();
});

/**
 * Les rôles d'équipe vivent dans `event_user`, donc par événement : le même
 * compte n'a aucune raison de porter la même fonction partout.
 */
it('attache les rôles d’équipe à UN événement, pas au compte', function () {
    $serie = EventSeries::create(['name' => 'Série rôles', 'slug' => 'serie-roles']);

    /** @var Event $a */
    $a = $serie->events()->create([
        'title' => 'Événement A', 'year' => 2026, 'status' => 'open', 'slug' => 'evenement-a',
        'submission_open' => false, 'settings' => [],
    ]);
    /** @var Event $b */
    $b = $serie->events()->create([
        'title' => 'Événement B', 'year' => 2026, 'status' => 'open', 'slug' => 'evenement-b',
        'submission_open' => false, 'settings' => [],
    ]);

    $u = User::create([
        'first_name' => 'Ama', 'last_name' => 'Gestion',
        'email' => 'ama.roles@example.tg', 'password' => 'pass12345',
    ]);
    $u->assignedEditions()->attach($a->id, ['role' => 'gerant']);

    expect($u->rolesOnEdition($a))->toBe(['gerant']);
    expect($u->rolesOnEdition($b))->toBe([]);

    expect($u->canAccessEdition($a))->toBeTrue();
    expect($u->canAccessEdition($b))->toBeFalse();

    // Les capacités suivent la fonction, et seulement sur CET événement.
    expect($u->canOnEdition('payments', $a))->toBeTrue();
    expect($u->canOnEdition('payments', $b))->toBeFalse();

    // ⚠️ Et le niveau système reste « utilisateur » : gérer un événement n'élève
    // personne au rang d'administrateur de la plateforme.
    //
    // ⚠️ `fresh()` n'est pas décoratif : le défaut `user` de `access_level` vit
    // dans la BASE, pas dans le modèle. Une instance tout juste créée porte donc
    // `null` jusqu'au rechargement - inoffensif ici (les comparaisons `===` avec
    // null sont fausses, donc on retombe du bon côté), mais c'est un piège pour
    // qui lirait l'attribut juste après un `create()`.
    expect($u->fresh()->access_level)->toBe(User::ACCESS_USER);
    expect($u->canCreateColloque())->toBeFalse();
});

/**
 * ⚠️ **Modérateur, rapporteur et relecteur ne sont pas des rôles stockés.** Ils
 * se déduisent du travail confié - une séance à animer, une séance à rapporter,
 * une relecture. Les chercher dans `event_user` serait une erreur.
 */
it('déduit les rôles du programme et de l’évaluation, sans les stocker', function () {
    $u = User::create([
        'first_name' => 'Kofi', 'last_name' => 'Deduit',
        'email' => 'kofi.roles@example.tg', 'password' => 'pass12345',
    ]);

    expect($u->isModeratorSomewhere())->toBeFalse();
    expect($u->isRapporteurSomewhere())->toBeFalse();
    expect($u->isReviewerSomewhere())->toBeFalse();

    $serie = EventSeries::create(['name' => 'Série prog', 'slug' => 'serie-prog']);
    /** @var Event $e */
    $e = $serie->events()->create([
        'title' => 'Événement prog', 'year' => 2026, 'status' => 'open', 'slug' => 'evenement-prog',
        'submission_open' => false, 'settings' => [],
    ]);

    // Animer une séance SUFFIT à être modérateur : aucune ligne dans event_user.
    $e->sessions()->create(['type' => 'plenary', 'label' => 'Plénière', 'moderator_email' => $u->email]);

    expect($u->fresh()->isModeratorSomewhere())->toBeTrue();
    expect($u->fresh()->rolesOnEdition($e))->toBe([]);
});

/**
 * Les fonctions d'équipe du projet, et leurs capacités, sont celles de
 * `StaffRoles` - une seule source.
 */
it('tient la liste des fonctions d’équipe dans StaffRoles', function () {
    $fonctions = array_keys(StaffRoles::ROLES);

    expect($fonctions)->toContain('gerant', 'co_gerant', 'reviewer', 'secretariat', 'accounting');

    // Chaque fonction déclare des capacités prises dans la matrice, sans inventer.
    $inconnues = [];
    foreach ($fonctions as $role) {
        foreach (StaffRoles::permissionsFor($role) as $cap) {
            if (! array_key_exists($cap, StaffRoles::PERMISSIONS)) {
                $inconnues[] = $role.' -> '.$cap;
            }
        }
    }

    expect($inconnues)->toBe([]);
});
