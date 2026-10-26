<?php

use App\Models\ProjectTask;
use App\Models\User;
use App\Support\Realtime;
use App\Support\TaskDocumentHtml;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

// adminStaff() / openEdition() -> tests/Pest.php

/** Membre du staff ayant la capacité « project » sur l'événement, sans être admin. */
function staffProjet($event): User
{
    $u = User::factory()->create(['access_level' => User::ACCESS_USER]);
    $event->team()->attach($u->id, ['role' => 'secretariat']);

    return $u;
}

/**
 * Tâche portant un ANCIEN document texte.
 *
 * ⚠️ L'éditeur Tiptap/Yjs a été retiré le 21/10/2026 : plus rien ne crée ce
 * genre de document, mais les installations qui en portent doivent pouvoir le
 * relire. C'est exactement ce que ces cas éprouvent.
 */
function tacheAvecDocument($event, ?User $createur = null, ?string $html = null): array
{
    $task = ProjectTask::create([
        'event_id' => $event->id,
        'title' => 'Rédiger l’appel à communications',
        'status' => 'doing',
        'priority' => 'normal',
        'created_by' => $createur?->id,
    ]);

    $doc = $task->documents()->create([
        'title' => 'Appel à communications',
        'html' => $html,
        'created_by' => $createur?->id,
    ]);

    return [$task, $doc];
}

it('ouvre le document à qui accède au projet, et ne reconnaît le droit d’écrire qu’aux personnes associées', function () {
    $event = openEdition();
    $lecteur = staffProjet($event);
    $redacteur = staffProjet($event);

    [$task, $doc] = tacheAvecDocument($event);
    $task->collaborators()->sync([$redacteur->id]);

    // ⚠️ Un plan de travail partagé se LIT par l'équipe : le lecteur entre.
    $this->actingAs($lecteur, 'web')
        ->get(route('admin.project.documents.edit', [$task, $doc]))->assertOk();

    // ⚠️ Mais écrire suppose d'être associé à la tâche, sinon « associer
    // quelqu'un » ne voudrait rien dire - tout le monde écrirait déjà. C'est ce
    // prédicat qui décide de remettre, ou non, le jeton d'édition DocSpace.
    expect($doc->isEditableBy($lecteur))->toBeFalse()
        ->and($doc->isEditableBy($redacteur))->toBeTrue();
});

it('ferme le document à qui n’a pas la capacité « project » sur l’événement', function () {
    $event = openEdition();
    $dehors = User::factory()->create(['access_level' => User::ACCESS_USER]);
    $event->team()->attach($dehors->id, ['role' => 'reception']); // pas de capacité « project »

    [$task, $doc] = tacheAvecDocument($event);
    $task->collaborators()->sync([$dehors->id]);

    // ⚠️ Être nommé collaborateur d'une vieille tâche ne rouvre pas un événement
    // dont on a été retiré : l'accès au module passe en premier.
    $this->actingAs($dehors, 'web')
        ->get(route('admin.project.documents.edit', [$task, $doc]))->assertForbidden();

    expect($doc->isEditableBy($dehors))->toBeFalse();
});

it('⚠️ n’ouvre pas le document d’un AUTRE événement par une tâche accessible', function () {
    $staff = adminStaff();
    $evenementA = openEdition();
    $evenementB = openEdition();
    $evenementB->update(['slug' => 'uk-2027', 'year' => 2027]);

    [$tacheA] = tacheAvecDocument($evenementA);
    [, $docB] = tacheAvecDocument($evenementB);

    // ⚠️ `scopeBindings()` exige que le document appartienne à CETTE tâche :
    // sans lui, une tâche accessible plus l'identifiant d'un document d'ailleurs
    // ouvriraient le second.
    $this->actingAs($staff, 'web')
        ->get(route('admin.project.documents.edit', [$tacheA, $docB]))->assertNotFound();
});

it('⚠️ borne les personnes associées au vivier de l’événement', function () {
    $event = openEdition();
    $staff = adminStaff();
    $membre = staffProjet($event);
    $etranger = User::factory()->create(['access_level' => User::ACCESS_USER]);

    $this->actingAs($staff, 'web')->postJson(route('admin.project.tasks.store', ['event_id' => $event->id]), [
        'title' => 'Préparer le livret',
        'status' => 'todo',
        'priority' => 'normal',
        'collaborator_ids' => [$membre->id, $etranger->id],
    ])->assertOk();

    $task = ProjectTask::where('event_id', $event->id)->firstOrFail();

    // ⚠️ Un identifiant posté n'est pas une autorisation : les collaborateurs
    // ouvrent le plan de travail de l'événement.
    expect($task->collaborators()->pluck('users.id')->all())->toBe([$membre->id]);
});

it('⚠️ reconnaît l’ADMINISTRATEUR comme rédacteur, pas seulement le super administrateur', function () {
    // ⚠️ Le modèle donne à l'administrateur l'accès à TOUT. Garder ici un
    // `isColloqueSuperAdmin()` refermerait, sur ce seul écran, la porte que le
    // projet venait de lui ouvrir - et c'est le réflexe que
    // `DroitsDeLAdministrateurTest` traque dans tout `app/`.
    $event = openEdition();
    $admin = User::factory()->create(['access_level' => User::ACCESS_COLLOQUE_ADMIN]);
    [$task, $doc] = tacheAvecDocument($event);

    expect($task->isAssociate($admin))->toBeFalse()
        ->and($doc->isEditableBy($admin))->toBeTrue();
});

describe('ancien document texte', function () {
    it('⚠️ se relit entièrement, éditeur retiré', function () {
        // ⚠️⚠️ **UN TEXTE QU'ON CESSE D'AFFICHER EST UN TEXTE PERDU**, et c'est
        // en production qu'on s'en apercevrait. L'éditeur Tiptap/Yjs est parti ;
        // la page doit rendre ce qui a été rédigé avant.
        $event = openEdition();
        $staff = adminStaff();
        [$task, $doc] = tacheAvecDocument($event, $staff, '<h2>Ordre du jour</h2><p>Relire les résumés déposés.</p>');

        $this->actingAs($staff, 'web')
            ->get(route('admin.project.documents.edit', [$task, $doc]))->assertOk()
            ->assertSee('Relire les résumés déposés.', false)
            ->assertSee('Lecture seule');
    });

    it('⚠️ rend aussi ses versions conservées', function () {
        $event = openEdition();
        $staff = adminStaff();
        [$task, $doc] = tacheAvecDocument($event, $staff, '<p>dernier état</p>');
        $doc->versions()->create([
            'revision' => 1,
            'label' => 'Avant relecture',
            'html' => '<p>une phrase que personne ne doit perdre</p>',
            'user_id' => $staff->id,
        ]);

        $this->actingAs($staff, 'web')
            ->get(route('admin.project.documents.edit', [$task, $doc]))->assertOk()
            ->assertSee('Avant relecture')
            ->assertSee('une phrase que personne ne doit perdre', false);
    });

    it('⚠️ ne porte plus AUCUN point d’accroche de l’éditeur', function () {
        $event = openEdition();
        $staff = adminStaff();
        [$task, $doc] = tacheAvecDocument($event, $staff, '<p>texte</p>');

        $html = $this->actingAs($staff, 'web')
            ->get(route('admin.project.documents.edit', [$task, $doc]))->assertOk()->getContent();

        // ⚠️ Laisser un crochet, une barre d'outils ou l'entrée Vite sans le
        // script qui va avec donnerait une page qui a l'air modifiable et qui
        // n'enregistre rien.
        foreach (['ukcDocEditeur', 'window.UKC_DOC', 'data-doc-cmd', 'task-document'] as $trace) {
            expect($html)->not->toContain($trace);
        }
    });

    it('⚠️ les points d’entrée de l’ancien éditeur n’existent plus', function () {
        // ⚠️ Une route laissée en place sans écran est une seconde porte que
        // personne ne surveille (même leçon que `admin.participants.impersonate`).
        foreach ([
            'admin.project.documents.save',
            'admin.project.documents.state',
            'admin.project.documents.versions.store',
            'admin.project.documents.versions.show',
            'admin.project.documents.versions.download',
            'admin.project.documents.transfer',
            'office.documents.file',
            'office.documents.callback',
        ] as $nom) {
            expect(Route::has($nom))->toBeFalse($nom.' devrait avoir disparu');
        }
    });
});

describe('assainissement du HTML', function () {
    it('retire les scripts et les attributs, garde le texte', function () {
        $sale = '<p style="x" onclick="v()">Bonjour</p><script>alert(1)</script><span>suite</span>';

        $propre = TaskDocumentHtml::clean($sale);

        expect($propre)->toContain('Bonjour')
            ->and($propre)->toContain('suite')
            ->and($propre)->not->toContain('script')
            ->and($propre)->not->toContain('onclick')
            ->and($propre)->not->toContain('style');
    });

    it('⚠️ refuse un lien « javascript: » mais garde les liens légitimes', function () {
        // ⚠️ Ce HTML a été écrit par des rédacteurs et il est relu dans le
        // back-office : le retrait de l'éditeur n'enlève rien au besoin de
        // l'assainir à l'AFFICHAGE. Sans ce contrôle, « cliquer sur le lien »
        // exécuterait le code d'un ancien rédacteur chez qui relit la page.
        $propre = TaskDocumentHtml::clean(
            '<p><a href="javascript:vol()">piège</a> <a href="https://univkara.tg">UK</a></p>'
        );

        expect($propre)->not->toContain('javascript:')
            ->and($propre)->toContain('piège')
            ->and($propre)->toContain('https://univkara.tg');
    });

    it('⚠️ DÉPLIE une balise inconnue au lieu de jeter son contenu', function () {
        // Jeter le nœud entier ferait disparaître un paragraphe parce qu'il
        // portait une balise inconnue - et c'est le travail de quelqu'un.
        expect(TaskDocumentHtml::clean('<p><marquee>texte important</marquee></p>'))
            ->toContain('texte important');
    });

    it('garde les accents', function () {
        // Sans préambule d'encodage, DOMDocument lit l'UTF-8 comme du latin-1 et
        // rend « Ã© » là où il y avait « é », sur un texte qu'on vient d'écrire.
        expect(TaskDocumentHtml::clean('<p>Évaluation des résumés déposés</p>'))
            ->toContain('Évaluation des résumés déposés');
    });

    it('conserve titres et listes, que l’ancien éditeur produisait', function () {
        $propre = TaskDocumentHtml::clean('<h2>Ordre du jour</h2><ul><li>Point un</li></ul>');

        expect($propre)->toContain('<h2>')->and($propre)->toContain('<li>');
    });
});

/**
 * ⚠️⚠️ **LA COUCHE TEMPS RÉEL EST UNE RÉSERVE ASSUMÉE, PAS UN OUBLI**
 * (21/10/2026). Le retrait de l'éditeur Yjs l'a privée de son seul
 * consommateur ; elle est conservée sur décision explicite, pour un prochain
 * usage (présence, notifications en direct), avec les identifiants PieSocket
 * déjà réglés. Ces cas la gardent CORRECTE en attendant : un socle qu'on garde
 * sans l'éprouver se périme, et on le découvrirait le jour où on s'en sert.
 */
describe('couche temps reel en reserve', function () {
    it('⚠️ ne laisse JAMAIS le secret sortir du serveur', function () {
        Config::set('broadcasting.default', 'pusher');
        Config::set('broadcasting.connections.pusher.app_id', '424242');
        Config::set('broadcasting.connections.pusher.key', 'cle-publique');
        Config::set('broadcasting.connections.pusher.secret', 'SECRET-A-NE-PAS-SORTIR');
        Config::set('broadcasting.connections.pusher.options.host', 'exemple.piesocket.com');

        // La clé publique est faite pour descendre dans une page ; le secret
        // signe l'autorisation de canal côté serveur, et NULLE PART ailleurs.
        $config = Realtime::clientConfig();
        expect($config)->not->toBeNull()
            ->and($config)->not->toHaveKey('secret')
            ->and(json_encode($config))->not->toContain('SECRET-A-NE-PAS-SORTIR')
            ->and($config['key'] ?? null)->toBe('cle-publique');
    });

    it('⚠️ exige les TROIS identifiants avant de s’annoncer', function () {
        Config::set('broadcasting.default', 'pusher');
        Config::set('broadcasting.connections.pusher.app_id', '1');
        Config::set('broadcasting.connections.pusher.key', 'cle');
        Config::set('broadcasting.connections.pusher.options.host', 'exemple.piesocket.com');
        Config::set('broadcasting.connections.pusher.secret', null);

        // Avec la clé publique seule, une page ouvrirait une connexion que le
        // serveur ne saurait pas autoriser : l'attente, sans un mot d'erreur.
        expect(Realtime::configured())->toBeFalse();

        Config::set('broadcasting.connections.pusher.secret', 'secret');
        expect(Realtime::configured())->toBeTrue();
    });

    it('le canal porte UNE seule définition de nom', function () {
        // Deux chaînes bâties séparément se décaleraient un jour, et chaque
        // abonné se retrouverait seul dans un canal à lui, sans erreur visible.
        expect(Realtime::documentChannel(12))->toBe('ukc.doc.12');

        $channels = file_get_contents(base_path('routes/channels.php'));
        expect($channels)->toContain('ukc.doc.{document}');
    });
});

describe('autorisation du canal temps reel', function () {
    beforeEach(function () {
        Config::set('broadcasting.default', 'pusher');
        Config::set('broadcasting.connections.pusher.app_id', '424242');
        Config::set('broadcasting.connections.pusher.key', 'cle');
        Config::set('broadcasting.connections.pusher.secret', 'secret');

        // ⚠️⚠️ **`Broadcast::channel()` ENREGISTRE SUR LE PILOTE COURANT**
        // (`BroadcastManager::__call` délègue à `driver()`). En production,
        // l'ordre est le bon tout seul : `applySettingsToConfig()` pose
        // `broadcasting.default` pendant le `boot()` des fournisseurs, et
        // `withBroadcasting()` ne charge `routes/channels.php` qu'ensuite, dans
        // un rappel `booted()`. Ici la configuration change APRÈS le démarrage :
        // sans ce rechargement, les canaux resteraient posés sur le pilote
        // « null » et TOUTES les demandes seraient refusées - y compris les
        // légitimes, ce qui ferait passer les deux cas de refus ci-dessous pour
        // bons alors qu'ils ne prouveraient plus rien.
        require base_path('routes/channels.php');
    });

    it('refuse le canal d un document a qui n a pas acces au projet', function () {
        $event = openEdition();
        [$task, $doc] = tacheAvecDocument($event);
        $dehors = User::factory()->create(['access_level' => User::ACCESS_USER]);

        $this->actingAs($dehors, 'web')
            ->post('/broadcasting/auth', [
                'channel_name' => 'presence-'.Realtime::documentChannel($doc->id),
                'socket_id' => '1234.5678',
            ])
            ->assertForbidden();
    });

    it('refuse le canal a qui n est pas connecte', function () {
        $event = openEdition();
        [$task, $doc] = tacheAvecDocument($event);

        $this->post('/broadcasting/auth', [
            'channel_name' => 'presence-'.Realtime::documentChannel($doc->id),
            'socket_id' => '1234.5678',
        ])->assertForbidden();
    });

    it('ouvre le canal a l equipe, et dit qui peut ecrire', function () {
        $event = openEdition();
        $lecteur = staffProjet($event);
        $redacteur = staffProjet($event);
        [$task, $doc] = tacheAvecDocument($event);
        $task->collaborators()->sync([$redacteur->id]);

        $reponse = $this->actingAs($redacteur, 'web')
            ->post('/broadcasting/auth', [
                'channel_name' => 'presence-'.Realtime::documentChannel($doc->id),
                'socket_id' => '1234.5678',
            ])->assertOk();

        expect($reponse->getContent())->toContain('canWrite');

        // ⚠️ **La forme de la signature est ce que le service VÉRIFIE.** Elle
        // doit valoir « cle:HMAC-SHA256(socket_id:canal:donnees) », et le canal
        // de présence doit porter ses `channel_data` - sans eux, le service
        // refuse l'abonnement. Éprouvé contre le vrai service.
        $charge = $reponse->json();

        expect($charge)->toHaveKeys(['auth', 'channel_data'])
            ->and($charge['auth'])->toMatch('/^cle:[0-9a-f]{64}$/');

        $donnees = json_decode((string) $charge['channel_data'], true);
        // ⚠️ `user_id` n'est plus la clé primaire mais l'EMPREINTE du compte
        // ({@see User::getAuthIdentifierForBroadcasting()}) : la liste des
        // membres d'un canal de présence est lue par tous les autres membres,
        // et le canal des connectés les réunit tous. Ce qui compte ici est
        // qu'elle désigne bien CE rédacteur, et lui seul.
        expect($donnees['user_id'])->toBe(Realtime::presenceMemberId($redacteur->id))
            ->and($donnees['user_id'])->not->toBe((string) $redacteur->id)
            ->and($donnees['user_info']['canWrite'])->toBeTrue();

        $vu = $this->actingAs($lecteur, 'web')
            ->post('/broadcasting/auth', [
                'channel_name' => 'presence-'.Realtime::documentChannel($doc->id),
                'socket_id' => '1234.5678',
            ])->assertOk()->getContent();

        // Le lecteur entre dans le canal, mais son entree dit qu il ne redige pas.
        expect($vu)->toContain('false');
    });
});
