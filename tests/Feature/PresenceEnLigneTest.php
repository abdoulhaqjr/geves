<?php

use App\Models\User;
use App\Support\Realtime;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Le nombre de connectés, en direct
|--------------------------------------------------------------------------
|
| Le compteur « En ligne » se déduisait de la table des sessions, par un
| sondage toutes les 30 secondes : il disait qui avait été ACTIF dans les cinq
| dernières minutes, pas qui était là. Un canal de présence répond à la vraie
| question - et il ne coûte aucune connexion de plus, la cloche en tenant déjà
| une sur chaque page authentifiée.
|
*/

it('ne nomme le canal qu’à UN seul endroit', function () {
    // Trois chaînes bâties chacune de leur côté finiraient par se décaler, et
    // chaque poste compterait les connectés d'un canal à lui - sans erreur.
    $js = (string) file_get_contents(resource_path('js/app.js'));
    $cloche = (string) file_get_contents(resource_path('views/components/ukc/notification-bell.blade.php'));

    expect($js)->not->toContain(Realtime::presenceChannel())
        ->and($js)->toContain('presence-${canal}')
        ->and($cloche)->toContain('Realtime::presenceChannel()');
});

it('donne à chaque compte une empreinte stable, qui ne le désigne pas', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    $empreinte = Realtime::presenceMemberId($a->id);

    expect($empreinte)->toBe(Realtime::presenceMemberId($a->id))
        ->and($empreinte)->not->toBe(Realtime::presenceMemberId($b->id))
        // Elle sert d'identité de membre : deux onglets comptent pour une
        // personne parce que le service dédoublonne sur cette valeur.
        ->and($a->getAuthIdentifierForBroadcasting())->toBe($empreinte)
        ->and($empreinte)->toMatch('/^[0-9a-f]{32}$/');
});

describe('autorisation du canal des connectés', function () {
    beforeEach(function () {
        Config::set('broadcasting.default', 'pusher');
        Config::set('broadcasting.connections.pusher.app_id', '424242');
        Config::set('broadcasting.connections.pusher.key', 'cle');
        Config::set('broadcasting.connections.pusher.secret', 'secret');

        // ⚠️ Les canaux se posent sur le pilote COURANT : la configuration
        // change ici après le démarrage, donc il faut les reposer. Sans ce
        // rechargement, tout serait refusé - y compris ce qui doit passer, et
        // le cas de refus ci-dessous ne prouverait plus rien.
        require base_path('routes/channels.php');
    });

    it('refuse le canal à qui n’est pas connecté', function () {
        $this->post('/broadcasting/auth', [
            'channel_name' => 'presence-'.Realtime::presenceChannel(),
            'socket_id' => '1234.5678',
        ])->assertForbidden();
    });

    it('ouvre le canal au participant comme au membre du staff', function () {
        foreach (['account', 'web'] as $guard) {
            $reponse = $this->actingAs(User::factory()->create(), $guard)
                ->post('/broadcasting/auth', [
                    'channel_name' => 'presence-'.Realtime::presenceChannel(),
                    'socket_id' => '1234.5678',
                ])->assertOk();

            expect($reponse->json())->toHaveKeys(['auth', 'channel_data']);
        }
    });

    it('ne publie RIEN qui désigne quelqu’un aux autres membres', function () {
        // La liste des membres de ce canal-là est lue par toute la plateforme.
        $user = User::factory()->create([
            'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa.presence@example.tg',
        ]);

        $charge = $this->actingAs($user, 'account')
            ->post('/broadcasting/auth', [
                'channel_name' => 'presence-'.Realtime::presenceChannel(),
                'socket_id' => '1234.5678',
            ])->assertOk()->json();

        $donnees = (string) $charge['channel_data'];

        expect($donnees)->not->toContain('awa.presence@example.tg')
            ->and($donnees)->not->toContain('Kodjo')
            ->and($donnees)->toContain(Realtime::presenceMemberId($user->id));
    });
});

it('garde le sondage serveur quand aucun service temps réel n’est réglé', function () {
    // Le direct AMÉLIORE le compteur, il ne le remplace pas : sans service, la
    // page doit continuer de compter comme avant.
    expect(Realtime::configured())->toBeFalse();

    $admin = usersAdmin();
    $this->actingAs($admin, 'web')->get(route('admin.users.index'))
        ->assertOk()
        // ⚠️ L'adresse part dans une expression Alpine, donc en JSON : ses
        // barres obliques y sont échappées. La chercher telle quelle ne mord
        // jamais - et le cas passerait au vert le jour où le sondage partirait.
        ->assertSee(str_replace('/', '\\/', route('admin.users.online')), false)
        ->assertSee('En ligne (5 min)');
});

/** Une session authentifiée, vue par la table des sessions. */
function sessionActive(User $user, int $ilYaSecondes = 0): void
{
    DB::table(config('session.table', 'sessions'))->insert([
        'id' => bin2hex(random_bytes(16)),
        'user_id' => $user->id,
        'ip_address' => '41.207.185.10',
        'user_agent' => 'test',
        'payload' => '',
        'last_activity' => now()->timestamp - $ilYaSecondes,
    ]);
}

it('le canal ne sert QUE de signal, jamais de chiffre à afficher', function () {
    // ⚠️⚠️ Il sait combien, jamais qui - ses membres sont des empreintes.
    // L'écran a besoin des deux, donc il redemande tout au serveur quand le
    // canal bouge. Afficher le compte du canal en face de pastilles calculées
    // depuis les sessions ferait dire deux choses au même écran.
    $vue = (string) file_get_contents(resource_path('views/admin/users/index.blade.php'));
    $js = (string) file_get_contents(resource_path('js/app.js'));

    expect($vue)->toContain("window.addEventListener('ukc-en-ligne'")
        ->and($vue)->toContain('this.rafraichir()')
        // Le compteur ne se pose jamais depuis la charge de l'évènement.
        ->and($vue)->not->toContain('onlineCount = e.detail')
        ->and($js)->toContain("new CustomEvent('ukc-en-ligne')");
});

it('dit la présence ligne par ligne, et la carte compte EXACTEMENT ces lignes', function () {
    // ⚠️⚠️ La garde qui tient tout le reste : une carte qui annonce un nombre
    // que les pastilles ne confirment pas se lit comme une panne.
    // ⚠️ Les sessions en base sont la SEULE source par personne ; la suite de
    // tests tourne sur le pilote « array », où il n'y a rien à lire.
    config(['session.driver' => 'database']);

    $admin = usersAdmin();
    $present = User::factory()->create(['name' => 'Awa Presente', 'is_active' => true]);
    $autre = User::factory()->create(['name' => 'Bea Presente', 'is_active' => true]);
    $parti = User::factory()->create(['name' => 'Cyr Absent', 'is_active' => true]);

    sessionActive($present);
    sessionActive($autre, 60);
    sessionActive($parti, 3600); // Au-delà de la fenêtre de 5 minutes.

    $html = $this->actingAs($admin, 'web')->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Présence')
        ->assertSee('Hors ligne')
        ->getContent();

    expect(substr_count((string) $html, '>En ligne</span>'))->toBe(2)
        // La carte lit la MÊME liste : son chiffre est la taille de celle-ci.
        ->and((string) $html)->toContain('<div class="val green" x-text="onlineCount === null ? \'-\' : onlineCount">2</div>');
});

it('mène de la carte aux personnes qu’elle compte, et emporte les autres filtres', function () {
    // ⚠️ « 1 en ligne » au-dessus d'une page où tout le monde est hors ligne se
    // lit comme une panne : le chiffre doit mener à qui il compte.
    config(['session.driver' => 'database']);

    // ⚠️ On éprouve l'e-mail, pas le nom : `User::saving()` recompose `name`
    // depuis prénom/nom dès que la fabrique les pose, donc un `name` passé à
    // la création ne survit pas - et l'assertion porterait à côté.
    $admin = usersAdmin();
    $present = User::factory()->create(['email' => 'presente@example.tg', 'is_active' => true]);
    User::factory()->create(['email' => 'absente@example.tg', 'is_active' => true]);
    sessionActive($present);

    $this->actingAs($admin, 'web')->get(route('admin.users.index', ['presence' => 'online']))
        ->assertOk()
        ->assertSee('presente@example.tg')
        ->assertDontSee('absente@example.tg');

    // Le lien de la carte garde la recherche en cours, et il BASCULE.
    $html = (string) $this->actingAs($admin, 'web')
        ->get(route('admin.users.index', ['search' => 'Awa']))->getContent();
    expect($html)->toContain('search=Awa&amp;presence=online');

    $html = (string) $this->actingAs($admin, 'web')
        ->get(route('admin.users.index', ['presence' => 'online', 'search' => 'Awa']))->getContent();
    expect($html)->toContain('is-on')
        ->and($html)->not->toContain('presence=online"');
});

it('ignore une valeur de présence inconnue', function () {
    // Appliquée, elle viderait la liste et ferait apparaître « Réinitialiser »
    // pour un filtre que la barre ne montre nulle part.
    config(['session.driver' => 'database']);

    $admin = usersAdmin();
    User::factory()->create(['email' => 'absente@example.tg', 'is_active' => true]);

    $this->actingAs($admin, 'web')->get(route('admin.users.index', ['presence' => 'nimporte']))
        ->assertOk()
        ->assertSee('absente@example.tg')
        ->assertDontSee('Réinitialiser');
});

it('rend la liste des connectés en même temps que leur nombre', function () {
    // Les deux sortent d'une seule requête : servis séparément, la page
    // pourrait les afficher en désaccord.
    config(['session.driver' => 'database']);

    $admin = usersAdmin();
    $present = User::factory()->create(['is_active' => true]);
    sessionActive($present);

    $charge = $this->actingAs($admin, 'web')->getJson(route('admin.users.online'))
        ->assertOk()->json();

    expect($charge['count'])->toBe(1)
        ->and($charge['ids'])->toBe([$present->id]);
});

it('se tait sur la présence quand les sessions ne sont pas en base', function () {
    // ⚠️ « On ne sait pas » n'est pas « personne » : annoncer tout le monde
    // hors ligne serait une affirmation fausse.
    config(['session.driver' => 'file']);
    $admin = usersAdmin();

    $this->actingAs($admin, 'web')->get(route('admin.users.index'))
        ->assertOk()
        ->assertDontSee('>En ligne</span>', false)
        ->assertDontSee('>Hors ligne</span>', false);
});
