<?php

use App\Models\ProjectTask;
use App\Models\ProjectTaskDocument;
use App\Models\User;
use App\Support\TaskDocSpace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

// adminStaff() / openEdition() -> tests/Pest.php ; staffProjet() -> DocumentDeTacheTest.php

const DS_URL = 'https://docspace.test';
const DS_CLE = 'cle-api-docspace-a-ne-jamais-divulguer';

function reglagesDocSpace(bool $liens = true): void
{
    Config::set('services.docspace.url', DS_URL);
    Config::set('services.docspace.api_key', DS_CLE);
    Config::set('services.docspace.room_id', '4706751');
    Config::set('services.docspace.file_links_enabled', $liens);
}

/** Tâche du projet, sans document. */
function tacheProjet($event, ?User $createur = null): ProjectTask
{
    return ProjectTask::create([
        'event_id' => $event->id,
        'title' => 'Rédiger la note de cadrage',
        'status' => 'doing',
        'priority' => 'normal',
        'created_by' => $createur?->id,
    ]);
}

/**
 * Réponses de l'API DocSpace pour une création : dossier, fichier, lien
 * d'édition, puis la vérification du lien.
 */
function faireRepondreDocSpace(string $fileId = '9001', string $token = 'JETON-EDITION-A'): void
{
    Http::fake([
        DS_URL.'/api/2.0/files/folder/4706751' => Http::response(['response' => ['id' => 5001]]),
        DS_URL.'/api/2.0/files/5001/file' => Http::response(['response' => ['id' => (int) $fileId]]),
        DS_URL.'/api/2.0/files/5001/upload*' => Http::response(['response' => [['id' => (int) $fileId]]]),
        DS_URL.'/api/2.0/files/file/'.$fileId.'/link' => Http::response([
            'response' => ['access' => 10, 'sharedLink' => ['id' => 'lien-1', 'requestToken' => $token]],
        ]),
        DS_URL.'/api/2.0/files/share/'.$token.'*' => Http::response([
            'response' => ['status' => 0, 'id' => (int) $fileId],
        ]),
    ]);
}

/** Document DocSpace déjà en place, sans aucun appel réseau. */
function documentDocSpace(ProjectTask $task, string $fileId = '9001', string $token = 'JETON-EDITION-A'): ProjectTaskDocument
{
    return $task->documents()->create([
        'title' => 'Note de cadrage',
        'format' => 'docx',
        'docspace_file_id' => $fileId,
        'docspace_request_token' => $token,
    ]);
}

/** Les trois réponses d'une rotation : liste des liens, nouveau lien, révocation. */
function faireTournerLeLien(string $ancien, string $nouveau, string $fileId = '9001'): void
{
    Http::fake([
        DS_URL.'/api/2.0/files/file/'.$fileId.'/links' => Http::sequence()
            ->push(['response' => [['sharedLink' => ['id' => 'lien-1', 'requestToken' => $ancien]]]])
            ->push(['response' => ['access' => 10, 'sharedLink' => ['id' => 'lien-2', 'requestToken' => $nouveau]]])
            ->push(['response' => []]),
        DS_URL.'/api/2.0/files/share/'.$nouveau.'*' => Http::response(['response' => ['status' => 0, 'id' => (int) $fileId]]),
    ]);
}

/** Archive OOXML minimale, acceptée par `TaskDocSpace::validFile()`. */
function docxMinimal(): string
{
    $chemin = tempnam(sys_get_temp_dir(), 'geves_docx_');
    $zip = new ZipArchive;
    $zip->open($chemin, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types/>');
    $zip->addFromString('word/document.xml', '<?xml version="1.0"?><document/>');
    $zip->close();

    try {
        return (string) file_get_contents($chemin);
    } finally {
        @unlink($chemin);
    }
}

it('crée le DOCX dans DocSpace et garde l’identifiant du fichier et son lien', function () {
    $event = openEdition();
    $redacteur = staffProjet($event);
    $task = tacheProjet($event);
    $task->collaborators()->sync([$redacteur->id]);
    reglagesDocSpace();
    faireRepondreDocSpace();

    $this->actingAs($redacteur, 'web')
        ->post(route('admin.project.documents.store', $task), ['title' => 'Note de cadrage', 'format' => 'docx'])
        ->assertRedirect();

    $doc = $task->documents()->firstOrFail();
    expect($doc->format)->toBe('docx')
        ->and($doc->docspace_file_id)->toBe('9001')
        ->and($doc->docspace_request_token)->toBe('JETON-EDITION-A')
        ->and($doc->isOfficeFile())->toBeTrue();

    // Le dossier de la tâche est retenu : un second document ne le recrée pas.
    expect($task->fresh()->docspace_folder_id)->toBe('5001');
});

it('⚠️ le jeton du lien d’édition est CHIFFRÉ en base, pas écrit en clair', function () {
    $event = openEdition();
    $doc = documentDocSpace(tacheProjet($event), '9001', 'JETON-EDITION-A');

    // ⚠️ Le jeton vaut un droit d'écriture sur le fichier chez le prestataire :
    // lu en clair dans une sauvegarde de base, il rouvrirait le document.
    $brut = (string) DB::table('project_task_documents')->where('id', $doc->id)->value('docspace_request_token');
    expect($brut)->not->toBe('JETON-EDITION-A')->and($brut)->not->toBeEmpty()
        ->and($doc->fresh()->docspace_request_token)->toBe('JETON-EDITION-A');
});

it('⚠️ refuse de créer un document tant que DocSpace n’est pas configuré', function () {
    $event = openEdition();
    $redacteur = staffProjet($event);
    $task = tacheProjet($event);
    $task->collaborators()->sync([$redacteur->id]);
    Config::set('services.docspace.url', null);
    Http::preventStrayRequests();

    // ⚠️ Depuis le retrait de l'éditeur texte, DocSpace n'est plus une option :
    // sans lui il n'y a PAS de document de tâche, et l'écran doit le dire.
    $this->actingAs($redacteur, 'web')
        ->post(route('admin.project.documents.store', $task), ['title' => 'Note', 'format' => 'docx'])
        ->assertSessionHasErrors('file');

    expect($task->documents()->count())->toBe(0);
    Http::assertNothingSent();
});

it('⚠️ refuse de créer un document tant que les liens d’édition ne sont pas autorisés', function () {
    $event = openEdition();
    $redacteur = staffProjet($event);
    $task = tacheProjet($event);
    $task->collaborators()->sync([$redacteur->id]);
    reglagesDocSpace(liens: false);
    Http::preventStrayRequests();

    $this->actingAs($redacteur, 'web')
        ->post(route('admin.project.documents.store', $task), ['title' => 'Note', 'format' => 'xlsx'])
        ->assertSessionHasErrors('file');

    // ⚠️ Le refus tombe AVANT le premier appel : un fichier créé puis privé de
    // lien resterait orphelin chez le prestataire.
    Http::assertNothingSent();
});

it('⚠️ refuse un document sans format : il n’y a plus de document texte', function () {
    $event = openEdition();
    $redacteur = staffProjet($event);
    $task = tacheProjet($event);
    $task->collaborators()->sync([$redacteur->id]);
    reglagesDocSpace();
    Http::preventStrayRequests();

    // ⚠️ L'éditeur Tiptap/Yjs a été retiré : un document sans format n'aurait
    // plus aucun éditeur, et la page ne saurait que le dire en lecture seule.
    $this->actingAs($redacteur, 'web')
        ->post(route('admin.project.documents.store', $task), ['title' => 'Note libre'])
        ->assertSessionHasErrors('file');

    expect($task->documents()->count())->toBe(0);
});

it('importe un fichier Word dans DocSpace et ne garde AUCUNE copie locale', function () {
    $event = openEdition();
    $redacteur = staffProjet($event);
    $task = tacheProjet($event);
    $task->collaborators()->sync([$redacteur->id]);
    reglagesDocSpace();
    faireRepondreDocSpace();

    $this->actingAs($redacteur, 'web')
        ->post(route('admin.project.documents.store', $task), [
            'file' => UploadedFile::fake()->createWithContent('Plan de travail.docx', docxMinimal()),
        ])->assertRedirect();

    $doc = $task->documents()->firstOrFail();
    expect($doc->title)->toBe('Plan de travail')   // le nom du fichier sert de titre, sans son extension
        ->and($doc->docspace_file_id)->toBe('9001')
        // ⚠️ Le fichier vit chez le prestataire, et seulement là : plus aucun
        // chemin n'écrit `file_path`, qui ne sert qu'aux anciens documents.
        ->and($doc->file_path)->toBeNull();
});

it('refuse un fichier qui n’est pas un vrai document Office', function () {
    $event = openEdition();
    $redacteur = staffProjet($event);
    $task = tacheProjet($event);
    $task->collaborators()->sync([$redacteur->id]);
    reglagesDocSpace();
    Http::preventStrayRequests();

    $this->actingAs($redacteur, 'web')
        ->post(route('admin.project.documents.store', $task), [
            'file' => UploadedFile::fake()->createWithContent('piege.docx', 'ceci n’est pas un zip OOXML'),
        ])->assertSessionHasErrors('file');

    expect($task->documents()->count())->toBe(0);
    Http::assertNothingSent();
});

it('⚠️ ne remet le jeton d’édition QU’AUX rédacteurs, et jamais la clé de l’API', function () {
    $event = openEdition();
    $lecteur = staffProjet($event);
    $redacteur = staffProjet($event);
    $task = tacheProjet($event);
    $task->collaborators()->sync([$redacteur->id]);
    $doc = documentDocSpace($task);
    reglagesDocSpace();
    Http::fake();

    $pageRedacteur = $this->actingAs($redacteur, 'web')
        ->get(route('admin.project.documents.edit', [$task, $doc]))->assertOk();
    $pageRedacteur->assertSee('JETON-EDITION-A');
    $pageRedacteur->assertDontSee(DS_CLE);

    // ⚠️ Le jeton donne l'ÉCRITURE chez le prestataire, et il est partagé par
    // tous les rédacteurs : un lecteur qui le recevrait écrirait hors de GEVES.
    $pageLecteur = $this->actingAs($lecteur, 'web')
        ->get(route('admin.project.documents.edit', [$task, $doc]))->assertOk();
    $pageLecteur->assertDontSee('JETON-EDITION-A');
    $pageLecteur->assertDontSee(DS_CLE);
    $pageLecteur->assertSee('Télécharger le fichier');
});

it('⚠️ donne son NOM à l’éditeur au lieu de le lui demander', function () {
    $event = openEdition();
    $redacteur = staffProjet($event);
    $task = tacheProjet($event);
    $task->collaborators()->sync([$redacteur->id]);
    $doc = documentDocSpace($task);
    reglagesDocSpace();
    Http::fake();

    $config = $this->actingAs($redacteur, 'web')
        ->get(route('admin.project.documents.edit', [$task, $doc]))->assertOk()
        ->viewData('docspaceConfig');

    // ⚠⚠ **UN LIEN DE PARTAGE EST ANONYME** : constaté à l'écran contre le vrai
    // service, l'éditeur barrait le document d'une boîte « Entrez un nom à utiliser
    // pour la collaboration » à chaque ouverture - alors que GEVES sait qui entre.
    expect($config['editorCustomization']['anonymous']['request'])->toBeFalse()
        ->and($config['editorCustomization']['anonymous']['label'])
        ->toBe($redacteur->salutationName() ?: $redacteur->name);
});

it('⚠️ n’offre le renouvellement du lien qu’à l’administrateur', function () {
    $event = openEdition();
    $redacteur = staffProjet($event);
    $task = tacheProjet($event);
    $task->collaborators()->sync([$redacteur->id]);
    $doc = documentDocSpace($task);
    reglagesDocSpace();
    Http::fake();

    // ⚠️ Un bouton absent n'a jamais protégé personne - le serveur refuse de
    // toute façon - mais offrir un geste qui sera refusé est un piège.
    $this->actingAs($redacteur, 'web')
        ->get(route('admin.project.documents.edit', [$task, $doc]))->assertOk()
        ->assertDontSee('Renouveler le lien');

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.project.documents.edit', [$task, $doc]))->assertOk()
        ->assertSee('Renouveler le lien d’édition');
});

it('renouvelle le lien - réservé à l’administrateur, et seulement après vérification du nouveau', function () {
    $event = openEdition();
    $redacteur = staffProjet($event);
    $task = tacheProjet($event);
    $task->collaborators()->sync([$redacteur->id]);
    $doc = documentDocSpace($task);
    reglagesDocSpace();

    // ⚠️ `Http::fake()` AJOUTE ses motifs au lieu de les remplacer : un appel sans
    // argument poserait un fourre-tout `*` qui coifferait les motifs posés ensuite.
    Http::preventStrayRequests();

    $this->actingAs($redacteur, 'web')
        ->post(route('admin.project.documents.rotate-link', [$task, $doc]))->assertForbidden();
    Http::assertNothingSent();

    faireTournerLeLien('JETON-EDITION-A', 'JETON-EDITION-B');

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.project.documents.rotate-link', [$task, $doc]))
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($doc->fresh()->docspace_request_token)->toBe('JETON-EDITION-B');

    // ⚠️ L'ordre est la règle : vérifier le nouveau lien AVANT de couper l'ancien,
    // sinon un échec laisse le document inaccessible à toute l'équipe.
    $appels = collect(Http::recorded())->map(fn ($paire) => $paire[0]->url())->all();
    $verification = array_search(true, array_map(fn ($u) => str_contains($u, 'JETON-EDITION-B'), $appels), true);
    expect($verification)->not->toBeFalse()->and($verification)->toBeLessThan(count($appels) - 1);
});

it('⚠️⚠️ RENOUVELLE LE LIEN D’OFFICE quand une personne cesse d’être associée', function () {
    $event = openEdition();
    $staff = adminStaff();
    $parti = staffProjet($event);
    $reste = staffProjet($event);
    $task = tacheProjet($event, $staff);
    $task->collaborators()->sync([$parti->id, $reste->id]);
    $doc = documentDocSpace($task);
    reglagesDocSpace();
    faireTournerLeLien('JETON-EDITION-A', 'JETON-APRES-RETRAIT');

    $this->actingAs($staff, 'web')->putJson(route('admin.project.tasks.update', $task), [
        'event_id' => $event->id,
        'title' => $task->title,
        'status' => 'doing',
        'priority' => 'normal',
        'collaborator_ids' => [$reste->id],
    ])->assertOk();

    // ⚠️⚠️ **RETIRER QUELQU'UN D'UNE TÂCHE NE LUI RETIRAIT PAS LE FICHIER** : le
    // jeton est partagé et vaut un droit d'écriture hors de GEVES. Sans ce
    // renouvellement, la personne écartée gardait le document ouvert.
    expect($doc->fresh()->docspace_request_token)->toBe('JETON-APRES-RETRAIT');
});

it('ne renouvelle RIEN quand on ajoute quelqu’un', function () {
    $event = openEdition();
    $staff = adminStaff();
    $present = staffProjet($event);
    $nouveau = staffProjet($event);
    $task = tacheProjet($event, $staff);
    $task->collaborators()->sync([$present->id]);
    $doc = documentDocSpace($task);
    reglagesDocSpace();
    Http::preventStrayRequests();

    $this->actingAs($staff, 'web')->putJson(route('admin.project.tasks.update', $task), [
        'event_id' => $event->id,
        'title' => $task->title,
        'status' => 'doing',
        'priority' => 'normal',
        'collaborator_ids' => [$present->id, $nouveau->id],
    ])->assertOk();

    // Personne n'a perdu l'accès : couper le lien ferait sortir toute l'équipe
    // du document pour une arrivée.
    Http::assertNothingSent();
    expect($doc->fresh()->docspace_request_token)->toBe('JETON-EDITION-A');
});

it('télécharge le fichier depuis DocSpace pour qui accède au projet', function () {
    $event = openEdition();
    $lecteur = staffProjet($event);
    $task = tacheProjet($event);
    $doc = documentDocSpace($task);
    reglagesDocSpace();

    Http::fake([
        DS_URL.'/api/2.0/files/file/9001/presigneduri' => Http::response(['response' => DS_URL.'/filehandler?jeton=x']),
        DS_URL.'/filehandler*' => Http::response('PK-le-contenu-du-docx'),
    ]);

    $reponse = $this->actingAs($lecteur, 'web')
        ->get(route('admin.project.documents.download', [$task, $doc]))->assertOk();

    expect($reponse->getContent())->toBe('PK-le-contenu-du-docx');
    $reponse->assertDownload('Note de cadrage.docx');
});

it('⚠️ n’accepte pas une adresse de téléchargement étrangère à DocSpace', function () {
    $event = openEdition();
    $task = tacheProjet($event);
    $doc = documentDocSpace($task);
    reglagesDocSpace();

    // ⚠️ L'adresse vient du prestataire : suivie sans contrôle, elle ferait de
    // GEVES un relais vers n'importe quel hôte (requête côté serveur).
    Http::fake([
        DS_URL.'/api/2.0/files/file/9001/presigneduri' => Http::response(['response' => 'https://ailleurs.test/secret']),
        '*' => Http::response('ne doit jamais partir'),
    ]);

    $this->actingAs(staffProjet($event), 'web')
        ->get(route('admin.project.documents.download', [$task, $doc]))->assertStatus(500);

    collect(Http::recorded())->each(fn ($paire) => expect($paire[0]->url())->toStartWith(DS_URL));
});

it('supprime le fichier chez DocSpace en même temps que le document', function () {
    $event = openEdition();
    $redacteur = staffProjet($event);
    $task = tacheProjet($event);
    $task->collaborators()->sync([$redacteur->id]);
    $doc = documentDocSpace($task);
    reglagesDocSpace();
    Http::fake([DS_URL.'/api/2.0/files/file/9001' => Http::response(['response' => []])]);

    $this->actingAs($redacteur, 'web')
        ->delete(route('admin.project.documents.destroy', [$task, $doc]))->assertRedirect();

    expect(ProjectTaskDocument::find($doc->id))->toBeNull();
    Http::assertSent(fn ($r) => $r->method() === 'DELETE' && $r->url() === DS_URL.'/api/2.0/files/file/9001');
});

it('⚠️ CONSERVE le document quand DocSpace refuse de retirer le fichier, et le dit', function () {
    $event = openEdition();
    $redacteur = staffProjet($event);
    $task = tacheProjet($event);
    $task->collaborators()->sync([$redacteur->id]);
    $doc = documentDocSpace($task);
    reglagesDocSpace();
    Http::fake([DS_URL.'/api/2.0/files/file/9001' => Http::response(['erreur' => 'non'], 500)]);

    // ⚠️ Supprimer la ligne d'abord laisserait chez le prestataire un fichier
    // que plus rien ne référence, avec son lien d'édition toujours vivant -
    // donc plus personne pour le révoquer. On garde le document et on explique.
    $this->actingAs($redacteur, 'web')
        ->delete(route('admin.project.documents.destroy', [$task, $doc]))
        ->assertRedirect()->assertSessionHasErrors('docspace');

    expect(ProjectTaskDocument::find($doc->id))->not->toBeNull();
});

it('⚠️⚠️ SUPPRIMER LA TÂCHE emporte ses fichiers ET son dossier chez DocSpace', function () {
    $event = openEdition();
    $staff = adminStaff();
    $task = tacheProjet($event, $staff);
    $task->forceFill(['docspace_folder_id' => '5001'])->save();
    documentDocSpace($task, '9001');
    documentDocSpace($task, '9002');
    reglagesDocSpace();
    Http::fake([DS_URL.'/api/2.0/files/*' => Http::response(['response' => []])]);

    $this->actingAs($staff, 'web')
        ->deleteJson(route('admin.project.tasks.destroy', $task))->assertOk();

    // ⚠️⚠️ La clé étrangère est en CASCADE : les lignes de documents partent au
    // niveau de la base, sans passer par le modèle. Sans ce ménage, chaque DOCX,
    // son dossier ET son lien d'édition resteraient vivants chez le prestataire,
    // sans plus rien dans GEVES pour les retrouver - donc pour les révoquer.
    $appels = collect(Http::recorded())
        ->filter(fn ($p) => $p[0]->method() === 'DELETE')
        ->map(fn ($p) => $p[0]->url())->values()->all();

    expect($appels)->toContain(DS_URL.'/api/2.0/files/file/9001')
        ->and($appels)->toContain(DS_URL.'/api/2.0/files/file/9002')
        ->and($appels)->toContain(DS_URL.'/api/2.0/files/folder/5001')
        ->and(ProjectTask::find($task->id))->toBeNull();
});

it('le gestionnaire de l’événement retire un document qu’il n’a pas rédigé', function () {
    $event = openEdition();
    $gerant = User::factory()->create(['access_level' => User::ACCESS_USER]);
    $event->team()->attach($gerant->id, ['role' => 'gerant']);
    $task = tacheProjet($event);
    $doc = documentDocSpace($task);
    reglagesDocSpace();
    Http::fake([DS_URL.'/api/2.0/files/file/9001' => Http::response(['response' => []])]);

    // ⚠️ Il n'est pas associé à la tâche, donc il n'écrit pas dedans - mais il
    // répond du plan de travail, et un document de trop doit pouvoir partir.
    expect($doc->isEditableBy($gerant))->toBeFalse()
        ->and($doc->isDeletableBy($gerant))->toBeTrue();

    $this->actingAs($gerant, 'web')
        ->delete(route('admin.project.documents.destroy', [$task, $doc]))->assertRedirect();
});

it('renommer un document reprend le titre chez DocSpace', function () {
    $event = openEdition();
    $redacteur = staffProjet($event);
    $task = tacheProjet($event);
    $task->collaborators()->sync([$redacteur->id]);
    $doc = documentDocSpace($task);
    reglagesDocSpace();
    Http::fake([DS_URL.'/api/2.0/files/file/9001' => Http::response(['response' => []])]);

    $this->actingAs($redacteur, 'web')
        ->putJson(route('admin.project.documents.rename', [$task, $doc]), ['title' => 'Note revue'])
        ->assertOk();

    // ⚠️ Le titre de GEVES fait foi, mais sans ce rappel on chercherait « Note
    // revue » dans une salle qui affiche encore l'ancien nom.
    expect($doc->fresh()->title)->toBe('Note revue');
    Http::assertSent(fn ($r) => $r->method() === 'PUT'
        && $r->url() === DS_URL.'/api/2.0/files/file/9001'
        && ($r->data()['title'] ?? null) === 'Note revue.docx');
});

describe('formes de reponse du lien DocSpace', function () {
    it('⚠️ lit le jeton quelle que soit la forme, et échoue FERMÉ sinon', function (array $reponse, ?string $attendu) {
        $event = openEdition();
        $redacteur = staffProjet($event);
        $task = tacheProjet($event);
        $task->collaborators()->sync([$redacteur->id]);
        reglagesDocSpace();

        Http::fake([
            DS_URL.'/api/2.0/files/folder/4706751' => Http::response(['response' => ['id' => 5001]]),
            DS_URL.'/api/2.0/files/5001/file' => Http::response(['response' => ['id' => 9001]]),
            DS_URL.'/api/2.0/files/file/9001/link' => Http::response(['response' => $reponse]),
            DS_URL.'/api/2.0/files/share/*' => Http::response(['response' => ['status' => 0, 'id' => 9001]]),
            DS_URL.'/api/2.0/files/file/9001' => Http::response(['response' => []]),
        ]);

        $this->actingAs($redacteur, 'web')
            ->post(route('admin.project.documents.store', $task), ['title' => 'Note', 'format' => 'docx']);

        expect($task->documents()->first()?->docspace_request_token)->toBe($attendu);
    })->with([
        'jeton sous sharedLink' => [
            ['access' => 10, 'sharedLink' => ['id' => 'l1', 'requestToken' => 'TOK-1']], 'TOK-1',
        ],
        'jeton sous sharedTo' => [
            ['access' => 10, 'sharedTo' => ['id' => 'l2', 'requestToken' => 'TOK-2']], 'TOK-2',
        ],
        'jeton a la racine' => [
            ['access' => 10, 'id' => 'l3', 'requestToken' => 'TOK-3'], 'TOK-3',
        ],
        // ⚠️ `sharedTo.shareLink` est tantot un objet, tantot la CHAINE de
        // l'adresse : indexer une chaine par un nom ne rend rien, et le jeton se
        // lit alors dans sa requete.
        'jeton dans l adresse de partage' => [
            ['access' => 10, 'sharedTo' => ['id' => 'l4', 'shareLink' => DS_URL.'/s/abc?share=TOK-4']], 'TOK-4',
        ],
        // ⚠️ Forme inconnue : on ne DEVINE pas un jeton. Aucun document créé.
        'forme inconnue' => [['access' => 10, 'autre' => 'chose'], null],
    ]);
});

describe('TaskDocSpace::validFile', function () {
    it('reconnaît un DOCX et refuse ce qui n’en est pas un', function () {
        $docspace = new TaskDocSpace;
        $docx = tempnam(sys_get_temp_dir(), 'geves_test_');
        file_put_contents($docx, docxMinimal());
        $texte = tempnam(sys_get_temp_dir(), 'geves_test_');
        file_put_contents($texte, 'du texte, pas une archive');

        try {
            expect($docspace->validFile($docx, 'docx'))->toBeTrue()
                // ⚠️ Un DOCX n'est pas un XLSX : la pièce attendue diffère dans l'archive.
                ->and($docspace->validFile($docx, 'xlsx'))->toBeFalse()
                ->and($docspace->validFile($texte, 'docx'))->toBeFalse();
        } finally {
            @unlink($docx);
            @unlink($texte);
        }
    });
});
