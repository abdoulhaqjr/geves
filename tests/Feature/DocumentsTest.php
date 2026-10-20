<?php

use App\Actions\IssueAttestation;
use App\Documents\BadgePdfRenderer;
use App\Documents\BadgeSheet;
use App\Documents\LayoutSanitizer;
use App\Documents\LivretAppearance;
use App\Documents\LivretDocx;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Models\Event;
use App\Models\Formation;
use App\Models\Participant;
use App\Models\User;
use App\Support\BadgeCatalog;
use App\Support\EventSettings;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest ; seededSubmission() -> idem

/**
 * Un participant dont les frais sont RÉGLÉS.
 *
 * ⚠️ Le badge participant suit le paiement de participation (« Inscriptions
 * réglées ») : une inscription sans règlement ne paraît ni sur la planche, ni
 * dans « Badges prêts ». Un jeu d'essai qui l'oublie ne constate pas un défaut,
 * il décrit une règle.
 */
function participantRegle(Event $event, string $prenom, string $nom, string $email): Participant
{
    $participant = $event->participants()->create([
        'first_name' => $prenom, 'last_name' => $nom, 'email' => $email, 'role' => 'author',
    ]);

    $event->payments()->create([
        'participant_id' => $participant->id,
        'amount' => 15000, 'status' => 'paid', 'receipt_no' => 'REC-'.$participant->id,
    ]);

    return $participant;
}

/** Extrait word/document.xml d'un .docx (contenu binaire) pour assertions de contenu. */
function docxDocumentXml(string $binary): string
{
    $tmp = tempnam(sys_get_temp_dir(), 'docx');
    file_put_contents($tmp, $binary);

    $zip = new ZipArchive;
    expect($zip->open($tmp))->toBeTrue(); // .docx = archive ZIP valide
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    @unlink($tmp);

    return (string) $xml;
}

it('émet une attestation vérifiable et reste idempotente', function () {
    $event = openEdition();
    $p = $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    $a1 = (new IssueAttestation)->handle($p, 'participation');
    expect($a1->uuid)->not->toBeEmpty();
    expect($a1->recipient_name)->toBe('Awa KODJO');

    $a2 = (new IssueAttestation)->handle($p, 'participation');
    expect($a2->id)->toBe($a1->id); // pas de doublon
    expect(Attestation::count())->toBe(1);
});

it('émet en masse les attestations aux participants ayant payé', function () {
    $staff = adminStaff();
    $event = openEdition();
    $paid = $event->participants()->create(['first_name' => 'A', 'last_name' => 'Payé', 'email' => 'p@example.tg', 'role' => 'author']);
    $event->payments()->create(['participant_id' => $paid->id, 'amount' => 15000, 'status' => 'paid']);
    $event->participants()->create(['first_name' => 'B', 'last_name' => 'Impayé', 'email' => 'i@example.tg', 'role' => 'author']);

    $this->actingAs($staff, 'web')->post(route('admin.documents.attestations.issue'), [
        'event_id' => (string) $event->id,
        'type' => 'participation',
    ]);

    expect(Attestation::count())->toBe(1);
    expect(Attestation::first()->participant_id)->toBe($paid->id);
});

it('émet UNE attestation de modération par personne qui a modéré au moins une séance', function () {
    $staff = adminStaff();
    $event = openEdition();

    // Une personne modère DEUX séances (dédoublonnée), une autre a le rôle
    // « modérateur » mais n'est assignée à aucune séance (donc non éligible).
    $mod = $event->participants()->create(['first_name' => 'Moussa', 'last_name' => 'Modérateur', 'email' => 'moussa@uk.tg', 'role' => 'moderator']);
    $event->participants()->create(['first_name' => 'Sans', 'last_name' => 'Séance', 'email' => 'sans@uk.tg', 'role' => 'moderator']);
    $event->sessions()->create(['type' => 'atelier', 'label' => 'S1', 'moderator_email' => 'moussa@uk.tg']);
    $event->sessions()->create(['type' => 'atelier', 'label' => 'S2', 'moderator_email' => 'MOUSSA@UK.TG']); // casse différente

    $this->actingAs($staff, 'web')->post(route('admin.documents.attestations.issue'), [
        'event_id' => (string) $event->id, 'type' => 'moderation',
    ]);

    $atts = Attestation::where('event_id', $event->id)->where('type', 'moderation')->where('revoked', false)->get();
    expect($atts)->toHaveCount(1);
    expect($atts->first()->participant_id)->toBe($mod->id);

    // Idempotent : réémettre ne crée pas de doublon.
    $this->actingAs($staff, 'web')->post(route('admin.documents.attestations.issue'), [
        'event_id' => (string) $event->id, 'type' => 'moderation',
    ]);
    expect(Attestation::where('event_id', $event->id)->where('type', 'moderation')->where('revoked', false)->count())->toBe(1);
});

it('ne propose plus les attestations de soumission et d’acceptation par défaut', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')
        ->get(route('admin.documents.attestations', ['event_id' => $event->id, 'tab' => 'attestations', 'stab' => 'modeles']))
        ->assertOk()
        ->assertDontSee('Attestation de soumission')
        ->assertDontSee('Lettre d’acceptation');

    foreach (['soumission', 'acceptation'] as $type) {
        expect(DocumentTemplate::where('event_id', $event->id)->where('kind', 'attestation')->where('type', $type)->exists())
            ->toBeFalse("un modèle « {$type} » ne devrait plus être créé par défaut");
    }
});

it('vérifie publiquement une attestation valide, révoquée ou inconnue', function () {
    $event = openEdition();
    $p = $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $att = (new IssueAttestation)->handle($p, 'communication');

    // La vérification se lit sur la page de l'attestation : il n'y a plus
    // d'adresse séparée qui dirait la même chose de son côté.
    $this->get(route('attestation.document', $att->uuid))->assertOk()->assertSee('authentique')->assertSee('Awa KODJO');

    $att->update(['revoked' => true]);
    $this->get(route('attestation.document', $att->uuid))->assertOk()->assertSee('révoquée');

    // Un identifiant inconnu n'existe pas : la page ne doit pas se laisser ouvrir.
    $this->get(route('attestation.document', 'code-bidon'))->assertNotFound();
});

it('génère le document d’attestation avec un QR', function () {
    $event = openEdition();
    $p = $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $att = (new IssueAttestation)->handle($p, 'participation');

    $this->get(route('attestation.document', $att->uuid))
        ->assertOk()
        ->assertSee('Awa KODJO')
        ->assertSee('<svg', false); // QR SVG inline
});

it('le PDF d’attestation utilise le modèle enregistré du type', function () {
    $event = openEdition();

    // Modèle personnalisé pour ce type, avec une taille distinctive (700×500 px → 525×375 pt).
    DocumentTemplate::create([
        'event_id' => $event->id,
        'kind' => 'attestation',
        'type' => 'participation',
        'layout' => [
            'width' => 700, 'height' => 500, 'bg' => '#ffffff', 'accent' => '#223ea4',
            'elements' => [[
                'type' => 'text', 'x' => 40, 'y' => 40, 'w' => 600, 'h' => 40,
                'text' => 'MODELE PERSONNALISE {name}', 'size' => 20, 'weight' => 800,
                'color' => '#223ea4', 'align' => 'center',
            ]],
        ],
    ]);

    $p = $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $att = (new IssueAttestation)->handle($p, 'participation');

    $res = $this->get(route('attestation.pdf', $att->uuid));
    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');

    // La page PDF a la taille du modèle (525×375 pt) → preuve que le modèle enregistré est bien utilisé
    // (le repli par défaut serait de l'A4 paysage, ~842×595 pt).
    $pdf = $res->getContent();
    expect($pdf)->toContain('MediaBox')->toContain('525');
});

it('génère le livret des résumés groupé par axe', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted'); // titre « Innovation durable », axe « Sciences »
    $event = $submission->event;

    $this->actingAs($staff, 'web')->get(route('admin.documents.livret', $event))
        ->assertOk()
        ->assertSee('Livret des résumés')
        ->assertSee('Innovation durable')
        ->assertSee('Sciences');
});

it('génère le livret des résumés au format Word (.docx)', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted'); // titre « Innovation durable », axe « Sciences »
    $event = $submission->event;

    $response = $this->actingAs($staff, 'web')
        ->get(route('admin.documents.livret', [$event, 'format' => 'docx']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    expect($response->headers->get('Content-Disposition'))->toContain('.docx');

    $xml = docxDocumentXml($response->streamedContent());
    // Logique du booklet uk-colloques : label « LIVRE DES RÉSUMÉS » + titres d'axes en MAJUSCULES.
    expect($xml)->toContain('LIVRE DES RÉSUMÉS')
        ->toContain('Innovation durable')
        ->toContain('SCIENCES');
});

/**
 * ⚠️⚠️ **LE .DOCX NE DESCEND PAS JUSQU'AU PARTICIPANT**, même s'il le demande :
 * c'est un **gabarit**, fait pour être retouché dans Word puis réimporté, et il
 * appartient au back-office. Proposé à un participant, il donne à croire que le
 * livre se modifie - et il s'ouvre différemment d'une machine à l'autre.
 */
it('ne donne jamais le livret .docx au participant, même sur demande', function () {
    $submission = seededSubmission('accepted');
    $event = $submission->event;
    $account = $submission->participant->user;

    $reponse = $this->actingAs($account, 'account')
        ->get(route('account.documents.livret', [$event, 'format' => 'docx']));

    $reponse->assertOk();
    // Un lien ancien ne tombe pas en erreur : il rend le livre, en PDF.
    expect($reponse->headers->get('content-type'))->toContain('application/pdf');
});

it('refuse le livret .docx à un compte non participant', function () {
    $event = openEdition();
    $intrus = verifiedAccount(); // compte sans participation sur l'événement

    $this->actingAs($intrus, 'account')
        ->get(route('account.documents.livret', [$event, 'format' => 'docx']))
        ->assertForbidden();
});

/** Nombre de pages d'un PDF (le « /Type /Pages » du catalogue n'en est pas une). */
function pdfPageCount(string $bytes): int
{
    return substr_count($bytes, '/Type /Page') - substr_count($bytes, '/Type /Pages');
}

it('imprime chaque badge à sa TAILLE RÉELLE et le dispose sur l’A4', function () {
    // ⚠️⚠️ Ce sont des dimensions de SORTIE : le badge entre dans une pochette,
    // donc il ne se rétrécit pas pour en faire tenir davantage. Notre travail est
    // de les DISPOSER en tenant compte de la zone non imprimable des imprimantes.
    $attendu = [
        'Modèle 303' => [333, 472, 4, 'portrait'],
        'Modèle 304' => [378, 510, 4, 'portrait'],
        'Modèle 106' => [246, 359, 9, 'portrait'],
        // ⚠️ 2 × 108 mm = 216 mm, plus large qu'une A4 portrait : on TOURNE la feuille.
        'A6 porte-badge' => [408, 586, 2, 'landscape'],
        'CR80' => [324, 204, 10, 'portrait'],
    ];

    foreach ($attendu as $nom => [$w, $h, $parPage, $orientation]) {
        $p = BadgeSheet::plan($w, $h);

        expect($p['scale'])->toBe(1.0, $nom.' doit sortir à sa taille réelle')
            ->and($p['trueSize'])->toBeTrue()
            ->and($p['cellW'])->toBe($w)
            ->and($p['cellH'])->toBe($h)
            ->and($p['perPage'])->toBe($parPage, $nom)
            ->and($p['orientation'])->toBe($orientation, $nom)
            ->and($p['label'])->toStartWith($nom);

        // La planche entière tient dans la zone imprimable (marges comprises).
        expect($p['originLeft'])->toBeGreaterThanOrEqual($p['minMarginPx'], $nom.' marge gauche')
            ->and($p['originTop'])->toBeGreaterThanOrEqual($p['minMarginPx'], $nom.' marge haute')
            ->and($p['originLeft'] + $p['sheetW'])->toBeLessThanOrEqual($p['pageW'] - $p['minMarginPx'], $nom)
            ->and($p['originTop'] + $p['sheetH'])->toBeLessThanOrEqual($p['pageH'] - $p['minMarginPx'], $nom);
    }
});

/** Dimensions de la première page, en points (dompdf écrit un MediaBox par page). */
function pdfPageSize(string $bytes): array
{
    preg_match('~/MediaBox\s*\[\s*0(?:\.\d+)?\s+0(?:\.\d+)?\s+([\d.]+)\s+([\d.]+)~', $bytes, $m);

    return [(float) ($m[1] ?? 0), (float) ($m[2] ?? 0)];
}

it('tourne la feuille quand elle rend davantage de badges', function () {
    // ⚠️ L'A6 porte-badge fait 108 mm de large : 2 × 108 = 216 mm, plus que les
    // 210 mm d'une A4 portrait. En paysage, deux badges tiennent côte à côte -
    // une feuille gâchée par badge, sinon.
    $portrait = BadgeSheet::plan(408, 586, 'portrait');
    $paysage = BadgeSheet::plan(408, 586, 'landscape');

    expect($portrait['perPage'])->toBe(1)
        ->and($paysage['perPage'])->toBe(2)
        ->and(BadgeSheet::plan(408, 586)['orientation'])->toBe('landscape');

    // ⚠️ À ÉGALITÉ on garde le portrait : tourner la feuille sans rien y gagner
    // donnerait une planche à l'orientation surprenante.
    foreach ([[333, 472], [378, 510], [246, 359], [324, 204]] as [$w, $h]) {
        $p = BadgeSheet::plan($w, $h);
        expect($p['orientation'])->toBe('portrait', "{$w}x{$h}")
            ->and($p['perPage'])->toBeGreaterThanOrEqual(BadgeSheet::plan($w, $h, 'landscape')['perPage'], "{$w}x{$h}");
    }

    // Les deux orientations posent leur grille DANS la zone imprimable.
    foreach ([$portrait, $paysage] as $p) {
        expect($p['originLeft'])->toBeGreaterThanOrEqual($p['minMarginPx'])
            ->and($p['originLeft'] + $p['sheetW'])->toBeLessThanOrEqual($p['pageW'] - $p['minMarginPx'])
            ->and($p['originTop'] + $p['sheetH'])->toBeLessThanOrEqual($p['pageH'] - $p['minMarginPx']);
    }
});

it('le PDF est dans l’orientation du plan, et un lot mixte retombe en portrait', function () {
    $staff = adminStaff();
    $event = openEdition();
    participantRegle($event, 'Awa', 'Kodjo', 'awa@example.tg');

    $pochette = (new ReflectionMethod(LayoutSanitizer::class, 'defaultBadge'));
    $pochette->setAccessible(true);
    DocumentTemplate::ensureFor((int) $event->id, 'badge', 'default', 'Badge')
        ->forceFill(['layout' => $pochette->invoke(null, false, 'a6-pochette')])->save();

    // Une seule catégorie au format A6 porte-badge : la feuille est en paysage.
    $seul = $this->actingAs($staff, 'web')
        ->get(route('admin.documents.badges', [$event, 'type' => 'default']))->assertOk();

    [$w, $h] = pdfPageSize($seul->getContent());
    expect($w)->toBeGreaterThan($h, 'A6 porte-badge : feuille en paysage');

    // ⚠️⚠️ UNE FEUILLE N'A QU'UNE ORIENTATION : un lot « tous les badges prêts »
    // mêle des formats, donc on impose le PORTRAIT **et** on recalcule les plans
    // concernés. Sans ce recalcul, les positions viseraient une feuille qui n'est
    // pas celle du PDF et les badges sortiraient hors page.
    $qui = [['name' => 'Awa Kodjo', 'institution' => 'UK', 'ref' => 'BDG-1', 'photo' => '', 'photo_mode' => 'without']];
    $mixte = (new BadgePdfRenderer)->pdf($event, [
        ['type' => 'default', 'people' => $qui],      // A6 porte-badge -> paysage
        ['type' => 'moderation', 'people' => $qui],   // modèle 303 -> portrait
    ]);

    [$tw, $th] = pdfPageSize($mixte->output());
    expect($th)->toBeGreaterThan($tw, 'lot mixte : feuille en portrait');

    $replie = BadgeSheet::plan(408, 586, 'portrait');
    expect($replie['originLeft'] + $replie['sheetW'])->toBeLessThanOrEqual($replie['pageW'] - $replie['minMarginPx'])
        ->and($replie['originTop'] + $replie['sheetH'])->toBeLessThanOrEqual($replie['pageH'] - $replie['minMarginPx']);
});

it('ne réduit un badge que s’il est plus grand que la feuille', function () {
    // Filet de sécurité : sans lui, un format démesuré sortirait rogné.
    $p = BadgeSheet::plan(1200, 1600);

    expect($p['scale'])->toBeLessThan(1.0)
        ->and($p['trueSize'])->toBeFalse()
        ->and($p['perPage'])->toBe(1)
        ->and($p['originLeft'] + $p['sheetW'])->toBeLessThanOrEqual($p['pageW'] - $p['minMarginPx']);
});

it('pose la grille DANS la feuille A4, marges comprises', function () {
    // ⚠️⚠️ LE DÉFAUT QUI A COÛTÉ UNE VERSION : la planche débordait de la feuille
    // (un badge coupé à droite, la moitié basse vide). Deux causes, toutes deux
    // propres à dompdf : il n'ENROULE PAS les `float`, et il place un élément
    // absolu par rapport à la FEUILLE en ignorant `@page { margin }`.
    foreach ([[397, 560], [560, 397], [333, 472], [378, 510], [246, 359], [324, 204], [408, 586]] as [$w, $h]) {
        $p = BadgeSheet::plan($w, $h);

        $droite = $p['originLeft'] + ($p['cols'] - 1) * ($p['cellW'] + $p['gutterX']) + $p['cellW'];
        $bas = $p['originTop'] + ($p['rows'] - 1) * ($p['cellH'] + $p['gutterY']) + $p['cellH'];

        expect($droite)->toBeLessThanOrEqual($p['pageW'] - $p['minMarginPx'], "largeur {$w}x{$h}")
            ->and($bas)->toBeLessThanOrEqual($p['pageH'] - $p['minMarginPx'], "hauteur {$w}x{$h}")
            ->and($p['minMarginPx'])->toBeGreaterThanOrEqual(18); // 5 mm à 96 dpi
    }
});

it('la VUE pose chaque badge dans la zone imprimable de l’A4', function () {
    // ⚠️⚠️ La garde porte sur la VUE, car c'est elle qui portait le défaut : les
    // coordonnées doivent inclure la marge d'impression (dompdf place un élément
    // absolu par rapport à la FEUILLE et ignore `@page { margin }`).
    $plan = BadgeSheet::plan(333, 472); // Modèle 303 : 4 par page

    $html = view('pdf.badges-sheet', [
        'sheets' => [[
            'cols' => $plan['cols'], 'cellW' => $plan['cellW'], 'cellH' => $plan['cellH'],
            'gutterX' => $plan['gutterX'], 'gutterY' => $plan['gutterY'],
            'originLeft' => $plan['originLeft'], 'originTop' => $plan['originTop'],
            'pageW' => $plan['pageW'], 'pageH' => $plan['pageH'],
            'badges' => array_fill(0, $plan['perPage'], '<i></i>'),
        ]],
    ])->render();

    preg_match_all('/class="cell" style="left:(\d+)px;top:(\d+)px;width:(\d+)px;height:(\d+)px;"/', $html, $m, PREG_SET_ORDER);

    expect($m)->toHaveCount($plan['perPage']);

    foreach ($m as $cellule) {
        [, $x, $y, $w, $h] = array_map('intval', $cellule);
        expect($x)->toBeGreaterThanOrEqual($plan['minMarginPx'])
            ->and($y)->toBeGreaterThanOrEqual($plan['minMarginPx'])
            ->and($x + $w)->toBeLessThanOrEqual($plan['pageW'] - $plan['minMarginPx'])
            ->and($y + $h)->toBeLessThanOrEqual($plan['pageH'] - $plan['minMarginPx']);
    }
});

it('la planche PDF ne retombe jamais sur des flottants', function () {
    // ⚠️ dompdf n'enroule pas les `float` : il alignait les 4 badges sur UNE
    // rangée, qui sortait de la feuille. La planche se pose en absolu.
    $vue = file_get_contents(resource_path('views/pdf/badges-sheet.blade.php'));

    expect($vue)->toContain('position: absolute')
        ->and($vue)->not->toContain('float:')
        ->and($vue)->not->toContain('float :');
});

it('redessine la maquette à la taille de la cellule, sans toucher à l’interlignage', function () {
    // ⚠️ La réduction RECALCULE la géométrie : dompdf n'applique pas `transform:
    // scale()` de façon fiable, et c'est lui qui produit le PDF imprimé.
    $layout = [
        'width' => 400, 'height' => 600, 'bg' => '#ffffff',
        'elements' => [
            ['type' => 'text', 'x' => 100, 'y' => 200, 'w' => 200, 'h' => 40, 'size' => 20, 'leading' => 1.4, 'spacing' => 2],
            ['type' => 'qr', 'x' => 40, 'y' => 40, 'size' => 80],
        ],
    ];

    $scaled = BadgeSheet::scaleLayout($layout, 0.5, 200, 300);

    expect($scaled['width'])->toBe(200)->and($scaled['height'])->toBe(300);
    expect($scaled['elements'][0]['x'])->toBe(50)
        ->and($scaled['elements'][0]['w'])->toBe(100)
        ->and($scaled['elements'][0]['size'])->toBe(10)
        ->and($scaled['elements'][1]['size'])->toBe(40);
    // `leading` est un RAPPORT, pas une longueur : le mettre à l'échelle
    // diviserait l'interligne par deux.
    expect($scaled['elements'][0]['leading'])->toBe(1.4);
});

it('génère la planche de badges en PDF', function () {
    $staff = adminStaff();
    $event = openEdition();
    // ⚠️ **Le badge participant suit le PAIEMENT** (« Inscriptions réglées ») :
    // sans règlement, la planche est vide - et c'est la règle, pas un défaut.
    participantRegle($event, 'Awa', 'Kodjo', 'awa@example.tg');

    // ⚠️ C'est dompdf qui produit la planche, plus l'impression du navigateur.
    $res = $this->actingAs($staff, 'web')->get(route('admin.documents.badges', $event))->assertOk();

    expect($res->headers->get('content-type'))->toContain('application/pdf')
        ->and($res->headers->get('content-disposition'))->toContain('badges-default.pdf')
        ->and(substr($res->getContent(), 0, 5))->toBe('%PDF-');
});

it('sans destinataire, la planche le dit au lieu de rendre un PDF vide', function () {
    $staff = adminStaff();
    $event = openEdition(); // aucun participant réglé

    $this->actingAs($staff, 'web')->get(route('admin.documents.badges', $event))
        ->assertRedirect();
});

it('imprime TOUS les badges prêts en un seul PDF', function () {
    $staff = adminStaff();
    $event = openEdition();
    participantRegle($event, 'Awa', 'Kodjo', 'awa@example.tg');

    $res = $this->actingAs($staff, 'web')->get(route('admin.documents.badges.all', $event))->assertOk();

    expect($res->headers->get('content-disposition'))->toContain('badges-tous.pdf')
        ->and(substr($res->getContent(), 0, 5))->toBe('%PDF-');
});

it('n’imprime que les destinataires cochés', function () {
    $staff = adminStaff();
    $event = openEdition();
    participantRegle($event, 'Awa', 'Kodjo', 'awa@example.tg');
    participantRegle($event, 'Beni', 'Abalo', 'beni@example.tg');

    // La référence du badge est la clé de sélection : elle vient de la liste réelle.
    $refAwa = BadgeCatalog::recipients($event, 'default')
        ->firstWhere('name', 'Awa KODJO')['ref'];

    $res = $this->actingAs($staff, 'web')
        ->post(route('admin.documents.badges.selection', ['event' => $event->id]), [
            'type' => 'default', 'refs' => [$refAwa],
        ])->assertOk();

    expect($res->headers->get('content-disposition'))->toContain('badges-default-selection.pdf')
        ->and(substr($res->getContent(), 0, 5))->toBe('%PDF-');

    // Rien de coché : on le dit, on ne rend pas un PDF vide.
    $this->actingAs($staff, 'web')
        ->post(route('admin.documents.badges.selection', ['event' => $event->id]), ['type' => 'default', 'refs' => []])
        ->assertRedirect();
});

it('la planche PDF utilise le modèle enregistré', function () {
    $staff = adminStaff();
    $event = openEdition();
    participantRegle($event, 'Awa', 'Kodjo', 'awa@example.tg');
    participantRegle($event, 'Beni', 'Abalo', 'beni@example.tg');

    // ⚠️ Preuve OBSERVABLE dans le PDF : un modèle presque aussi grand qu'une A4
    // n'en laisse tenir qu'UN par page, là où l'A6 par défaut en met quatre. Deux
    // destinataires donnent donc deux pages - impossible si le modèle enregistré
    // était ignoré. (Le texte d'un PDF est compressé : on ne peut pas l'y lire.)
    DocumentTemplate::create([
        'event_id' => $event->id,
        'kind' => 'badge',
        'type' => 'default',
        'layout' => [
            'width' => 700, 'height' => 900, 'bg' => '#ffffff', 'accent' => '#223ea4',
            'elements' => [[
                'type' => 'text', 'x' => 20, 'y' => 40, 'w' => 600, 'h' => 40,
                'text' => 'BADGE PERSO {name}', 'size' => 18, 'weight' => 800,
                'color' => '#223ea4', 'align' => 'center',
            ]],
        ],
    ]);

    expect(BadgeSheet::plan(700, 900)['perPage'])->toBe(1);

    $res = $this->actingAs($staff, 'web')->get(route('admin.documents.badges', $event))->assertOk();

    expect(pdfPageCount($res->getContent()))->toBe(2);
});

it('borne la planche en lots, et « badges prêts » annonce les lots', function () {
    $staff = adminStaff();
    $event = openEdition();
    // ⚠️ Un lot BORNÉ : environ 2,5 Mo de mémoire par badge, et le rendu tombait
    // au milieu sur une édition de 200 inscrits. 52 destinataires = 2 PDF.
    for ($i = 0; $i < 52; $i++) {
        participantRegle($event, 'P'.$i, 'Nom'.$i, 'p'.$i.'@example.tg');
    }

    // Le badge par défaut est au modèle 303 : 4 par feuille A4.
    expect(BadgeSheet::LOT)->toBe(48)
        ->and(BadgeSheet::plan(333, 472)['perPage'])->toBe(4);

    // Lot 1 : 48 badges = 12 feuilles A4. Lot 2 : les 4 restants.
    $lot1 = $this->actingAs($staff, 'web')->get(route('admin.documents.badges', [$event, 'type' => 'default']))->assertOk();
    expect(pdfPageCount($lot1->getContent()))->toBe(12);

    $lot2 = $this->actingAs($staff, 'web')->get(route('admin.documents.badges', [$event, 'type' => 'default', 'lot' => 2]))->assertOk();
    expect(pdfPageCount($lot2->getContent()))->toBe(1)
        ->and($lot2->headers->get('content-disposition'))->toContain('badges-default-lot2.pdf');

    // ⚠️ Les lots s'annoncent là où l'on imprime - l'onglet « Badges prêts » -,
    // sinon on ne pourrait jamais atteindre le lot 2.
    $this->actingAs($staff, 'web')
        ->get(route('admin.documents.attestations', ['event_id' => $event->id, 'tab' => 'badges', 'stab' => 'prets']))
        ->assertOk()
        ->assertSee('Lot 1/2')
        ->assertSee('Lot 2/2');
});

it('le studio badges montre les KPI et l’aperçu SUR LA PAGE', function () {
    $staff = adminStaff();
    $event = openEdition();
    participantRegle($event, 'Awa', 'Kodjo', 'awa@example.tg');

    $res = $this->actingAs($staff, 'web')
        ->get(route('admin.documents.attestations', ['event_id' => $event->id, 'tab' => 'badges', 'stab' => 'modeles']))
        ->assertOk()
        // Bandeau KPI (parité avec Attestations et Livre).
        ->assertSee('Destinataires prêts')
        ->assertSee('Catégories prêtes')
        // L'aperçu est rendu sur la page, à côté de la liste.
        ->assertSee('badge-studio__preview', false)
        ->assertSee('Aperçu du modèle')
        // Le format imprimé et le nombre par feuille se lisent dans la table.
        ->assertSee('Modèle 303')
        ->assertSee('par page A4');

    // ⚠️ Trois colonnes retirées : la vignette n'apprenait rien, et le libellé
    // porte déjà le type. Ne pas les réintroduire. (On vise l'en-tête EXACT du
    // tableau des badges : la page rend aussi les onglets Attestations et Livre.)
    $res->assertSee('<th>Libellé</th><th>Format</th><th>Destinataires</th><th>Statut</th>', false)
        ->assertDontSee('<th>Aperçu</th><th>Type</th><th>Libellé</th>', false);

    // Le bouton « œil » du badge faisait doublon avec l'aperçu de la page.
    $res->assertDontSee('apercu?event_id='.$event->id.'&amp;kind=badge', false);
});

it('propose de cocher des destinataires dans « badges prêts »', function () {
    $staff = adminStaff();
    $event = openEdition();
    participantRegle($event, 'Awa', 'Kodjo', 'awa@example.tg');

    $this->actingAs($staff, 'web')
        ->get(route('admin.documents.attestations', ['event_id' => $event->id, 'tab' => 'badges', 'stab' => 'prets']))
        ->assertOk()
        // ⚠️ On vise la MÉCANIQUE de sélection, pas le nom affiché : sa mise en
        // forme appartient à l'identité d'événement et peut changer sans que la
        // sélection soit en cause.
        ->assertSee('Imprimer la sélection')
        ->assertSee('name="refs[]"', false)
        ->assertSee('badges-selection', false);
});

it('protège les documents admin sans permission', function () {
    $event = openEdition();
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get(route('admin.documents.livret', $event))->assertForbidden();
});

it('affiche le hub Documents fidèle au mockup (volets + table par type)', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')
        ->get(route('admin.documents.attestations', ['event_id' => $event->id]))
        ->assertOk()
        ->assertSee('Modèles')
        ->assertSee('Attestations délivrées')
        ->assertSee('Options générales')
        ->assertSee('Livre des résumés')
        ->assertSee('Attestation de participation')
        ->assertSee('Destinataires', false);
});

it('liste les modèles d’attestation du module Formation avec émission par formation', function () {
    $staff = adminStaff();
    $event = openEdition(['formation_enabled' => true]);
    $formation = Formation::create([
        'event_id' => $event->id, 'title' => 'Atelier', 'fee' => 0, 'published' => true,
    ]);
    $formation->trainers()->create(['name' => 'Pr. Awa Kodjo']);

    $this->actingAs($staff, 'web')
        ->get(route('admin.documents.attestations', ['event_id' => $event->id, 'tab' => 'attestations', 'stab' => 'modeles']))
        ->assertOk()
        ->assertSee('Attestation de formation')
        ->assertSee('Attestation d’animation', false)
        // Ces attestations s'émettent depuis chaque formation, pas globalement.
        ->assertSee('Émettre par formation')
        ->assertSee(route('admin.formations', ['event_id' => $event->id]), false);
});

it('filtre les attestations délivrées par nom, e-mail et date d’émission', function () {
    $staff = adminStaff();
    $event = openEdition();
    $base = ['event_id' => $event->id, 'tab' => 'attestations', 'stab' => 'delivrees'];

    Attestation::create([
        'event_id' => $event->id, 'type' => 'participation', 'uuid' => 'uuid-awa',
        'recipient_name' => 'Awa Kodjo', 'recipient_email' => 'awa@example.tg',
        'revoked' => false, 'issued_at' => now()->subDays(10),
    ]);
    Attestation::create([
        'event_id' => $event->id, 'type' => 'participation', 'uuid' => 'uuid-kofi',
        'recipient_name' => 'Kofi Mensah', 'recipient_email' => 'kofi@example.tg',
        'revoked' => false, 'issued_at' => now(),
    ]);

    // Par nom.
    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['search' => 'Awa']))
        ->assertOk()->assertSee('Awa Kodjo')->assertDontSee('Kofi Mensah');

    // Par e-mail.
    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['search' => 'kofi@example.tg']))
        ->assertOk()->assertSee('Kofi Mensah')->assertDontSee('Awa Kodjo');

    // Par date d'émission (les 3 derniers jours).
    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['from' => now()->subDays(3)->toDateString()]))
        ->assertOk()->assertSee('Kofi Mensah')->assertDontSee('Awa Kodjo');
});

it('ne propose que les types d’attestation offerts, et garde ceux déjà délivrés', function () {
    $staff = adminStaff();
    $event = openEdition();
    $base = ['event_id' => $event->id, 'tab' => 'attestations'];

    // « soumission » et « acceptation » restent dans Attestation::TYPES pour les
    // données existantes, mais ne sont plus offerts : ni à la création d'un
    // modèle, ni au filtre des délivrées.
    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['stab' => 'modeles']))
        ->assertOk()
        ->assertDontSee('<option value="soumission"', false)
        ->assertDontSee('<option value="acceptation"', false)
        ->assertSee('<option value="instruction_resumes"', false);

    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['stab' => 'delivrees']))
        ->assertOk()
        ->assertDontSee('<option value="soumission"', false)
        ->assertSee('<option value="participation"', false);

    // ⚠️ Mais une attestation déjà délivrée sous un type retiré doit rester
    // filtrable - même révoquée, sinon elle devient introuvable.
    Attestation::create([
        'event_id' => $event->id, 'type' => 'soumission', 'uuid' => 'uuid-ancienne',
        'recipient_name' => 'Awa Kodjo', 'revoked' => true, 'issued_at' => now()->subYear(),
    ]);

    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['stab' => 'delivrees']))
        ->assertOk()
        ->assertSee('<option value="soumission"', false)
        // Le type retiré ne revient PAS dans le menu de création pour autant.
        ->assertDontSee('<option value="acceptation"', false);

    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['stab' => 'modeles']))
        ->assertOk()->assertDontSee('<option value="soumission"', false);
});

it('sert chaque sous-onglet des attestations par son URL (?stab=)', function () {
    $staff = adminStaff();
    $event = openEdition();
    $base = ['event_id' => $event->id, 'tab' => 'attestations'];

    // Modèles (défaut) : la table des types d'attestation.
    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['stab' => 'modeles']))
        ->assertOk()
        ->assertSee('Attestation de participation')
        ->assertSee('Attestation de rapporteur')
        ->assertSee('Émission idempotente');

    // Délivrées : la liste filtrable, sur la MÊME url (le panneau Modèles n'est
    // alors pas rendu du tout).
    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['stab' => 'delivrees']))
        ->assertOk()
        ->assertSee('Bénéficiaire')
        ->assertDontSee('Émission idempotente');

    // Options.
    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['stab' => 'options']))
        ->assertOk()->assertSee('Options générales - Attestations');
});

it('persiste l’avant-propos du livre dans les réglages et l’injecte au .docx', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.documents.livret.options'), [
        'event_id' => $event->id,
        'forewordTitle' => 'Préface',
        'forewordText' => 'Un mot du comité.',
        'forewordSignature' => 'Le Président',
    ]);

    // Réglages documents scopés par événement (« à chaque colloque ses templates »).
    expect(EventSettings::get($event->fresh(), 'livret.foreword_text'))->toBe('Un mot du comité.');

    // Le .docx inclut l'avant-propos (réponse streamée → capture via tampon).
    $response = (new LivretDocx($event->fresh(), collect()))->download('t.docx');
    ob_start();
    $response->sendContent();
    $binary = (string) ob_get_clean();

    $xml = docxDocumentXml($binary);
    expect($xml)->toContain('Préface')->toContain('Un mot du comité.')->toContain('Le Président');
});

it('persiste l’apparence du livre (typographie, couleurs, page de garde)', function () {
    $staff = adminStaff();
    $event = openEdition();

    // ⚠️ **Le formulaire poste le MODÈLE ACTIF** depuis « Isoler l'apparence du
    // livre par modèle actif » : l'apparence appartient désormais à un modèle, et
    // l'action refuse d'enregistrer si celui de la page n'est plus celui de la
    // base - deux onglets ouverts suffiraient sinon à écrire l'apparence d'un
    // modèle sur un autre. Ce cas ne le postait pas, donc rien n'était enregistré.
    //
    // ⚠️ Et ce refus-là **n'est pas une erreur de validation** : il part en message
    // flash. `assertSessionHasNoErrors()` ne le voit pas - le cas restait vert
    // jusqu'à l'assertion finale, qui accusait le stockage au lieu du refus. On
    // éprouve donc aussi l'absence de refus.
    $reponse = $this->actingAs($staff, 'web')->post(route('admin.documents.livret.apparence'), [
        'event_id' => $event->id,
        'livretModel' => LivretAppearance::activeModel($event),
        'livretFontBody' => 'Georgia',
        'livretFontHead' => 'Jost',
        'livretSizeBody' => '12',
        'livretSizeCover' => '24',
        'livretSizeAxis' => '16',
        'livretSizeAbstract' => '12',
        'livretSizeAuthors' => '10',
        'livretLineHeight' => '1.15',
        'livretColorPrimary' => '#123456',
        'livretColorSecondary' => '#009933',
        'livretAlignTitles' => 'Centré',
        'livretAlignAxis' => 'Centré',
        'livretAlignAuthors' => 'Centré',
        'livretAlignBody' => 'Gauche',
        'livretCoverMention' => 'Livre des résumés - UK',
        'livretNumber' => '1',
    ]);

    $reponse->assertSessionHasNoErrors();
    expect(session('ukc_error'))->toBeNull();

    $event = $event->fresh();

    // ⚠️ **L'apparence est rangée PAR MODÈLE** (`livret.appearances.<modèle>`)
    // depuis « Isoler l'apparence du livre par modèle actif » : chaque modèle
    // garde la sienne. Ce cas lisait encore les clés plates `livret.font_body`,
    // qui ne sont plus écrites - il mesurait donc l'ancien rangement, pas le
    // réglage. On relit par la source de vérité, celle que le rendu emploie.
    expect(LivretAppearance::get($event, 'font_body'))->toBe('Georgia');
    expect((int) LivretAppearance::get($event, 'size_body'))->toBe(12);
    expect(LivretAppearance::get($event, 'color_primary'))->toBe('#123456');
    expect(LivretAppearance::get($event, 'cover_mention'))->toBe('Livre des résumés - UK');
    expect((bool) LivretAppearance::get($event, 'number_abstracts'))->toBeTrue();
});

it('applique les réglages d’apparence au .docx (mention de couverture + numérotation)', function () {
    $submission = seededSubmission('accepted'); // titre « Innovation durable »
    $event = $submission->event;

    Settings::setMany([
        'livret.cover_mention' => 'MENTION COUVERTURE TEST',
        'livret.number_abstracts' => true,
    ]);

    $groups = collect(['Sciences' => collect([$submission])]);
    $response = (new LivretDocx($event->fresh(), $groups))->download('t.docx');
    ob_start();
    $response->sendContent();
    $xml = docxDocumentXml((string) ob_get_clean());

    // La mention de couverture apparaît, et le résumé est numéroté « 1. … ».
    expect($xml)->toContain('MENTION COUVERTURE TEST')->toContain('1. Innovation durable');
});

it('échappe les caractères spéciaux (& < >) : le .docx reste un document valide', function () {
    $submission = seededSubmission('accepted');
    $submission->update([
        'title' => 'Recherche & Développement <IA>',
        'track' => 'Sciences & technologies',
        'abstract' => 'Étude sur A & B avec seuils < 5 et > 1.',
    ]);
    $event = $submission->event;

    $groups = collect(['Sciences & technologies' => collect([$submission->fresh()])]);
    $response = (new LivretDocx($event->fresh(), $groups))->download('t.docx');
    ob_start();
    $response->sendContent();
    $xml = docxDocumentXml((string) ob_get_clean());

    // Le document.xml doit être bien formé (Word refuse un XML invalide) et les « & » échappés.
    expect(simplexml_load_string($xml))->not->toBeFalse();
    expect($xml)->toContain('Recherche &amp; Développement');
});

it('livre des résumés : préserve les « < » / « > » nus et le gras du corps', function () {
    $submission = seededSubmission('accepted');
    $submission->update(['abstract' => 'Seuils < 5 et > 1 avec <b>résultat majeur</b>.']);
    $event = $submission->event;

    $groups = collect(['Sciences' => collect([$submission->fresh()])]);
    $response = (new LivretDocx($event->fresh(), $groups))->download('t.docx');
    ob_start();
    $response->sendContent();
    $xml = docxDocumentXml((string) ob_get_clean());

    expect(simplexml_load_string($xml))->not->toBeFalse();   // document valide
    expect($xml)->toContain('&lt; 5 et &gt; 1')              // « < » / « > » nus conservés (échappés)
        ->toContain('résultat majeur');                       // texte en gras conservé
});

it('audit livret : aperçu HTML, export .docx et harmonisation IA (normalise les auteurs)', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $submission->update(['authors' => [['name' => '  jean   DUPONT ']]]);
    $event = $submission->event;

    $this->actingAs($staff, 'web');
    $this->get(route('admin.documents.livret', $event))->assertOk();                                        // aperçu HTML
    $this->get(route('admin.documents.livret', ['event' => $event, 'format' => 'docx']))->assertOk();     // export .docx

    // Harmonisation : « jean   DUPONT » → « Jean Dupont », puis affiché en MAJUSCULES.
    $response = $this->get(route('admin.documents.livret', ['event' => $event, 'format' => 'docx', 'harmonize' => 1]));
    $response->assertOk();
    expect(docxDocumentXml($response->streamedContent()))->toContain('JEAN DUPONT');
});

it('avant-propos IA : endpoint sûr même sans fournisseur IA (redirige, aucun 500)', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.documents.livret.foreword-ai', ['event_id' => $event->id]))
        ->assertRedirect();

    // Aucun fournisseur IA configuré en test → rien n'est généré ni persisté.
    expect(Settings::get('livret.foreword_text'))->toBeNull();
});

it('audit options : les DIDs des attestations se persistent sans erreur', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.documents.attestations.options'), [
        'event_id' => $event->id,
        'attPrefix' => 'UKC-ATT-2026-',
        'attDidIssuer' => 'did:web:univkara.tg:colloques',
        'attDidMethod' => 'did:web:univkara.tg:colloques#key-ed25519-1',
        'attCryptoSuite' => 'Ed25519Signature2020',
        'attAuto' => '1', 'attNotify' => '1', 'attIncludeQr' => '1',
    ])->assertSessionHasNoErrors();

    expect(EventSettings::get($event->fresh(), 'attestations.did_issuer'))->toBe('did:web:univkara.tg:colloques');
    expect(EventSettings::get($event->fresh(), 'attestations.crypto_suite'))->toBe('Ed25519Signature2020');
});

it('importe un modèle .docx et fixe le modèle actif', function () {
    Storage::fake('public');
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.documents.livret.template'), [
        'event_id' => $event->id,
        'templateFile' => UploadedFile::fake()->create('modele.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    ])->assertSessionHasNoErrors();

    $templates = EventSettings::get($event->fresh(), 'livret.templates');
    expect($templates)->toHaveCount(1);
    expect($templates[0]['name'])->toBe('modele.docx');
    Storage::disk('public')->assertExists($templates[0]['path']);

    $this->actingAs($staff, 'web')->post(route('admin.documents.livret.template'), ['event_id' => $event->id, 'livretTemplate' => 'base']);
    expect(EventSettings::get($event->fresh(), 'livret.template'))->toBe('base');
});

it('crée un modèle avec son libellé avant d’ouvrir le concepteur', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.documents.templates.create'), [
        'event_id' => $event->id, 'kind' => 'attestation',
        'type' => 'participation', 'title' => 'Attestation de présence',
    ])->assertRedirect(route('admin.documents.templates', [
        'event_id' => $event->id, 'kind' => 'attestation', 'type' => 'participation',
    ]));

    $tpl = DocumentTemplate::where('event_id', $event->id)->where('type', 'participation')->first();
    expect($tpl)->not->toBeNull();
    expect($tpl->title)->toBe('Attestation de présence');

    // Le libellé saisi remplace celui du type dans la liste des modèles.
    $this->actingAs($staff, 'web')
        ->get(route('admin.documents.attestations', ['event_id' => $event->id, 'tab' => 'attestations', 'stab' => 'modeles']))
        ->assertOk()->assertSee('Attestation de présence');
});

it('retient le format choisi pour un modèle de badge', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.documents.templates.create'), [
        'event_id' => $event->id, 'kind' => 'badge', 'type' => 'default', 'format' => 'cr80',
    ])->assertRedirect();

    $tpl = DocumentTemplate::where('event_id', $event->id)->where('kind', 'badge')->first();
    expect($tpl->format)->toBe('cr80');
    // ⚠️ « Badge » tout court a été renommé « Badge participant » quand le modèle
    // unique a été aligné sur les participants réglés - `DocumentTemplate` remonte
    // même les anciens titres.
    expect($tpl->title)->toBe('Badge participant');
});

it('ne supprime un modèle que si le code de confirmation est exact', function () {
    $staff = adminStaff();
    $event = openEdition();
    $tpl = DocumentTemplate::create([
        'event_id' => $event->id, 'kind' => 'attestation', 'type' => 'participation',
        'title' => 'Attestation de présence', 'layout' => ['width' => 900, 'height' => 640, 'elements' => []],
    ]);

    // Mauvais code : rien n'est supprimé.
    $this->actingAs($staff, 'web')
        ->delete(route('admin.documents.templates.destroy', $tpl), ['confirm' => 'oui'])
        ->assertSessionHasErrors('confirm');
    expect(DocumentTemplate::find($tpl->id))->not->toBeNull();

    // Code exact (insensible à la casse).
    $this->actingAs($staff, 'web')
        ->delete(route('admin.documents.templates.destroy', $tpl), ['confirm' => 'supprimer-participation'])
        ->assertRedirect(route('admin.documents.attestations', [
            'event_id' => $event->id, 'tab' => 'attestations', 'stab' => 'modeles',
        ]));
    expect(DocumentTemplate::find($tpl->id))->toBeNull();
});

it('sert les sous-onglets des badges par leur URL, dont « badges prêts »', function () {
    $staff = adminStaff();
    $event = openEdition();
    $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $base = ['event_id' => $event->id, 'tab' => 'badges'];

    // Prêts : seules les inscriptions réglées, et celle-ci ne l'est pas encore.
    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['stab' => 'prets']))
        ->assertOk()->assertSee('Badges prêts')
        ->assertDontSee('aria-label="Imprimer le badge de Awa KODJO"', false);

    // ⚠️ **Il n'y a plus de « tous les participants »** : la liste a été alignée sur
    // les participants réglés, comme la planche. Une fois les frais réglés, la
    // personne y entre - c'est le règlement qui la fait apparaître, pas un filtre.
    $event->payments()->create([
        'participant_id' => $event->participants()->first()->id,
        'amount' => 15000, 'status' => 'paid', 'receipt_no' => 'REC-BADGE-1',
    ]);

    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['stab' => 'prets']))
        ->assertOk()->assertSee('aria-label="Imprimer le badge de Awa KODJO"', false);
});

it('sert chaque onglet principal de Documents par son URL (?tab=)', function () {
    $staff = adminStaff();
    $event = openEdition();

    // Chaque onglet doit pointer vers une URL : les panneaux sont rendus côté
    // serveur, un simple état JavaScript afficherait une page vide.
    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', ['event_id' => $event->id]))
        ->assertOk()
        // (les & des URL sont échappés en &amp; dans le HTML : on vise les fragments)
        ->assertSee('tab=badges', false)
        ->assertSee('tab=livre', false);

    // ⚠️ Plus de bouton « Nouveau modèle de badge » : il n'y a qu'UN modèle par
    // catégorie, provisionné d'office. On vise ce que l'onglet dit vraiment.
    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', ['event_id' => $event->id, 'tab' => 'badges', 'stab' => 'modeles']))
        ->assertOk()->assertSee('Aperçu du modèle');

    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', ['event_id' => $event->id, 'tab' => 'livre']))
        ->assertOk()->assertSee('Livre des résumés');
});

it('rend toujours une attestation depuis un modèle enregistré (plus de repli)', function () {
    $staff = adminStaff();
    $event = openEdition();
    $attestation = Attestation::create([
        'event_id' => $event->id, 'type' => 'participation', 'uuid' => 'uuid-rendu',
        'recipient_name' => 'Awa Kodjo', 'revoked' => false, 'issued_at' => now(),
    ]);

    expect(DocumentTemplate::where('event_id', $event->id)->where('type', 'participation')->exists())->toBeFalse();

    $this->actingAs($staff, 'web')->get(route('attestation.document', $attestation->uuid))->assertOk();

    // Le rendu a créé le modèle : il est désormais visible et modifiable.
    $tpl = DocumentTemplate::where('event_id', $event->id)->where('kind', 'attestation')->where('type', 'participation')->first();
    expect($tpl)->not->toBeNull();
    expect($tpl->layout)->not->toBeEmpty();
});

it('provisionne un modèle enregistré pour chaque type sur l’onglet Modèles', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')
        ->get(route('admin.documents.attestations', ['event_id' => $event->id, 'tab' => 'attestations', 'stab' => 'modeles']))
        ->assertOk();

    // Tous les types listés ont un modèle : la suppression est proposée partout.
    // ⚠️ « soumission » et « acceptation » ne sont plus proposés par défaut.
    foreach (['communication', 'participation', 'moderation', 'rapporteur', 'organisation', 'comite_organisation', 'instruction_resumes', 'formation', 'formation_animation'] as $type) {
        expect(DocumentTemplate::where('event_id', $event->id)->where('kind', 'attestation')->where('type', $type)->exists())
            ->toBeTrue("modèle manquant pour {$type}");
    }
});
