<?php

use App\Models\ProjectTask;
use App\Models\ProjectTaskDocument;
use App\Models\User;
use App\Support\Realtime;
use App\Support\TaskDocumentHtml;
use Illuminate\Support\Facades\Config;

// adminStaff() / openEdition() -> tests/Pest.php

/** Membre du staff ayant la capacité « project » sur l'événement, sans être admin. */
function staffProjet($event): User
{
    $u = User::factory()->create(['access_level' => User::ACCESS_USER]);
    $event->team()->attach($u->id, ['role' => 'secretariat']);

    return $u;
}

function tacheAvecDocument($event, ?User $createur = null): array
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
        'created_by' => $createur?->id,
    ]);

    return [$task, $doc];
}

it('ouvre le document à qui accède au projet, et ne laisse écrire que les personnes associées', function () {
    $event = openEdition();
    $lecteur = staffProjet($event);
    $redacteur = staffProjet($event);

    [$task, $doc] = tacheAvecDocument($event);
    $task->collaborators()->sync([$redacteur->id]);

    // ⚠️ Un plan de travail partagé se LIT par l'équipe : le lecteur entre.
    $this->actingAs($lecteur, 'web')
        ->get(route('admin.project.documents.edit', [$task, $doc]))->assertOk();

    // ⚠️ Mais écrire suppose d'être associé à la tâche, sinon « associer
    // quelqu'un » ne voudrait rien dire - tout le monde écrirait déjà.
    $this->actingAs($lecteur, 'web')
        ->postJson(route('admin.project.documents.save', [$task, $doc]), [
            'state' => base64_encode('x'), 'html' => '<p>a</p>', 'revision' => 0,
        ])->assertForbidden();

    $this->actingAs($redacteur, 'web')
        ->postJson(route('admin.project.documents.save', [$task, $doc]), [
            'state' => base64_encode('x'), 'html' => '<p>a</p>', 'revision' => 0,
        ])->assertOk();
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
});

it('⚠️ n’ouvre pas le document d’un AUTRE événement par une tâche accessible', function () {
    $staff = adminStaff();
    $evenementA = openEdition();
    $evenementB = openEdition(['title' => 'Autre événement', 'slug' => 'autre-evenement']);

    [$tacheA] = tacheAvecDocument($evenementA);
    [, $docB] = tacheAvecDocument($evenementB);

    // Sans l'imbrication contrôlée (`scopeBindings`), une tâche qu'on a le droit
    // d'ouvrir plus l'identifiant d'un document d'ailleurs suffiraient à lire le
    // second.
    $this->actingAs($staff, 'web')
        ->get(route('admin.project.documents.edit', [$tacheA, $docB]))
        ->assertNotFound();
});

it('enregistre, incrémente la révision et assainit le HTML', function () {
    $event = openEdition();
    $staff = adminStaff();
    [$task, $doc] = tacheAvecDocument($event, $staff);

    $this->actingAs($staff, 'web')
        ->postJson(route('admin.project.documents.save', [$task, $doc]), [
            'state' => base64_encode('etat'),
            'html' => '<p onclick="voler()">Ordre du jour</p><script>alert(1)</script>',
            'revision' => 0,
        ])
        ->assertOk()
        ->assertJsonPath('revision', 1);

    $doc->refresh();

    expect($doc->html)->toContain('Ordre du jour')
        ->and($doc->html)->not->toContain('onclick')
        ->and($doc->html)->not->toContain('alert(1)')
        ->and($doc->revision)->toBe(1);
});

it('⚠️ refuse un enregistrement parti d’une révision dépassée, et rend l’état courant', function () {
    $event = openEdition();
    $staff = adminStaff();
    [$task, $doc] = tacheAvecDocument($event, $staff);

    $this->actingAs($staff, 'web')->postJson(route('admin.project.documents.save', [$task, $doc]), [
        'state' => base64_encode('premier'), 'html' => '<p>1</p>', 'revision' => 0,
    ])->assertOk();

    // ⚠️ Le refus n'est pas une perte : la réponse porte l'état du serveur, que
    // le poste repoussé FUSIONNE au sien avant de réessayer. Sans cet état, il
    // n'aurait d'autre issue que de recharger - en perdant sa frappe.
    $this->actingAs($staff, 'web')->postJson(route('admin.project.documents.save', [$task, $doc]), [
        'state' => base64_encode('second'), 'html' => '<p>2</p>', 'revision' => 0,
    ])
        ->assertStatus(409)
        ->assertJsonPath('revision', 1)
        ->assertJsonPath('state', base64_encode('premier'));

    expect($doc->fresh()->html)->toContain('1');
});

it('verse une version au premier enregistrement, puis espace les suivantes', function () {
    $event = openEdition();
    $staff = adminStaff();
    [$task, $doc] = tacheAvecDocument($event, $staff);

    $enregistre = fn (int $revision) => $this->actingAs($staff, 'web')
        ->postJson(route('admin.project.documents.save', [$task, $doc]), [
            'state' => base64_encode('e'.$revision), 'html' => '<p>'.$revision.'</p>', 'revision' => $revision,
        ])->assertOk();

    $enregistre(0);
    $enregistre(1);
    $enregistre(2);

    // ⚠️ L'enregistrement est automatique et fréquent : verser une version à
    // chaque fois rendrait l'historique illisible - des centaines d'entrées
    // pour une après-midi, et plus aucun point de repère où revenir.
    expect($doc->versions()->count())->toBe(1);

    // Le délai écoulé, la version suivante s'ouvre.
    $doc->versions()->first()->forceFill([
        'created_at' => now()->subMinutes(ProjectTaskDocument::VERSION_EVERY_MINUTES + 1),
    ])->save();

    $enregistre(3);

    expect($doc->versions()->count())->toBe(2);
});

it('rend le contenu d’une version pour revenir en arrière', function () {
    $event = openEdition();
    $staff = adminStaff();
    [$task, $doc] = tacheAvecDocument($event, $staff);

    $this->actingAs($staff, 'web')->postJson(route('admin.project.documents.save', [$task, $doc]), [
        'state' => base64_encode('e'), 'html' => '<p>Première rédaction</p>', 'revision' => 0,
    ])->assertOk();

    $version = $doc->versions()->firstOrFail();

    $this->actingAs($staff, 'web')
        ->getJson(route('admin.project.documents.versions.show', [$task, $doc, $version]))
        ->assertOk()
        ->assertJsonPath('html', '<p>Première rédaction</p>');
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

it('⚠️ ne laisse JAMAIS le secret du service temps réel descendre dans la page', function () {
    Config::set('broadcasting.default', 'pusher');
    Config::set('broadcasting.connections.pusher.app_id', '424242');
    Config::set('broadcasting.connections.pusher.key', 'cle-publique');
    Config::set('broadcasting.connections.pusher.secret', 'SECRET-A-NE-PAS-SORTIR');
    Config::set('broadcasting.connections.pusher.options.host', 'exemple.piesocket.com');

    $event = openEdition();
    $staff = adminStaff();
    [$task, $doc] = tacheAvecDocument($event, $staff);

    $html = $this->actingAs($staff, 'web')
        ->get(route('admin.project.documents.edit', [$task, $doc]))->assertOk()->getContent();

    // La clé publique part dans la page - c'est son rôle. Le secret signe
    // l'autorisation de canal côté serveur, et NULLE PART ailleurs.
    expect($html)->toContain('cle-publique')
        ->and($html)->not->toContain('SECRET-A-NE-PAS-SORTIR');

    expect(Realtime::clientConfig())->not->toHaveKey('secret');
});

it('fonctionne sans service temps réel, et le DIT', function () {
    Config::set('broadcasting.default', 'null');

    $event = openEdition();
    $staff = adminStaff();
    [$task, $doc] = tacheAvecDocument($event, $staff);

    expect(Realtime::configured())->toBeFalse()
        ->and(Realtime::clientConfig())->toBeNull();

    // ⚠️ On le dit au lieu de laisser croire que la simultanéité est branchée :
    // deux personnes ne perdent rien (les états fusionnent à l'enregistrement),
    // mais elles doivent savoir qu'elles ne se verront pas taper.
    $this->actingAs($staff, 'web')
        ->get(route('admin.project.documents.edit', [$task, $doc]))
        ->assertOk()
        ->assertSee('Rédaction simultanée non activée');

    // Et l'enregistrement, lui, marche exactement pareil.
    $this->actingAs($staff, 'web')->postJson(route('admin.project.documents.save', [$task, $doc]), [
        'state' => base64_encode('e'), 'html' => '<p>ok</p>', 'revision' => 0,
    ])->assertOk();
});

it('⚠️ exige les TROIS identifiants avant d’annoncer le temps réel', function () {
    Config::set('broadcasting.default', 'pusher');
    Config::set('broadcasting.connections.pusher.app_id', '1');
    Config::set('broadcasting.connections.pusher.key', 'cle');
    Config::set('broadcasting.connections.pusher.secret', null);

    // Avec la clé publique seule, la page ouvrirait une connexion que le serveur
    // ne saurait pas autoriser : l'éditeur attendrait des pairs qui n'arrivent
    // jamais, sans rien dire.
    expect(Realtime::configured())->toBeFalse();

    Config::set('broadcasting.connections.pusher.secret', 'secret');
    expect(Realtime::configured())->toBeTrue();
});

it('le canal porte UNE seule définition de nom', function () {
    // Trois endroits écrivent ce nom - la page, l'autorisation de canal et les
    // tests. Trois chaînes bâties séparément se décaleraient un jour, et chaque
    // rédacteur se retrouverait seul dans un canal à lui, sans erreur visible.
    expect(Realtime::documentChannel(12))->toBe('ukc.doc.12');

    $channels = file_get_contents(base_path('routes/channels.php'));
    expect($channels)->toContain('ukc.doc.{document}');
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
        // Le lien est le seul endroit où le rédacteur fournit une adresse : sans
        // ce contrôle, « cliquer sur le lien » exécuterait son code chez tous les
        // autres rédacteurs, dans le back-office.
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

    it('conserve titres et listes, que l’éditeur produit', function () {
        $propre = TaskDocumentHtml::clean('<h2>Ordre du jour</h2><ul><li>Point un</li></ul>');

        expect($propre)->toContain('<h2>')->and($propre)->toContain('<li>');
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
        // refuse l'abonnement et l'éditeur reste seul, sans message d'erreur
        // lisible. Éprouvé contre le vrai service : cette forme est acceptée.
        $charge = $reponse->json();

        expect($charge)->toHaveKeys(['auth', 'channel_data'])
            ->and($charge['auth'])->toMatch('/^cle:[0-9a-f]{64}$/');

        $donnees = json_decode((string) $charge['channel_data'], true);
        // ⚠️ Le protocole sérialise `user_id` en CHAÎNE : le comparer à un
        // entier échouerait sur un détail de transport, pas sur la règle.
        expect((int) $donnees['user_id'])->toBe($redacteur->id)
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

it('rend les points d accroche dont le script a besoin', function () {
    // ⚠️ Faute de pouvoir regarder la page (le navigateur integre refuse
    // l hote de developpement), c est ce cas qui tient le contrat entre la vue
    // et le script : sans l un de ces crochets, l editeur ne se monte pas, la
    // barre ne commande rien, et l etat de l enregistrement n est jamais
    // affiche - sans aucune erreur visible.
    $event = openEdition();
    $staff = adminStaff();
    [$task, $doc] = tacheAvecDocument($event, $staff);

    $html = $this->actingAs($staff, 'web')
        ->get(route('admin.project.documents.edit', [$task, $doc]))->assertOk()->getContent();

    foreach (['ukcDocEditeur', 'ukcDocEtat', 'ukcDocPresence', 'ukcDocVersions', 'ukcDocVersion', 'window.UKC_DOC'] as $crochet) {
        expect($html)->toContain($crochet);
    }

    // La barre de mise en forme commande l editeur par un attribut, pas par un
    // gestionnaire ecrit dans la vue.
    expect($html)->toContain('data-doc-cmd="toggleBold"')
        ->and($html)->toContain('data-doc-cmd="toggleBulletList"');

    // ⚠️ L entree Vite est SEPAREE : si elle n est pas appelee par cette page,
    // l editeur n existe nulle part - et personne ne s en apercevrait avant
    // d ouvrir un document.
    expect($html)->toContain('task-document');
});

it('n offre les gestes d ecriture qu a qui peut ecrire', function () {
    $event = openEdition();
    $lecteur = staffProjet($event);
    [$task, $doc] = tacheAvecDocument($event);

    $html = $this->actingAs($lecteur, 'web')
        ->get(route('admin.project.documents.edit', [$task, $doc]))->assertOk()->getContent();

    // Masquer un bouton n a jamais protege personne - le serveur refuse de toute
    // facon - mais proposer « Enregistrer une version » a qui sera refuse est un
    // piege.
    expect($html)->not->toContain('ukcDocVersion"')
        ->and($html)->not->toContain('data-doc-cmd');
});

it('⚠️ laisse écrire l ADMINISTRATEUR, pas seulement le super administrateur', function () {
    // ⚠️ Le modèle donne à l administrateur l accès à TOUT. Garder ici un
    // `isColloqueSuperAdmin()` refermait, sur ce seul écran, la porte que le
    // projet venait de lui ouvrir - et c est le réflexe que
    // `DroitsDeLAdministrateurTest` traque dans tout `app/`.
    $event = openEdition();
    $admin = User::factory()->create(['access_level' => User::ACCESS_COLLOQUE_ADMIN]);
    [$task, $doc] = tacheAvecDocument($event);

    expect($task->isAssociate($admin))->toBeFalse();

    $this->actingAs($admin, 'web')
        ->postJson(route('admin.project.documents.save', [$task, $doc]), [
            'state' => base64_encode('x'), 'html' => '<p>a</p>', 'revision' => 0,
        ])->assertOk();
});
