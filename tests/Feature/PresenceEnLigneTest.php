<?php

use App\Models\User;
use App\Support\Realtime;
use Illuminate\Support\Facades\Config;

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

it('laisse le direct l’emporter sur le sondage', function () {
    // ⚠️ Les deux ne mesurent pas la même chose : laisser le sondage écraser
    // toutes les 30 secondes un chiffre plus juste par un chiffre plus vieux
    // ferait osciller le compteur sous les yeux.
    $vue = (string) file_get_contents(resource_path('views/admin/users/index.blade.php'));

    expect($vue)->toContain('clearInterval(this.sondage)')
        ->and($vue)->toContain('!this.direct')
        ->and($vue)->toContain('En ligne maintenant');
});
