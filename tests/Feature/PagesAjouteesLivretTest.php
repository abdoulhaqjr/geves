<?php

use App\Documents\DocxCoverMerger;
use App\Documents\LivretAssembly;
use App\Documents\LivretDocx;
use App\Documents\LivretPages;
use App\Documents\LivretPdf;
use App\Models\Event;
use App\Support\Settings;
use App\Support\TaskDocSpace;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Writer\Word2007;
use setasign\Fpdi\Fpdi;

/*
|--------------------------------------------------------------------------
| Pages ajoutées au livre des résumés
|--------------------------------------------------------------------------
|
| Le gestionnaire compose une page dans l'éditeur Word, puis dit où elle se
| pose. Tout l'enjeu est qu'elle atterrisse au MÊME endroit dans le PDF et
| dans le gabarit Word, et qu'elle ne décale pas la pagination du livre.
|
*/

/** Un PDF de N pages, pour tenir lieu de document converti par DocSpace. */
function pdfDeNPages(int $pages): string
{
    $pdf = new FPDF;
    for ($i = 0; $i < $pages; $i++) {
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 16);
        $pdf->Cell(0, 10, 'PAGE AJOUTEE '.($i + 1));
    }

    return $pdf->Output('S');
}

function nombreDePages(string $binaire): int
{
    $chemin = tempnam(sys_get_temp_dir(), 'test-pdf-');
    try {
        file_put_contents($chemin, $binaire);

        return (new Fpdi)->setSourceFile($chemin);
    } finally {
        @unlink($chemin);
    }
}

/** Un DocSpace qui rend toujours le même document, sans réseau. */
function docspaceQuiRend(string $binaire): TaskDocSpace
{
    return new class($binaire) extends TaskDocSpace
    {
        public function __construct(private string $binaire) {}

        public function configured(?Event $event = null): bool
        {
            return true;
        }

        public function fileLinksEnabled(): bool
        {
            return true;
        }

        public function roomId(?Event $event = null): string
        {
            return '1';
        }

        public function createFile(string $folderId, string $title, string $format): string
        {
            return 'fichier-'.$title;
        }

        public function createFileEditLink(string $fileId): string
        {
            return 'jeton-'.$fileId;
        }

        public function saveAsPdf(string $fileId, string $folderId): string
        {
            return 'converti';
        }

        public function downloadFile(string $fileId): string
        {
            return $this->binaire;
        }

        public function deleteFile(string $fileId): void {}
    };
}

/** @param array<string, mixed> $attributs */
function pageAjoutee(Event $event, array $attributs = []): array
{
    $page = array_merge([
        'id' => LivretPages::newId(),
        'title' => 'Mot du recteur',
        'file_id' => '42',
        'token' => LivretPages::encryptedToken('jeton'),
        'mode' => 'section',
        'anchor' => 'resumes',
        'position' => 'avant',
        'active' => true,
    ], $attributs);

    LivretPages::save($event, array_merge(LivretPages::all($event), [$page]));

    return LivretPages::find($event, $page['id']) ?? [];
}

it('RIEN ne passe devant la couverture', function () {
    // ⚠️⚠️ « Avant la page de garde » ferait de la page ajoutée la première page
    // du livre - et la couverture DocSpace, qui prend la place de la PREMIÈRE
    // page, s'écraserait sur elle. Une page de garde qui n'est plus la garde
    // n'est plus une garde. Le modèle le force, l'écran ne fait que le refléter.
    $event = openEdition();
    $page = pageAjoutee($event, ['anchor' => 'garde', 'position' => 'avant']);

    expect($page['position'])->toBe('apres')
        ->and(LivretPages::slot($page))->toBe('apres:garde');
});

it('ne prend JAMAIS les pages d’un autre événement', function () {
    // ⚠️⚠️ `EventSettings::get` retombe sur le réglage GLOBAL quand l'événement
    // n'a rien : des pages déposées une fois au niveau plateforme entreraient
    // dans le livre de TOUS les événements qui n'en ont pas. Un livre ne se
    // remplit pas des pages d'un autre colloque - d'où la lecture directe.
    Settings::set('livret.pages', [[
        'id' => 'intrus', 'title' => 'Page d’un autre colloque', 'file_id' => '9',
    ]]);

    expect(LivretPages::all(openEdition()))->toBe([]);
});

it('borne le numéro de page à partir de 2', function () {
    // La page 1 est la couverture : une page « en page 1 » la repousserait sans
    // que personne ne l'ait demandé.
    $event = openEdition();
    $page = pageAjoutee($event, ['mode' => 'page', 'page' => 1]);

    expect($page['page'])->toBe(2)
        ->and(LivretPages::slot($page))->toBe('')
        ->and(LivretPages::placementLabel($page))->toBe('En page 2');
});

it('une page retirée du livre n’est pas supprimée', function () {
    $event = openEdition();
    $page = pageAjoutee($event, ['active' => false]);

    expect(LivretPages::all($event->fresh()))->toHaveCount(1)
        ->and(LivretPages::active($event->fresh()))->toBe([]);
});

it('pose ses repères aux MÊMES endroits dans le PDF et dans le Word', function () {
    // ⚠️⚠️ SOURCE UNIQUE : deux listes de points d'ancrage se décaleraient, et
    // la page atterrirait à un endroit dans le PDF, à un autre dans le gabarit
    // Word - sans que rien ne le signale.
    $pdf = file_get_contents(resource_path('views/documents/livret-pdf.blade.php'));
    $docx = file_get_contents(app_path('Documents/LivretDocx.php'));

    $manquants = [];
    foreach (array_keys(LivretPages::SECTIONS) as $section) {
        foreach (['avant', 'apres'] as $position) {
            if ($section === 'garde' && $position === 'avant') {
                continue; // rien ne passe devant la couverture
            }
            $slot = $position.':'.$section;
            // Les mots signés partagent une boucle : leur clé y est composée.
            $compose = in_array($section, ['bienvenue', 'president'], true)
                && str_contains($pdf, "'".$position.":'.\$msg[3]")
                && str_contains($docx, "'".$slot."'");
            if (! $compose && ! (str_contains($pdf, "'".$slot."'") && str_contains($docx, "'".$slot."'"))) {
                $manquants[] = $slot;
            }
        }
    }

    expect($manquants)->toBe([]);
});

it('RÉSERVE une page blanche par page ajoutée, repère compris', function () {
    // ⚠️ Le repère est ce que pdf-lib relit pour savoir où la réservation est
    // tombée. Sans lui, l'assemblage ne saurait pas quelle page remplacer.
    $rendu = view('documents._livret-reserve', [
        'slot' => 'avant:resumes',
        'inserts' => ['avant:resumes' => [['uri' => 'ukc-livret://pTEST', 'pages' => 2]]],
    ])->render();

    expect(substr_count($rendu, 'page-break'))->toBe(2)
        ->and($rendu)->toContain('ukc-livret://pTEST/0')
        ->and($rendu)->toContain('ukc-livret://pTEST/1');
});

it('ne réserve rien pour une page placée par NUMÉRO', function () {
    // Elle ne se pose pas dans le flux du document : elle se glisse après coup
    // dans le PDF assemblé.
    $reserves = LivretAssembly::reserves([
        ['slot' => 'avant:resumes', 'uri' => 'ukc-livret://a', 'pages' => 1],
        ['slot' => '', 'uri' => 'ukc-livret://b', 'pages' => 1],
    ]);

    expect($reserves)->toBe(['avant:resumes' => [['uri' => 'ukc-livret://a', 'pages' => 1]]]);
});

it('ajoute ses pages au PDF sans décaler la pagination du livre', function () {
    // ⚠️⚠️ C'est la raison d'être de la réservation : le livre est rendu avec le
    // bon nombre de pages DÈS LE DÉPART, donc les numéros imprimés restent
    // justes. Une page glissée au milieu les ferait tous mentir après elle.
    $submission = seededSubmission('accepted');
    $event = $submission->event;
    $groups = $event->submissions()->acceptedCanonical()->get()->groupBy(fn ($s) => $s->track ?: 'Autres');

    $sans = nombreDePages(LivretPdf::make($event, $groups)->output());

    pageAjoutee($event, ['anchor' => 'resumes', 'position' => 'avant']);
    $avec = LivretAssembly::pdf($event->fresh(), $groups, docspaceQuiRend(pdfDeNPages(2)));

    expect($avec)->toStartWith('%PDF-')
        ->and(nombreDePages($avec))->toBe($sans + 2);
});

it('glisse une page placée par numéro à la page demandée', function () {
    $submission = seededSubmission('accepted');
    $event = $submission->event;
    $groups = $event->submissions()->acceptedCanonical()->get()->groupBy(fn ($s) => $s->track ?: 'Autres');

    $sans = nombreDePages(LivretPdf::make($event, $groups)->output());
    pageAjoutee($event, ['mode' => 'page', 'page' => 2]);

    expect(nombreDePages(LivretAssembly::pdf($event->fresh(), $groups, docspaceQuiRend(pdfDeNPages(1)))))
        ->toBe($sans + 1);
});

it('porte aussi ses pages dans le gabarit Word', function () {
    // ⚠️ Le .docx est un gabarit à retoucher : s'il ignorait ces pages, celui qui
    // le reprend travaillerait sur un livre incomplet sans qu'un écran le dise.
    $submission = seededSubmission('accepted');
    $event = $submission->event;
    $groups = $event->submissions()->acceptedCanonical()->get()->groupBy(fn ($s) => $s->track ?: 'Autres');
    $page = pageAjoutee($event, ['anchor' => 'resumes', 'position' => 'avant']);

    $livre = (new LivretDocx($event->fresh(), $groups))->render();
    $marqueur = LivretDocx::PAGE_MARKER.$page['id'].']]';
    expect(xmlDuDocx($livre))->toContain($page['id']);

    $ajout = new PhpWord;
    $ajout->addSection()->addText('MOT DU RECTEUR');
    $chemin = tempnam(sys_get_temp_dir(), 'ajout-docx-');
    (new Word2007($ajout))->save($chemin);
    $binaire = (string) file_get_contents($chemin);
    @unlink($chemin);

    $fusionne = xmlDuDocx(DocxCoverMerger::insert($livre, [$marqueur => $binaire]));

    expect($fusionne)->toContain('MOT DU RECTEUR')
        ->and($fusionne)->not->toContain($page['id']);
});

it('laisse le repère en clair quand la fusion ne le trouve pas', function () {
    // ⚠️ On ne glisse pas la page au jugé à un endroit que personne n'a choisi :
    // mieux vaut que l'échec se lise dans le document.
    $ajout = new PhpWord;
    $ajout->addSection()->addText('PAGE ORPHELINE');
    $chemin = tempnam(sys_get_temp_dir(), 'ajout-docx-');
    (new Word2007($ajout))->save($chemin);
    $binaire = (string) file_get_contents($chemin);
    @unlink($chemin);

    $livre = new PhpWord;
    $livre->addSection()->addText(LivretDocx::PAGE_MARKER.'pEXISTE]]');
    $cheminLivre = tempnam(sys_get_temp_dir(), 'livre-docx-');
    (new Word2007($livre))->save($cheminLivre);
    $livreBinaire = (string) file_get_contents($cheminLivre);
    @unlink($cheminLivre);

    $fusionne = xmlDuDocx(DocxCoverMerger::insert($livreBinaire, [LivretDocx::PAGE_MARKER.'pABSENT]]' => $binaire]));

    expect($fusionne)->toContain('pEXISTE')
        ->and($fusionne)->not->toContain('PAGE ORPHELINE');
});

it('se crée depuis l’onglet « Pages ajoutées », avec son document Word', function () {
    $event = openEdition();
    $this->app->instance(TaskDocSpace::class, docspaceQuiRend(pdfDeNPages(1)));

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.attestations', ['event_id' => $event->id, 'tab' => 'livre', 'stab' => 'pages']))
        ->assertOk()
        ->assertSee('Pages ajoutées au livre');

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.livret.pages.create'), [
        'event_id' => $event->id,
        'title' => 'Mot du recteur',
        'mode' => 'section',
        'anchor' => 'programme',
        'position' => 'apres',
        'page' => 2,
    ])->assertRedirect();

    $pages = LivretPages::all($event->fresh());
    expect($pages)->toHaveCount(1)
        ->and($pages[0]['title'])->toBe('Mot du recteur')
        ->and($pages[0]['file_id'])->not->toBe('')
        ->and(LivretPages::slot($pages[0]))->toBe('apres:programme')
        ->and(LivretPages::token($pages[0]))->not->toBe('');
});

it('ne retire PAS la page du livre quand le champ n’est pas posté', function () {
    // ⚠️⚠️ C'est la raison d'être du `select` et de `input(..., '1')` plutôt que
    // de `boolean()` : une case à cocher décochée n'envoie RIEN, et `boolean()`
    // ne distingue pas « retirée » de « champ absent ». Un enregistrement qui ne
    // porterait pas le champ retirerait la page du livre dans le dos du
    // gestionnaire, sans un mot.
    $event = openEdition();
    $page = pageAjoutee($event);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.livret.pages.update'), [
        'event_id' => $event->id,
        'pageId' => $page['id'],
        'title' => 'Mot du recteur',
        'mode' => 'section',
        'anchor' => 'resumes',
        'position' => 'avant',
        'page' => 2,
    ])->assertRedirect();

    expect(LivretPages::find($event->fresh(), $page['id'])['active'])->toBeTrue();
});

it('refuse une section inconnue', function () {
    // Le formulaire propose une liste fermée ; une clé postée n'est pas une
    // autorisation, et une ancre inventée poserait la page nulle part.
    $event = openEdition();
    $page = pageAjoutee($event);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.livret.pages.update'), [
        'event_id' => $event->id,
        'pageId' => $page['id'],
        'title' => 'Mot du recteur',
        'mode' => 'section',
        'anchor' => 'section-inventee',
        'position' => 'avant',
        'page' => 2,
        'pageActive' => '1',
    ])->assertSessionHasErrors('anchor');
});

it('retire la page du livre ET son document', function () {
    $event = openEdition();
    $page = pageAjoutee($event);
    $supprimes = [];
    $this->app->instance(TaskDocSpace::class, new class($supprimes) extends TaskDocSpace
    {
        /** @param array<int, string> $supprimes */
        public function __construct(public array &$supprimes) {}

        public function deleteFile(string $fileId): void
        {
            $this->supprimes[] = $fileId;
        }
    });

    $this->actingAs(adminStaff(), 'web')->delete(route('admin.documents.livret.pages.delete'), [
        'event_id' => $event->id,
        'pageId' => $page['id'],
    ])->assertRedirect();

    expect(LivretPages::all($event->fresh()))->toBe([])
        ->and($supprimes)->toBe(['42']);
});

it('borne le nombre de pages ajoutées', function () {
    // ⚠️ Chaque page coûte une conversion DocSpace à CHAQUE export du livre, qui
    // est déjà le document le plus lourd de la plateforme.
    $event = openEdition();
    for ($i = 0; $i < LivretPages::MAX; $i++) {
        pageAjoutee($event, ['title' => 'Page '.$i]);
    }
    $this->app->instance(TaskDocSpace::class, docspaceQuiRend(pdfDeNPages(1)));

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.livret.pages.create'), [
        'event_id' => $event->id,
        'title' => 'Une de trop',
        'mode' => 'section',
        'anchor' => 'resumes',
        'position' => 'avant',
        'page' => 2,
    ])->assertRedirect();

    expect(LivretPages::all($event->fresh()))->toHaveCount(LivretPages::MAX);
});

function xmlDuDocx(string $binaire): string
{
    $chemin = tempnam(sys_get_temp_dir(), 'docx-');
    try {
        file_put_contents($chemin, $binaire);
        $zip = new ZipArchive;
        $zip->open($chemin);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        return $xml;
    } finally {
        @unlink($chemin);
    }
}
