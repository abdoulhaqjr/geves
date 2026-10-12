<?php

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\User;
use App\Support\StaffRoles;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * **L'onglet Rôles compte ce qu'il annonce.**
 *
 * Il listait « N utilisateur(s) » à partir des rôles GLOBAUX (spatie) alors que
 * les fonctions d'équipe se confient PAR ÉVÉNEMENT (`event_user`). Sur la base de
 * dev, il affichait donc « 0 utilisateur(s) » pour un secrétariat et une
 * comptabilité pourtant à l'œuvre sur un colloque - le chiffre n'était pas faux,
 * il répondait à une question que personne ne pose devant cet écran.
 *
 * Il affiche désormais, dans cet ordre : les **personnes affectées** sur des
 * événements, le **rôle global** s'il y en a, puis les permissions.
 */

/** Le bloc de l'onglet Rôles, tel qu'il est rendu. */
function ongletRoles(): string
{
    $html = (string) test()->actingAs(adminStaff(), 'web')
        ->get(route('admin.users.index'))
        ->assertOk()
        ->getContent();

    $bloc = (string) Str::between($html, "x-show=\"tab==='roles'\"", '</div>'."\n".'        </div>');

    expect($bloc)->not->toBeEmpty();

    return $bloc;
}

/** La ligne de compteurs d'une fonction. */
function ligneRole(string $html, string $libelle): string
{
    $apres = Str::after($html, $libelle);

    return trim(strip_tags((string) Str::between($apres, '<div class="meta">', '</div>')));
}

/** Une personne affectée à une fonction sur un événement. */
function affecteSur(string $role, string $suffixe): User
{
    $serie = EventSeries::firstOrCreate(
        ['slug' => 'serie-onglet'],
        ['name' => 'Série onglet'],
    );
    /** @var Event $event */
    $event = $serie->events()->firstOrCreate(
        ['slug' => 'evenement-onglet'],
        ['title' => 'Événement onglet', 'year' => 2026, 'status' => 'open', 'submission_open' => false, 'settings' => []],
    );

    $u = User::create([
        'first_name' => 'Membre', 'last_name' => Str::upper($suffixe),
        'email' => 'onglet-'.$suffixe.'@example.tg', 'password' => 'pass12345',
    ]);
    $u->assignedEditions()->attach($event->id, ['role' => $role]);

    return $u;
}

it('compte les personnes affectées sur des événements, pas les rôles globaux', function () {
    affecteSur('secretariat', 'sec');

    $bloc = ongletRoles();

    // ⚠️ Le cas qui a motivé le changement : secrétariat affiché « 0 » alors que
    // quelqu'un l'exerce.
    expect(ligneRole($bloc, 'Secrétariat'))->toContain('1 personne sur 1 événement');

    // Une fonction que personne n'exerce le dit, au lieu d'un « 0 » ambigu.
    expect(ligneRole($bloc, 'Comité scientifique'))->toContain('aucune affectation');
});

/**
 * ⚠️ Le rôle global n'est mentionné QUE s'il existe : c'est ce qui rend visible
 * qu'« Administrateur colloque » est un rôle de plateforme, pas une fonction
 * d'événement.
 */
it('ne mentionne le rôle global que lorsqu’il y en a un', function () {
    $u = affecteSur('gerant', 'ger');
    Role::firstOrCreate(['name' => 'gerant', 'guard_name' => 'web']);
    $u->assignRole('gerant');

    $bloc = ongletRoles();

    expect(ligneRole($bloc, 'Gestionnaire du colloque'))
        ->toContain('1 personne sur 1 événement')
        ->toContain('1 rôle global');

    // Le secrétariat, lui, n'a pas de rôle global : la mention disparaît.
    expect(ligneRole($bloc, 'Secrétariat'))->not->toContain('rôle global');
});

it('accorde les pluriels des compteurs', function () {
    affecteSur('organizer', 'org1');
    affecteSur('organizer', 'org2');

    expect(ligneRole(ongletRoles(), 'Organisateur'))->toContain('2 personnes sur 1 événement');
});

/**
 * ⚠️ « Système » entrait en collision avec les TROIS rôles système du modèle
 * (super-admin, admin, utilisateur), qui n'ont rien à voir avec ce classement
 * des fonctions d'équipe et ne figurent même pas sur cet écran.
 */
it('nomme les familles « Organisation » et « Appui »', function () {
    $familles = collect(StaffRoles::ROLES)->pluck('category')->unique()->sort()->values()->all();

    expect($familles)->toBe(['Appui', 'Organisation']);

    $bloc = ongletRoles();
    expect($bloc)->toContain('Organisation')->toContain('Appui');
    expect($bloc)->not->toContain('>Système<');
});

/**
 * ⚠️ La pastille porte la FAMILLE : sa couleur doit suivre la famille, pas la
 * fonction. Elle reprenait celle de chaque fonction, si bien que Comptabilité
 * ressortait en vert comme les « Organisation » - une couleur qui suggérait une
 * information fausse.
 */
it('donne une seule couleur de pastille par famille', function () {
    $couleurs = [];
    foreach (array_keys(StaffRoles::ROLES) as $role) {
        $couleurs[StaffRoles::ROLES[$role]['category']][] = StaffRoles::categoryPill($role);
    }

    foreach ($couleurs as $famille => $liste) {
        expect(array_unique($liste))->toHaveCount(1, 'la famille '.$famille.' porte plusieurs couleurs');
    }

    // Et les deux familles ne portent pas la MÊME couleur, sinon elle ne sépare rien.
    expect(StaffRoles::categoryPill('gerant'))->not->toBe(StaffRoles::categoryPill('secretariat'));
});

/**
 * ⚠️ `editor` portait `manage_colloques` - l'accès complet par court-circuit de
 * `Gate::before` - sans figurer dans `StaffRoles`, donc ni dans l'onglet Rôles ni
 * dans la matrice d'accès. Un rôle à tous les pouvoirs que les écrans de
 * surveillance ne montraient pas.
 */
it('ne crée plus le rôle legacy « editor »', function () {
    $this->seed(RolesAndAdminSeeder::class);

    expect(Role::where('name', 'editor')->exists())->toBeFalse();

    // Et la super-capacité reste portée par le seul administrateur.
    $porteurs = Role::query()->whereHas('permissions', fn ($q) => $q->where('name', 'manage_colloques'))
        ->pluck('name')->all();

    expect($porteurs)->toBe(['administrator']);
});
