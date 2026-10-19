<?php

use App\AI\AiManager;
use App\AI\Contracts\AiProvider;
use App\Models\Event;
use App\Models\User;
use App\Support\UserDirectory;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * **Marc répond sur les comptes - à qui peut déjà les voir.**
 *
 * Le copilote d'administration est joignable par TOUT le staff ayant « Tableau de
 * bord » sur l'événement : relecteur, secrétariat, comptabilité. La page
 * `admin/utilisateurs`, elle, est gardée par la permission `users_roles`, que
 * seul le rôle Administrateur porte par défaut.
 *
 * ⚠️ **L'assistant ne doit ouvrir aucune porte que l'interface tient fermée.**
 * D'où une seule et même condition des deux côtés : `users_roles`. Un
 * administrateur de colloque sans cette permission est en 403 sur la page ; il
 * n'obtient rien de plus en passant par Marc.
 *
 * ⚠️ Et la garde vit DANS `UserDirectory`, pas au point d'appel : `AskAdmin`
 * construit son prompt à chaque message, et une garde posée chez l'appelant
 * finit par être oubliée lors d'un déplacement de bloc.
 */

/**
 * Capture le prompt réellement transmis au modèle.
 *
 * ⚠️ Le fournisseur factice est monté ICI plutôt que par le `bindFakeAi()` de
 * `GenerateReportTest` : une fonction définie dans un autre fichier de cas
 * n'existe pas quand on exécute celui-ci seul, et le cas tombe sur un
 * « undefined function » qui ne dit rien du sujet testé.
 */
function capteurDePrompt(): object
{
    $capture = new stdClass;
    $capture->prompt = '';

    $provider = new class($capture) implements AiProvider
    {
        public function __construct(private object $c) {}

        public function key(): string
        {
            return 'fake';
        }

        public function label(): string
        {
            return 'Fake';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function supportsPdf(): bool
        {
            return true;
        }

        public function extract(string $instruction, array $files = [], ?string $tier = null): array
        {
            $this->c->prompt = $instruction;

            return ['answer' => 'ok'];
        }
    };

    $manager = Mockery::mock(AiManager::class);
    $manager->shouldReceive('resolve')->andReturn($provider);
    $manager->shouldReceive('extract')->andReturnUsing(
        fn (string $instruction, array $files = [], ?string $tier = null, ?string $preferKey = null) => $provider->extract($instruction, $files, $tier)
    );
    app()->instance(AiManager::class, $manager);

    return $capture;
}

/** Un membre du staff affecté à l'événement, sans la permission `users_roles`. */
function staffSansComptes(Event $event, string $role = 'accounting'): User
{
    $staff = User::create([
        'first_name' => 'Carine',
        'last_name' => 'Comptable',
        'email' => 'compta-annuaire@example.tg',
        'password' => 'pass12345',
    ]);
    $staff->assignedEditions()->attach($event->id, ['role' => $role]);

    return $staff;
}

it('sert l’annuaire des comptes à qui peut ouvrir la page Utilisateurs & rôles', function () {
    $admin = adminStaff();

    $cible = User::create([
        'first_name' => 'Nadia',
        'last_name' => 'Gestionnaire',
        'email' => 'nadia.annuaire@example.tg',
        'password' => 'pass12345',
        'access_level' => User::ACCESS_COLLOQUE_ADMIN,
    ]);

    expect(UserDirectory::autorise($admin))->toBeTrue();

    $annuaire = UserDirectory::digest($admin);

    // Ce que la PAGE montre, et rien d'autre : nom, e-mail, niveau d'accès, état.
    expect($annuaire)
        ->toContain('Nadia Gestionnaire')
        ->toContain($cible->email)
        ->toContain('Administrateur de colloque')
        ->toContain('RÉPARTITION DES COMPTES');
});

/**
 * Le cas qui compte : le staff ordinaire n'y a pas droit, et le prompt ne lui en
 * souffle pas un mot.
 */
it('ne donne rien à un membre du staff qui n’a pas accès à la page', function () {
    $event = openEdition();
    $staff = staffSansComptes($event);

    expect(UserDirectory::autorise($staff))->toBeFalse();
    expect(UserDirectory::digest($staff))->toBe('');

    // ⚠️ Pas même la mention de l'annuaire : décrire à un modèle une donnée qu'on
    // lui refuse, c'est l'inviter à la deviner.
    expect(UserDirectory::resume($staff))->toBe('');
});

/**
 * ⚠️ **La décision, écrite noir sur blanc.** « Administrateur de colloque » est
 * un NIVEAU D'ACCÈS, pas la permission qui garde la page : un gérant est en 403
 * sur `admin/utilisateurs`. Il ne doit donc rien obtenir par l'assistant, sans
 * quoi Marc deviendrait la porte la plus large de la plateforme.
 *
 * Pour lui ouvrir l'annuaire, on coche « Utilisateurs & rôles » dans la matrice
 * d'accès - et il voit alors la page ET l'annuaire, du même mouvement.
 */
it('ne donne rien à un administrateur de colloque sans la permission', function () {
    $gerant = User::create([
        'first_name' => 'Seyf',
        'last_name' => 'Gerant',
        'email' => 'gerant.annuaire@example.tg',
        'password' => 'pass12345',
        'access_level' => User::ACCESS_COLLOQUE_ADMIN,
    ]);

    // Il EST bien administrateur de colloque au sens du modèle...
    expect($gerant->isColloqueAdmin())->toBeTrue();

    // ... et pourtant il n'a pas accès à la page, donc pas à l'annuaire.
    expect($gerant->can('users_roles'))->toBeFalse();
    expect(UserDirectory::autorise($gerant))->toBeFalse();
    expect(UserDirectory::digest($gerant))->toBe('');
});

it('ne donne rien sans utilisateur identifié', function () {
    expect(UserDirectory::autorise(null))->toBeFalse();
    expect(UserDirectory::digest(null))->toBe('');
});

/**
 * ⚠️ Le prompt est reconstruit à CHAQUE message : un annuaire non borné le ferait
 * grossir sans fin, et la base de dev compte déjà 266 comptes.
 */
it('borne l’annuaire et renvoie vers les filtres de la page', function () {
    $admin = adminStaff();

    Role::firstOrCreate(['name' => 'organizer', 'guard_name' => 'web']);
    foreach (range(1, 4) as $i) {
        User::create([
            'first_name' => 'Org'.$i,
            'last_name' => 'Anisateur',
            'email' => 'org'.$i.'@example.tg',
            'password' => 'pass12345',
        ])->assignRole('organizer');
    }

    $borne = UserDirectory::digest($admin, 2, 2);

    expect($borne)
        ->toContain('autre(s) non listé(s)')
        ->toContain('page Utilisateurs & rôles');

    // Le plafond est VRAIMENT appliqué : au plus 2 lignes de personnel.
    $bloc = Str::between($borne, 'PERSONNEL ET COMPTES', 'RÉPARTITION');
    expect(substr_count($bloc, "\n- "))->toBe(2);
});

it('porte l’annuaire dans le prompt de Marc pour un administrateur', function () {
    $capture = capteurDePrompt();
    $event = openEdition();

    User::create([
        'first_name' => 'Yasmine',
        'last_name' => 'Secretaire',
        'email' => 'yasmine.annuaire@example.tg',
        'password' => 'pass12345',
        'access_level' => User::ACCESS_COLLOQUE_ADMIN,
    ]);

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.ai.ask'), ['question' => 'Qui sont les administrateurs ?', 'event_id' => $event->id])
        ->assertOk();

    expect($capture->prompt)
        ->toContain('COMPTES DE LA PLATEFORME')
        ->toContain('yasmine.annuaire@example.tg')
        // Marc lit, il n'écrit pas : toute modification passe par la page.
        ->toContain('ne désactives aucun compte');
});

it('n’en met rien dans le prompt d’un membre du staff non habilité', function () {
    $capture = capteurDePrompt();
    $event = openEdition();

    User::create([
        'first_name' => 'Yasmine',
        'last_name' => 'Secretaire',
        'email' => 'yasmine.annuaire@example.tg',
        'password' => 'pass12345',
        'access_level' => User::ACCESS_COLLOQUE_ADMIN,
    ]);

    $this->actingAs(staffSansComptes($event), 'web')
        ->postJson(route('admin.ai.ask'), ['question' => 'Qui sont les administrateurs ?', 'event_id' => $event->id])
        ->assertOk();

    expect($capture->prompt)
        ->not->toContain('COMPTES DE LA PLATEFORME')
        ->not->toContain('yasmine.annuaire@example.tg');
});

/**
 * La permission est éditable depuis la matrice d'accès : y cocher
 * « Utilisateurs & rôles » doit suffire, sans toucher au code de l'assistant.
 */
it('suit la matrice d’accès : cocher la permission ouvre l’annuaire', function () {
    $event = openEdition();
    $staff = staffSansComptes($event);

    expect(UserDirectory::autorise($staff))->toBeFalse();

    Permission::firstOrCreate(['name' => 'users_roles', 'guard_name' => 'web']);
    $staff->givePermissionTo('users_roles');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(UserDirectory::autorise($staff->fresh()))->toBeTrue();
    expect(UserDirectory::digest($staff->fresh()))->toContain('RÉPARTITION DES COMPTES');
});

/**
 * ⚠️ L'assistante PUBLIQUE ne doit jamais approcher ces données. La règle se
 * vérifie à la source : `UserDirectory` n'est cité que par le copilote admin.
 */
it('réserve l’annuaire au copilote admin, jamais à l’assistante publique', function () {
    $appelants = [];

    foreach ((array) glob(app_path('**/*.php')) + (array) glob(app_path('*/*/*.php')) as $f) {
        $src = (string) file_get_contents((string) $f);
        // ⚠️ Le symbole ENTIER, pas le préfixe : `UserDirectorySearch` est le
        // sélecteur de comptes du back-office, qui ne lit pas l'annuaire de
        // l'assistant. Cherché en sous-chaîne, son nom faisait tomber la garde
        // sur une homonymie - une alerte fausse qu'on finit par ignorer.
        if (preg_match('/\bUserDirectory\b/', $src) === 1) {
            $appelants[] = basename((string) $f);
        }
    }
    sort($appelants);

    expect($appelants)->toBe(['AskAdmin.php', 'UserDirectory.php']);
});
