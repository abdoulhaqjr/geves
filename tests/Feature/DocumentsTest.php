<?php

use App\Actions\IssueAttestation;
use App\Documents\LivretDocx;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Models\Formation;
use App\Models\User;
use App\Support\EventSettings;
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
    $event = openEdition();
    $p = $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    $a1 = (new IssueAttestation)->handle($p, 'participation');
    expect($a1->uuid)->not->toBeEmpty();
    expect($a1->recipient_name)->toBe('Awa Kodjo');

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
    $this->get(route('attestation.document', $att->uuid))->assertOk()->assertSee('authentique')->assertSee('Awa Kodjo');

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
        ->assertSee('Awa Kodjo')
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

it('permet au participant de télécharger le livret .docx de son événement', function () {
    $submission = seededSubmission('accepted');
    $event = $submission->event;
    $account = $submission->participant->user;

    $response = $this->actingAs($account, 'account')
        ->get(route('account.documents.livret', [$event, 'format' => 'docx']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    expect(docxDocumentXml($response->streamedContent()))->toContain('Innovation durable');
});

it('refuse le livret .docx à un compte non participant', function () {
    $event = openEdition();
    $intrus = verifiedAccount(); // compte sans participation sur l'événement

    $this->actingAs($intrus, 'account')
        ->get(route('account.documents.livret', [$event, 'format' => 'docx']))
        ->assertForbidden();
});

it('génère la planche de badges', function () {
    $staff = adminStaff();
    $event = openEdition();
    $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    $this->actingAs($staff, 'web')->get(route('admin.documents.badges', $event))
        ->assertOk()
        ->assertSee('Awa Kodjo')
        ->assertSee('Communicant');
});

it('la planche de badges utilise le modèle enregistré', function () {
    $staff = adminStaff();
    $event = openEdition();
    $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    // Modèle de badge personnalisé avec un texte distinctif substitué.
    DocumentTemplate::create([
        'event_id' => $event->id,
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
    $this->actingAs($staff, 'web')->get(route('admin.documents.badges', $event))
        ->assertOk()
        ->assertSee('BADGE PERSO Awa Kodjo');
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

    $this->actingAs($staff, 'web')->post(route('admin.documents.livret.apparence'), [
        'event_id' => $event->id,
        'livretFontBody' => 'Georgia',
        'livretSizeBody' => '12',
        'livretColorPrimary' => '#123456',
        'livretAlignBody' => 'Gauche',
        'livretCoverMention' => 'Livre des résumés - UK',
        'livretNumber' => '1',
    ])->assertSessionHasNoErrors();

    $event = $event->fresh();
    expect(EventSettings::get($event, 'livret.font_body'))->toBe('Georgia');
    expect((int) EventSettings::get($event, 'livret.size_body'))->toBe(12);
    expect(EventSettings::get($event, 'livret.color_primary'))->toBe('#123456');
    expect(EventSettings::get($event, 'livret.cover_mention'))->toBe('Livre des résumés - UK');
    expect((bool) EventSettings::get($event, 'livret.number_abstracts'))->toBeTrue();
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

it('audit options : DIDs attestations et réglages badges se persistent sans erreur', function () {
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

    $this->actingAs($staff, 'web')->post(route('admin.documents.badges.options'), [
        'event_id' => $event->id,
        'badgePrefix' => 'UKC-2026-',
        'badgeFormat' => 'CR80',
        'badgeGenerateAfterPayment' => '1',
        'badgeBatchPrint' => '1',
    ])->assertSessionHasNoErrors();

    expect(EventSettings::get($event->fresh(), 'badges.format'))->toBe('CR80');
    expect((bool) EventSettings::get($event->fresh(), 'badges.batch_print'))->toBeTrue();
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
    expect($tpl->title)->toBe('Badge');
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

    // Prêts : par défaut seules les inscriptions réglées (aucune ici).
    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['stab' => 'prets']))
        ->assertOk()->assertSee('Badges prêts')->assertDontSee('Awa Kodjo');

    // Tous les participants.
    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['stab' => 'prets', 'paid' => '0']))
        ->assertOk()->assertSee('Awa Kodjo');

    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', $base + ['stab' => 'options']))
        ->assertOk()->assertSee('Options générales - Badges');
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

    $this->actingAs($staff, 'web')->get(route('admin.documents.attestations', ['event_id' => $event->id, 'tab' => 'badges', 'stab' => 'modeles']))
        ->assertOk()->assertSee('Nouveau modèle de badge');

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
