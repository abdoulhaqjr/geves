<?php

use App\Actions\IssueAttestation;
use App\Documents\LivretDocx;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Models\Formation;
use App\Models\User;
use App\Support\EditionSettings;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest ; seededSubmission() -> idem

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
    $edition = openEdition();
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    $a1 = (new IssueAttestation)->handle($p, 'participation');
    expect($a1->uuid)->not->toBeEmpty();
    expect($a1->recipient_name)->toBe('Awa Kodjo');

    $a2 = (new IssueAttestation)->handle($p, 'participation');
    expect($a2->id)->toBe($a1->id); // pas de doublon
    expect(Attestation::count())->toBe(1);
});

it('émet en masse les attestations aux participants ayant payé', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $paid = $edition->participants()->create(['first_name' => 'A', 'last_name' => 'Payé', 'email' => 'p@example.tg', 'role' => 'author']);
    $edition->payments()->create(['participant_id' => $paid->id, 'amount' => 15000, 'status' => 'paid']);
    $edition->participants()->create(['first_name' => 'B', 'last_name' => 'Impayé', 'email' => 'i@example.tg', 'role' => 'author']);

    $this->actingAs($staff, 'web')->post(route('admin.documents.attestations.issue'), [
        'edition_id' => (string) $edition->id,
        'type' => 'participation',
    ]);

    expect(Attestation::count())->toBe(1);
    expect(Attestation::first()->participant_id)->toBe($paid->id);
});

it('vérifie publiquement une attestation valide, révoquée ou inconnue', function () {
    $edition = openEdition();
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $att = (new IssueAttestation)->handle($p, 'communication');

    $this->get(route('attestation.verify', $att->uuid))->assertOk()->assertSee('authentique')->assertSee('Awa Kodjo');

    $att->update(['revoked' => true]);
    $this->get(route('attestation.verify', $att->uuid))->assertOk()->assertSee('révoquée');

    $this->get(route('attestation.verify', 'code-bidon'))->assertOk()->assertSee('introuvable');
});

it('génère le document d’attestation avec un QR', function () {
    $edition = openEdition();
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $att = (new IssueAttestation)->handle($p, 'participation');

    $this->get(route('attestation.document', $att->uuid))
        ->assertOk()
        ->assertSee('Awa Kodjo')
        ->assertSee('<svg', false); // QR SVG inline
});

it('le PDF d’attestation utilise le modèle enregistré du type', function () {
    $edition = openEdition();

    // Modèle personnalisé pour ce type, avec une taille distinctive (700×500 px → 525×375 pt).
    DocumentTemplate::create([
        'edition_id' => $edition->id,
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

    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
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
    $edition = $submission->edition;

    $this->actingAs($staff, 'web')->get(route('admin.documents.livret', $edition))
        ->assertOk()
        ->assertSee('Livret des résumés')
        ->assertSee('Innovation durable')
        ->assertSee('Sciences');
});

it('génère le livret des résumés au format Word (.docx)', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted'); // titre « Innovation durable », axe « Sciences »
    $edition = $submission->edition;

    $response = $this->actingAs($staff, 'web')
        ->get(route('admin.documents.livret', [$edition, 'format' => 'docx']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    expect($response->headers->get('Content-Disposition'))->toContain('.docx');

    $xml = docxDocumentXml($response->streamedContent());
    // Logique du booklet uk-colloques : label « LIVRE DES RÉSUMÉS » + titres d'axes en MAJUSCULES.
    expect($xml)->toContain('LIVRE DES RÉSUMÉS')
        ->toContain('Innovation durable')
        ->toContain('SCIENCES');
});

it('permet au participant de télécharger le livret .docx de son édition', function () {
    $submission = seededSubmission('accepted');
    $edition = $submission->edition;
    $account = $submission->participant->user;

    $response = $this->actingAs($account, 'account')
        ->get(route('account.documents.livret', [$edition, 'format' => 'docx']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    expect(docxDocumentXml($response->streamedContent()))->toContain('Innovation durable');
});

it('refuse le livret .docx à un compte non participant', function () {
    $edition = openEdition();
    $intrus = verifiedAccount(); // compte sans participation sur l'édition

    $this->actingAs($intrus, 'account')
        ->get(route('account.documents.livret', [$edition, 'format' => 'docx']))
        ->assertForbidden();
});

it('génère la planche de badges', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    $this->actingAs($staff, 'web')->get(route('admin.documents.badges', $edition))
        ->assertOk()
        ->assertSee('Awa Kodjo')
        ->assertSee('Communicant');
});

it('la planche de badges utilise le modèle enregistré', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    // Modèle de badge personnalisé avec un texte distinctif substitué.
    DocumentTemplate::create([
        'edition_id' => $edition->id,
        'kind' => 'badge',
        'type' => 'default',
        'layout' => [
            'width' => 320, 'height' => 440, 'bg' => '#ffffff', 'accent' => '#223ea4',
            'elements' => [[
                'type' => 'text', 'x' => 20, 'y' => 40, 'w' => 280, 'h' => 40,
                'text' => 'BADGE PERSO {name}', 'size' => 18, 'weight' => 800,
                'color' => '#223ea4', 'align' => 'center',
            ]],
        ],
    ]);

    // Le rendu reprend le modèle (texte substitué) → preuve que le template enregistré est utilisé.
    $this->actingAs($staff, 'web')->get(route('admin.documents.badges', $edition))
        ->assertOk()
        ->assertSee('BADGE PERSO Awa Kodjo');
});

it('protège les documents admin sans permission', function () {
    $edition = openEdition();
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get(route('admin.documents.livret', $edition))->assertForbidden();
});

it('affiche le hub Documents fidèle au mockup (volets + table par type)', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')
        ->get(route('admin.documents.attestations', ['edition_id' => $edition->id]))
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
    $edition = openEdition(['formation_enabled' => true]);
    $formation = Formation::create([
        'edition_id' => $edition->id, 'title' => 'Atelier', 'fee' => 0, 'published' => true,
    ]);
    $formation->trainers()->create(['name' => 'Pr. Awa Kodjo']);

    $this->actingAs($staff, 'web')
        ->get(route('admin.documents.attestations', ['edition_id' => $edition->id, 'tab' => 'attestations', 'stab' => 'modeles']))
        ->assertOk()
        ->assertSee('Attestation de formation')
        ->assertSee('Attestation d’animation', false)
        // Ces attestations s'émettent depuis chaque formation, pas globalement.
        ->assertSee('Émettre par formation')
        ->assertSee(route('admin.formations', ['edition_id' => $edition->id]), false);
});

it('sert chaque sous-onglet des attestations par son URL (?stab=)', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $base = ['edition_id' => $edition->id, 'tab' => 'attestations'];

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
        ->assertOk()->assertSee('Options générales · Attestations');
});

it('persiste l’avant-propos du livre dans les réglages et l’injecte au .docx', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.documents.livret.options'), [
        'edition_id' => $edition->id,
        'forewordTitle' => 'Préface',
        'forewordText' => 'Un mot du comité.',
        'forewordSignature' => 'Le Président',
    ]);

    // Réglages documents scopés par édition (« à chaque colloque ses templates »).
    expect(EditionSettings::get($edition->fresh(), 'livret.foreword_text'))->toBe('Un mot du comité.');

    // Le .docx inclut l'avant-propos (réponse streamée → capture via tampon).
    $response = (new LivretDocx($edition->fresh(), collect()))->download('t.docx');
    ob_start();
    $response->sendContent();
    $binary = (string) ob_get_clean();

    $xml = docxDocumentXml($binary);
    expect($xml)->toContain('Préface')->toContain('Un mot du comité.')->toContain('Le Président');
});

it('persiste l’apparence du livre (typographie, couleurs, page de garde)', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.documents.livret.apparence'), [
        'edition_id' => $edition->id,
        'livretFontBody' => 'Georgia',
        'livretSizeBody' => '12',
        'livretColorPrimary' => '#123456',
        'livretAlignBody' => 'Gauche',
        'livretCoverMention' => 'Livre des résumés · UK',
        'livretNumber' => '1',
    ])->assertSessionHasNoErrors();

    $edition = $edition->fresh();
    expect(EditionSettings::get($edition, 'livret.font_body'))->toBe('Georgia');
    expect((int) EditionSettings::get($edition, 'livret.size_body'))->toBe(12);
    expect(EditionSettings::get($edition, 'livret.color_primary'))->toBe('#123456');
    expect(EditionSettings::get($edition, 'livret.cover_mention'))->toBe('Livre des résumés · UK');
    expect((bool) EditionSettings::get($edition, 'livret.number_abstracts'))->toBeTrue();
});

it('applique les réglages d’apparence au .docx (mention de couverture + numérotation)', function () {
    $submission = seededSubmission('accepted'); // titre « Innovation durable »
    $edition = $submission->edition;

    Settings::setMany([
        'livret.cover_mention' => 'MENTION COUVERTURE TEST',
        'livret.number_abstracts' => true,
    ]);

    $groups = collect(['Sciences' => collect([$submission])]);
    $response = (new LivretDocx($edition->fresh(), $groups))->download('t.docx');
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
    $edition = $submission->edition;

    $groups = collect(['Sciences & technologies' => collect([$submission->fresh()])]);
    $response = (new LivretDocx($edition->fresh(), $groups))->download('t.docx');
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
    $edition = $submission->edition;

    $groups = collect(['Sciences' => collect([$submission->fresh()])]);
    $response = (new LivretDocx($edition->fresh(), $groups))->download('t.docx');
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
    $edition = $submission->edition;

    $this->actingAs($staff, 'web');
    $this->get(route('admin.documents.livret', $edition))->assertOk();                                        // aperçu HTML
    $this->get(route('admin.documents.livret', ['edition' => $edition, 'format' => 'docx']))->assertOk();     // export .docx

    // Harmonisation : « jean   DUPONT » → « Jean Dupont » dans le .docx.
    $response = $this->get(route('admin.documents.livret', ['edition' => $edition, 'format' => 'docx', 'harmonize' => 1]));
    $response->assertOk();
    expect(docxDocumentXml($response->streamedContent()))->toContain('Jean Dupont');
});

it('avant-propos IA : endpoint sûr même sans fournisseur IA (redirige, aucun 500)', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.documents.livret.foreword-ai', ['edition_id' => $edition->id]))
        ->assertRedirect();

    // Aucun fournisseur IA configuré en test → rien n'est généré ni persisté.
    expect(Settings::get('livret.foreword_text'))->toBeNull();
});

it('audit options : DIDs attestations et réglages badges se persistent sans erreur', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.documents.attestations.options'), [
        'edition_id' => $edition->id,
        'attPrefix' => 'UKC-ATT-2026-',
        'attDidIssuer' => 'did:web:univkara.tg:colloques',
        'attDidMethod' => 'did:web:univkara.tg:colloques#key-ed25519-1',
        'attCryptoSuite' => 'Ed25519Signature2020',
        'attAuto' => '1', 'attNotify' => '1', 'attIncludeQr' => '1',
    ])->assertSessionHasNoErrors();

    expect(EditionSettings::get($edition->fresh(), 'attestations.did_issuer'))->toBe('did:web:univkara.tg:colloques');
    expect(EditionSettings::get($edition->fresh(), 'attestations.crypto_suite'))->toBe('Ed25519Signature2020');

    $this->actingAs($staff, 'web')->post(route('admin.documents.badges.options'), [
        'edition_id' => $edition->id,
        'badgePrefix' => 'UKC-2026-',
        'badgeFormat' => 'CR80',
        'badgeGenerateAfterPayment' => '1',
        'badgeBatchPrint' => '1',
    ])->assertSessionHasNoErrors();

    expect(EditionSettings::get($edition->fresh(), 'badges.format'))->toBe('CR80');
    expect((bool) EditionSettings::get($edition->fresh(), 'badges.batch_print'))->toBeTrue();
});

it('importe un modèle .docx et fixe le modèle actif', function () {
    Storage::fake('public');
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.documents.livret.template'), [
        'edition_id' => $edition->id,
        'templateFile' => UploadedFile::fake()->create('modele.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
    ])->assertSessionHasNoErrors();

    $templates = EditionSettings::get($edition->fresh(), 'livret.templates');
    expect($templates)->toHaveCount(1);
    expect($templates[0]['name'])->toBe('modele.docx');
    Storage::disk('public')->assertExists($templates[0]['path']);

    $this->actingAs($staff, 'web')->post(route('admin.documents.livret.template'), ['edition_id' => $edition->id, 'livretTemplate' => 'base']);
    expect(EditionSettings::get($edition->fresh(), 'livret.template'))->toBe('base');
});
