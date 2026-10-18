<?php

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Formation;
use App\Models\Media;
use App\Models\Submission;
use App\Models\User;
use App\Support\EventStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * **Les fichiers se rangent sous le NOM de leur événement.**
 *
 * Ils vivaient sous son identifiant - `submissions/2/…`, `events/2/…` - et
 * l'habillage à plat dans `events/`. Un dossier nommé `2` n'apprend rien à qui
 * ouvre le disque, et deux événements mêlaient leurs visuels dans le même
 * répertoire.
 *
 * ⚠️ Ces cas portent sur le CHEMIN D'ÉCRITURE et sur la migration qui a rangé
 * l'existant. Ce qui est déjà enregistré en base ne se recalcule jamais à la
 * lecture : le slug est modifiable, et un dossier déduit après un renommage
 * pointerait dans le vide (cf. {@see EventStorage}).
 */

// openEdition(), verifiedAccount(), adminStaff(), storagePath() -> tests/Pest.php

/** Un événement dont le slug ne porte PAS l'année. */
function evenementSansAnnee(): Event
{
    $series = EventSeries::create(['name' => 'Série sans année', 'slug' => 'serie-sans-annee']);

    return $series->events()->create([
        'title' => 'Journées de la recherche',
        'year' => 2027,
        'status' => 'open',
        'slug' => 'journees-recherche',
        'submission_open' => true,
        'settings' => ['tracks' => ['Sciences', 'Santé'], 'max_communications' => 3],
    ]);
}

/** @return array{0: User, 1: Submission} */
function communicationDeposee(Event $event): array
{
    $account = verifiedAccount();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);
    $submission = $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-CL', 'title' => 'Communication',
        'abstract' => 'Résumé.', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted', 'authors' => [],
    ]);

    return [$account, $submission];
}

/**
 * La migration de rangement. ⚠️ Chargée UNE fois : un `require` déjà exécuté
 * rend `true` et non l'objet, et le second appel du cas de rejeu tomberait sur
 * un booléen.
 */
function migrationDeRangement(): object
{
    static $migration = null;

    if ($migration === null) {
        $migration = require database_path('migrations/2026_10_17_000100_classer_les_fichiers_par_evenement.php');
    }

    return $migration;
}

/**
 * ⚠️ **L'année n'est ajoutée que si le nom ne la porte pas déjà.** Les deux
 * colloques de la base la portent dans leur slug : ajouter `year` sans regarder
 * donnait `avenir-durable-2025-2026`, deux millésimes contradictoires dans le
 * même nom de dossier.
 */
it('nomme le dossier d’après le slug, l’année ajoutée une seule fois', function () {
    expect(EventStorage::folder('journees-recherche', 2027))->toBe('journees-recherche-2027');

    // Le slug porte DÉJÀ une année - et pas forcément celle de la colonne.
    expect(EventStorage::folder('avenir-durable-2025', 2026))->toBe('avenir-durable-2025');
    expect(EventStorage::folder('premier-colloque-eau-togo-2026', 2026))->toBe('premier-colloque-eau-togo-2026');

    // Sans année connue, le nom seul.
    expect(EventStorage::folder('colloque-eau', 0))->toBe('colloque-eau');
    expect(EventStorage::folder('colloque-eau', null))->toBe('colloque-eau');

    // Un slug mal formé est normalisé : jamais d'espace ni d'accent dans un chemin.
    expect(EventStorage::folder('Journées de la Recherche', 2027))->toBe('journees-de-la-recherche-2027');
});

/**
 * ⚠️ **Jamais de segment VIDE.** Il écrirait dans le dossier parent, et tous les
 * événements mêleraient leurs fichiers - exactement ce qu'on vient de défaire.
 * Repli sur le titre, puis sur l'identifiant, qui existe toujours.
 */
it('ne rend jamais un dossier vide', function () {
    $event = evenementSansAnnee();

    expect(EventStorage::folder($event))->toBe('journees-recherche-2027');

    $event->update(['slug' => '']);
    expect(EventStorage::folder($event->fresh()))->toBe('journees-de-la-recherche-2027');

    $event->update(['title' => '']);
    expect(EventStorage::folder($event->fresh()))->toBe('evenement-'.$event->id.'-2027');

    expect(EventStorage::submissions($event->fresh()))->toStartWith('submissions/');
    expect(EventStorage::artwork($event->fresh()))->toStartWith('events/');
    expect(EventStorage::folder(''))->toBe('evenement');
});

it('dépose la pièce jointe d’une communication sous le nom de l’événement', function () {
    Storage::fake('public');
    $event = evenementSansAnnee();
    $account = verifiedAccount();
    $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Communication rangée',
            'abstract' => str_repeat('Contexte et objectifs. ', 5),
            'track' => 'Sciences',
            'type' => 'oral',
            'keywords' => 'rangement, classement',
            'file' => UploadedFile::fake()->create('resume.pdf', 80, 'application/pdf'),
        ])
        ->assertSessionHasNoErrors();

    $submission = Submission::where('event_id', $event->id)->latest('id')->firstOrFail();

    expect($submission->file_url)->toContain('submissions/journees-recherche-2027/');
    expect($submission->file_url)->not->toContain('submissions/'.$event->id.'/');
    Storage::disk('public')->assertExists(storagePath($submission->file_url));
});

it('téléverse l’habillage sous le nom de l’événement', function () {
    Storage::fake('public');
    $event = evenementSansAnnee();

    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.evenements.update', $event), [
            'title' => $event->title,
            'status' => 'open',
            'event_series_id' => $event->event_series_id,
            'max_communications' => 3,
            'slug' => $event->slug,
            'year' => $event->year,
            'hero_image_file' => UploadedFile::fake()->image('hero.jpg', 1920, 800),
            'banner_image_file' => UploadedFile::fake()->image('banniere.jpg', 1680, 720),
        ])
        ->assertSessionHasNoErrors();

    $event->refresh();

    foreach ([$event->settings['hero_image'], $event->banner_url] as $url) {
        expect($url)->toContain('events/journees-recherche-2027/');
        Storage::disk('public')->assertExists(storagePath($url));
    }
});

/**
 * ⚠️ **Un fichier déplacé sans son URL est un fichier perdu.** La migration
 * déplace ET réécrit, dans le même passage. Elle abandonne au passage les
 * segments intermédiaires, qui redisaient l'événement : l'import de l'eau
 * rangeait sous `submissions/2/eau-togo-2026/`, ce qui aurait donné
 * `submissions/premier-colloque-eau-togo-2026/eau-togo-2026/` - et le prochain
 * passage du seeder n'y aurait pas retrouvé ses pièces.
 */
it('déplace les fichiers existants et réécrit les URL enregistrées', function () {
    Storage::fake('public');
    $disque = Storage::disk('public');
    $event = evenementSansAnnee();
    [, $submission] = communicationDeposee($event);

    // L'ancienne forme : sous l'identifiant, avec un sous-dossier redondant.
    // ⚠️ La « version définitive » est sortie du processus (la demande de
    // correction la remplace), mais la migration doit CONTINUER à déplacer
    // celles déjà déposées : un fichier qu'on cesse de suivre est un fichier
    // perdu, et c'est en production qu'on s'en apercevrait.
    $disque->put('submissions/'.$event->id.'/import-2027/26-CL.docx', 'resume');
    $disque->put('submissions/'.$event->id.'/camera-ready/final.pdf', 'definitive');
    $disque->put('events/'.$event->id.'/import-2027/affiche.jpeg', 'affiche');

    $hote = 'https://geves.test/storage/';
    $submission->update([
        'file_url' => $hote.'submissions/'.$event->id.'/import-2027/26-CL.docx',
        'meta' => ['camera_ready' => ['file_url' => $hote.'submissions/'.$event->id.'/camera-ready/final.pdf']],
    ]);
    $media = Media::create([
        'event_id' => $event->id, 'type' => 'image', 'title' => 'Affiche', 'ordre' => 1,
        'url' => $hote.'events/'.$event->id.'/import-2027/affiche.jpeg',
    ]);

    migrationDeRangement()->up();

    $submission->refresh();

    // Les URL pointent sur le dossier nommé, et l'hôte est conservé.
    expect($submission->file_url)->toBe($hote.'submissions/journees-recherche-2027/26-CL.docx');
    expect($submission->meta['camera_ready']['file_url'])
        ->toBe($hote.'submissions/journees-recherche-2027/camera-ready/final.pdf');
    expect($media->fresh()->url)->toBe($hote.'events/journees-recherche-2027/affiche.jpeg');

    // Et les fichiers ont vraiment suivi.
    foreach ([
        'submissions/journees-recherche-2027/26-CL.docx' => 'resume',
        'submissions/journees-recherche-2027/camera-ready/final.pdf' => 'definitive',
        'events/journees-recherche-2027/affiche.jpeg' => 'affiche',
    ] as $chemin => $contenu) {
        $disque->assertExists($chemin);
        expect($disque->get($chemin))->toBe($contenu);
    }

    $disque->assertMissing('submissions/'.$event->id.'/import-2027/26-CL.docx');
    $disque->assertMissing('events/'.$event->id.'/import-2027/affiche.jpeg');
});

/** Rejouée, elle ne défait rien et ne déplace rien deux fois. */
it('se rejoue sans rien casser', function () {
    Storage::fake('public');
    $disque = Storage::disk('public');
    $event = evenementSansAnnee();
    [, $submission] = communicationDeposee($event);

    $hote = 'https://geves.test/storage/';
    $disque->put('submissions/'.$event->id.'/26-CL.docx', 'resume');
    $submission->update(['file_url' => $hote.'submissions/'.$event->id.'/26-CL.docx']);

    migrationDeRangement()->up();
    $apres = $submission->fresh()->file_url;

    migrationDeRangement()->up();

    expect($submission->fresh()->file_url)->toBe($apres);
    $disque->assertExists('submissions/journees-recherche-2027/26-CL.docx');
    expect($disque->get('submissions/journees-recherche-2027/26-CL.docx'))->toBe('resume');
});

/**
 * ⚠️ **Un fichier introuvable des deux côtés ne se réécrit pas.** C'est le cas
 * courant d'une base dont les téléversements n'ont pas été reprise : corriger
 * l'URL ferait pointer la base vers un fichier qui n'existe nulle part, et on
 * perdrait l'adresse d'origine - la seule piste qui reste pour le retrouver.
 */
it('laisse l’URL intacte quand le fichier est introuvable', function () {
    Storage::fake('public');
    $event = evenementSansAnnee();
    [, $submission] = communicationDeposee($event);

    $url = 'https://geves.test/storage/submissions/'.$event->id.'/jamais-televerse.docx';
    $submission->update(['file_url' => $url]);

    migrationDeRangement()->up();

    expect($submission->fresh()->file_url)->toBe($url);
});

/**
 * ⚠️ **Le déplacement est piloté par la BASE, jamais par le contenu des
 * dossiers.** Il restait un `events/1/` peuplé des affiches du colloque de
 * l'eau, du temps où cet événement portait l'identifiant 1 : déplacer les
 * répertoires par leur nom aurait rangé ces affiches sous le dossier de
 * l'événement 1 d'aujourd'hui, qui n'a rien à voir. Un orphelin se constate, il
 * ne se devine pas.
 */
it('ne touche pas un dossier qu’aucune ligne ne réclame', function () {
    Storage::fake('public');
    $disque = Storage::disk('public');
    $event = evenementSansAnnee();

    // ⚠️ Le dossier porte un identifiant qui EXISTE - c'est tout le piège. Sur la
    // base de développement, un `events/1/` peuplé des affiches du colloque de
    // l'eau avait survécu au temps où cet événement portait l'identifiant 1 :
    // un rangement qui lit les répertoires range ces affiches sous l'événement 1
    // d'aujourd'hui, qui n'a rien à voir avec elles. Aucune ligne ne les
    // réclamant, elles ne bougent pas.
    $orphelin = 'events/'.$event->id.'/affiche-d-un-autre.jpeg';
    $disque->put($orphelin, 'orpheline');

    expect(Media::where('event_id', $event->id)->count())->toBe(0);

    migrationDeRangement()->up();

    $disque->assertExists($orphelin);
    expect($disque->get($orphelin))->toBe('orpheline');
    $disque->assertMissing('events/journees-recherche-2027/affiche-d-un-autre.jpeg');
});

/**
 * ⚠️ **Le flyer d'une formation et les fichiers du livre des résumés sont des
 * CHEMINS NUS** en base, pas des URL : ils relèvent pourtant d'un événement, et
 * vivaient à plat dans `formations/`, `livret/` et `livret-templates/`.
 */
it('range le flyer d’une formation sous le nom de l’événement', function () {
    Storage::fake('public');
    $event = evenementSansAnnee();
    $formation = Formation::create([
        'event_id' => $event->id, 'title' => 'Méthodologie', 'fee' => 0, 'published' => true,
    ]);

    $this->actingAs(adminStaff(), 'web')->put(route('admin.formations.update', $formation), [
        'title' => 'Méthodologie', 'fee' => 0,
        'flyer' => UploadedFile::fake()->image('flyer.jpg'),
    ]);

    $chemin = (string) $formation->fresh()->flyer_path;

    expect($chemin)->toStartWith('formations/journees-recherche-2027/');
    Storage::disk('public')->assertExists($chemin);
});

it('déplace aussi les chemins nus : flyer et fichiers du livre', function () {
    Storage::fake('public');
    $disque = Storage::disk('public');
    $event = evenementSansAnnee();

    $formation = Formation::create([
        'event_id' => $event->id, 'title' => 'Méthodologie', 'fee' => 0, 'published' => true,
        'flyer_path' => 'formations/ancien-flyer.jpg',
    ]);
    $disque->put('formations/ancien-flyer.jpg', 'flyer');
    $disque->put('livret/couverture.png', 'logo');
    $disque->put('livret-templates/modele.docx', 'gabarit');

    $event->update(['settings' => array_merge($event->settings ?? [], [
        'livret.cover_logo' => 'livret/couverture.png',
        'livret.templates' => [['name' => 'modele.docx', 'path' => 'livret-templates/modele.docx']],
    ])]);

    migrationDeRangement()->up();

    expect($formation->fresh()->flyer_path)->toBe('formations/journees-recherche-2027/ancien-flyer.jpg');

    $settings = $event->fresh()->settings;
    expect($settings['livret.cover_logo'])->toBe('livret/journees-recherche-2027/couverture.png');
    expect($settings['livret.templates'][0]['path'])->toBe('livret-templates/journees-recherche-2027/modele.docx');

    foreach ([
        'formations/journees-recherche-2027/ancien-flyer.jpg' => 'flyer',
        'livret/journees-recherche-2027/couverture.png' => 'logo',
        'livret-templates/journees-recherche-2027/modele.docx' => 'gabarit',
    ] as $chemin => $contenu) {
        $disque->assertExists($chemin);
        expect($disque->get($chemin))->toBe($contenu);
    }
});
