<?php

use App\Documents\LivretTemplate;
use App\Documents\LivretView;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\Slot;
use App\Models\User;
use App\Support\EventSettings;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Writer\Word2007;

// adminStaff() / seededSubmission() : fabriques partagées de tests/Pest.php.

/** Lit word/document.xml d'un .docx reçu en corps de réponse. */
function livretDocumentXml(string $binary): string
{
    $tmp = tempnam(sys_get_temp_dir(), 'livret');
    file_put_contents($tmp, $binary);
    $zip = new ZipArchive;
    expect($zip->open($tmp))->toBeTrue();
    $xml = (string) $zip->getFromName('word/document.xml');
    $zip->close();
    @unlink($tmp);

    return $xml;
}

it('génère le livret .docx avec les résumés et l’index des auteurs', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $event = $submission->event;

    $res = $this->actingAs($staff, 'web')
        ->get(route('admin.documents.livret', ['event' => $event, 'format' => 'docx']));

    $res->assertOk();
    expect($res->headers->get('content-type'))
        ->toContain('officedocument.wordprocessingml.document');

    $xml = livretDocumentXml($res->streamedContent());
    expect($xml)->toContain('Résumés des communications')
        ->toContain('Innovation durable')   // titre de la communication acceptée
        ->toContain('Index des auteurs')     // la nouvelle section
        ->toContain('AWA KODJO')             // l'auteur indexé (style par défaut : nom en MAJUSCULES)
        ->toContain('26-001');               // la référence (renvoi de l'index)
});

it('insère les sections de tête génériques seulement si elles sont remplies', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $event = $submission->event;

    // 1) Rien de rempli : aucune section de tête (hors avant-propos éventuel).
    $xml = livretDocumentXml(
        $this->actingAs($staff, 'web')
            ->get(route('admin.documents.livret', ['event' => $event, 'format' => 'docx']))
            ->streamedContent()
    );
    expect($xml)->not->toContain('Haut patronage de la ministre')
        ->not->toContain('Monsieur le Président de l’Université');

    // 2) Sections remplies : elles apparaissent dans le .docx.
    EventSettings::set($event, 'livret.patronage', "Sous le haut patronage de la ministre\nProf Exemple");
    EventSettings::set($event, 'livret.acknowledgements', "Monsieur le Président de l’Université\nMonsieur le Préfet");
    EventSettings::set($event, 'livret.welcome_text', 'Chères participantes, chers participants, bienvenue.');
    EventSettings::set($event, 'livret.welcome_title', 'Mot de bienvenue du Doyen');
    EventSettings::set($event, 'livret.president_text', 'C’est avec plaisir que nous vous accueillons.');

    $xml = livretDocumentXml(
        $this->actingAs($staff, 'web')
            ->get(route('admin.documents.livret', ['event' => $event, 'format' => 'docx']))
            ->streamedContent()
    );
    expect($xml)->toContain('Sous le haut patronage de la ministre')
        ->toContain('Monsieur le Président de l’Université')
        ->toContain('Mot de bienvenue du Doyen')
        ->toContain('C’est avec plaisir que nous vous accueillons');
});

it('applique le style intégré sélectionné (Journées scientifiques / FSS)', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $event = $submission->event;

    // Un comité pour rendre visible la différence de titre entre les deux styles.
    CommitteeMember::create([
        'event_id' => $event->id,
        'person_name' => 'Jean Test',
        'role' => 'organisation',
        'ordre' => 1,
    ]);

    // Style par défaut (UK/UP) : « Différentes commissions… », commissions numérotées.
    $xmlDefault = livretDocumentXml(
        $this->actingAs($staff, 'web')
            ->get(route('admin.documents.livret', ['event' => $event, 'format' => 'docx']))
            ->streamedContent()
    );
    expect($xmlDefault)->toContain('Différentes commissions et programmes du colloque');

    // Style FSS sélectionné : titre « Comités », sans le libellé UK/UP.
    EventSettings::set($event, 'livret.template', 'fss');
    $xmlFss = livretDocumentXml(
        $this->actingAs($staff, 'web')
            ->get(route('admin.documents.livret', ['event' => $event, 'format' => 'docx']))
            ->streamedContent()
    );
    expect($xmlFss)->toContain('Comités')
        ->toContain('INNOVATION DURABLE')   // titres de résumé en MAJUSCULES (style FSS)
        ->not->toContain('Différentes commissions et programmes du colloque');
});

it('applique le style « Document général du colloque » (comité en tableau + ateliers)', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $event = $submission->event;

    // Comité d'organisation avec grade, contacts et qualité → tableau du livret.
    CommitteeMember::create([
        'event_id' => $event->id,
        'person_name' => 'Tchaa PALI',
        'person_title' => 'PT',
        'role' => 'organisation',
        'phone' => '+22890060576',
        'qualite' => 'Président',
        'ordre' => 1,
    ]);

    // Une communication programmée (créneau horodaté) → planning par atelier.
    $session = $event->sessions()->create([
        'label' => 'Atelier 1 - séance 1', 'type' => 'atelier',
        'session_day' => '2026-09-03', 'starts_at' => '2026-09-03 09:00', 'ends_at' => '2026-09-03 12:00',
    ]);
    Slot::create([
        'event_id' => $event->id, 'session_id' => $session->id,
        'submission_id' => $submission->id, 'participant_id' => $submission->participant_id,
        'starts_at' => '2026-09-03 09:00', 'ends_at' => '2026-09-03 09:30',
    ]);

    EventSettings::set($event, 'livret.template', 'general');

    $xml = livretDocumentXml(
        $this->actingAs($staff, 'web')
            ->get(route('admin.documents.livret', ['event' => $event, 'format' => 'docx']))
            ->streamedContent()
    );

    expect($xml)
        // Section lettrée « A) … » (gras, noir), comités en CAPITALES (doc de référence).
        ->toContain('A) Les différentes commissions et programmes du colloque')
        ->toContain('COMITÉ D’ORGANISATION')
        ->toContain('Tchaa PALI')
        ->toContain('+22890060576')
        ->toContain('Président')
        // Communications en ateliers : rubrique numérotée, un atelier par axe.
        ->toContain('COMMUNICATIONS EN ATELIERS')
        ->toContain('ATELIER 1 : SCIENCES')
        ->toContain('Innovation durable')
        // Résumés : section lettrée « B) … » et axe préfixé « 1- … ».
        ->toContain('B) Résumés des communications')
        ->toContain('1- SCIENCES')
        ->toContain('Index des auteurs')
        // ⚠️ COULEURS EXACTES du document de référence : ateliers en terracotta,
        // bandeau de jour bleu, en-tête de tableau gris (fidélité demandée).
        ->toContain('C0504D')   // titre d'atelier en terracotta
        ->toContain('95B3D7')   // bandeau de jour bleu
        ->toContain('F2F2F2')   // en-tête de tableau gris
        // ⚠️ NAVIGABLE : axes/ateliers (Heading1) et codes de communication
        // (Heading2) sont de vrais styles de titre Word → volet de navigation
        // et sommaire. Bookmarks posés par addTitle.
        ->toContain('w:val="Heading1"')
        ->toContain('w:val="Heading2"')
        ->toContain('<w:bookmarkStart');
});

it('regroupe les communications par ATELIER, pas par axe (un atelier couvre plusieurs axes)', function () {
    $staff = adminStaff();
    // Quatre axes, répartis en DEUX ateliers (comme le colloque de l'eau : 6 axes,
    // 2 ateliers). Le livret sortait un « atelier » par axe - ici ce serait quatre.
    $event = openEdition(['tracks' => ['Axe A', 'Axe B', 'Axe C', 'Axe D']]);
    $account = verifiedAccount();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);

    $event->ateliers()->create(['title' => 'Atelier 1', 'tracks' => ['Axe A', 'Axe B'], 'ordre' => 1]);
    $event->ateliers()->create(['title' => 'Atelier 2', 'tracks' => ['Axe C', 'Axe D'], 'ordre' => 2]);

    // Une communication acceptée et programmée par axe.
    foreach (['Axe A' => 'Comm A', 'Axe B' => 'Comm B', 'Axe C' => 'Comm C', 'Axe D' => 'Comm D'] as $axe => $titre) {
        $sub = $event->submissions()->create([
            'participant_id' => $participant->id,
            'ref_code' => 'REF-'.str_replace(' ', '', $titre),
            'title' => $titre,
            'abstract' => str_repeat('Contexte. ', 10),
            'track' => $axe,
            'type' => 'oral',
            'status' => 'accepted',
            'authors' => [['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'corresponding' => true]],
        ]);
        $session = $event->sessions()->create([
            'label' => $axe.' séance', 'type' => 'atelier',
            'session_day' => '2026-09-03', 'starts_at' => '2026-09-03 09:00', 'ends_at' => '2026-09-03 12:00',
        ]);
        Slot::create([
            'event_id' => $event->id, 'session_id' => $session->id,
            'submission_id' => $sub->id, 'participant_id' => $participant->id,
            'starts_at' => '2026-09-03 09:00', 'ends_at' => '2026-09-03 09:30',
        ]);
    }

    EventSettings::set($event, 'livret.template', 'general');

    $xml = livretDocumentXml(
        $this->actingAs($staff, 'web')
            ->get(route('admin.documents.livret', ['event' => $event, 'format' => 'docx']))
            ->streamedContent()
    );

    // DEUX ateliers, pas quatre : l'intitulé est « ATELIER 1 » / « ATELIER 2 » (le
    // sous-titre « : … » disparaît quand le titre se réduit au préfixe « Atelier n »),
    // et il n'existe PAS de troisième atelier.
    expect($xml)
        ->toContain('COMMUNICATIONS EN ATELIERS')
        ->toContain('ATELIER 1')
        ->toContain('ATELIER 2')
        ->not->toContain('ATELIER 3')
        // Les deux axes d'un même atelier sont réunis sous lui.
        ->toContain('Comm A')->toContain('Comm B')
        ->toContain('Comm C')->toContain('Comm D');
});

it('propose un bouton Télécharger sur CHAQUE modèle intégré, vers le gabarit à balises', function () {
    $staff = adminStaff();
    $event = openEdition();

    $page = $this->actingAs($staff, 'web')
        ->get(route('admin.documents.attestations', ['tab' => 'livre', 'stab' => 'modeles', 'event_id' => $event->id]))
        ->assertOk();

    // ⚠️ « Télécharger » rend le MODÈLE (gabarit .docx à balises à personnaliser dans
    // Word), jamais le livre peuplé : les QUATRE cartes de modèle intégré pointent sur
    // la route du gabarit de base (une 5e occurrence vient du lien « modèle de base »
    // de la carte des modèles personnalisés).
    $content = $page->getContent();
    expect(substr_count($content, e(route('admin.documents.livret.base-template'))))->toBeGreaterThanOrEqual(5);
});

it('le gabarit de base téléchargé est un vrai .docx à balises de fusion', function () {
    $staff = adminStaff();

    $res = $this->actingAs($staff, 'web')->get(route('admin.documents.livret.base-template'));

    $res->assertOk();
    expect($res->headers->get('content-type'))
        ->toContain('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    $xml = livretDocumentXml($res->streamedContent());
    expect($xml)->toContain('[[RESUMES]]')->toContain('[[PROGRAMME]]');
});

it('génère le livret en PDF', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');

    $res = $this->actingAs($staff, 'web')
        ->get(route('admin.documents.livret', ['event' => $submission->event, 'format' => 'pdf']));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('pdf');
    expect(substr((string) $res->getContent(), 0, 5))->toBe('%PDF-');
});

it('reflète le style « Document général » dans l’aperçu PDF (comité en tableau + ateliers)', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $event = $submission->event;

    CommitteeMember::create([
        'event_id' => $event->id, 'person_name' => 'Tchaa PALI', 'person_title' => 'PT',
        'role' => 'organisation', 'phone' => '+22890060576', 'qualite' => 'Président', 'ordre' => 1,
    ]);
    $session = $event->sessions()->create([
        'label' => 'Atelier 1 - séance 1', 'type' => 'atelier',
        'session_day' => '2026-09-03', 'starts_at' => '2026-09-03 09:00', 'ends_at' => '2026-09-03 12:00',
    ]);
    Slot::create([
        'event_id' => $event->id, 'session_id' => $session->id,
        'submission_id' => $submission->id, 'participant_id' => $submission->participant_id,
        'starts_at' => '2026-09-03 09:00', 'ends_at' => '2026-09-03 09:30',
    ]);

    EventSettings::set($event, 'livret.template', 'general');

    // Le PDF se génère sans erreur avec le style general (branche dédiée de LivretView::data).
    $res = $this->actingAs($staff, 'web')
        ->get(route('admin.documents.livret', ['event' => $event, 'format' => 'pdf']));

    $res->assertOk();
    expect(substr((string) $res->getContent(), 0, 5))->toBe('%PDF-');
});

it('génère le livret à partir d’un modèle .docx personnalisé (balises remplies)', function () {
    Storage::fake('public');

    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $event = $submission->event;

    // Un vrai modèle à balises = le modèle de base fourni à l'admin.
    $tmp = tempnam(sys_get_temp_dir(), 'tpl');
    (new Word2007(LivretTemplate::baseModel()))->save($tmp);
    Storage::disk('public')->put('livret-templates/modele.docx', (string) file_get_contents($tmp));
    @unlink($tmp);

    EventSettings::set($event, 'livret.templates', [['name' => 'modele.docx', 'path' => 'livret-templates/modele.docx']]);
    EventSettings::set($event, 'livret.template', 'modele.docx');

    $res = $this->actingAs($staff, 'web')
        ->get(route('admin.documents.livret', ['event' => $event, 'format' => 'docx']));

    $res->assertOk();
    $xml = livretDocumentXml($res->streamedContent());

    // Les balises bloc/inline ont été remplacées par le contenu généré.
    expect($xml)->toContain('Innovation durable')   // [[RESUMES]] -> résumés
        ->toContain($event->title)                 // [[TITRE]] -> titre de l'événement
        ->not->toContain('[[RESUMES]]')
        ->not->toContain('[[TITRE]]');
});

it('fournit un modèle de base .docx à balises', function () {
    $staff = adminStaff();

    $res = $this->actingAs($staff, 'web')->get(route('admin.documents.livret.base-template'));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('officedocument.wordprocessingml.document');
    $xml = livretDocumentXml($res->streamedContent());
    expect($xml)->toContain('[[RESUMES]]')->toContain('[[TITRE]]');
});

it('protège la génération du livret sans permission', function () {
    $submission = seededSubmission('accepted');
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->get(route('admin.documents.livret', ['event' => $submission->event, 'format' => 'docx']))
        ->assertForbidden();
});

/**
 * ⚠️ **Le livre des résumés ne contient que des communications ACCEPTÉES**, et
 * cela vaut pour TOUTES ses sections. Les résumés filtraient bien ; le
 * PROGRAMME, lui, lisait les créneaux sans rien vérifier - une communication
 * planifiée puis refusée gardait son créneau, et le livre imprimé annonçait son
 * titre et ses auteurs à l'heure dite alors que son résumé n'y figurait pas.
 * Deux sections du même livre se contredisaient.
 */
it('n’annonce au programme que les communications retenues au livre', function () {
    $staff = adminStaff();
    $retenue = seededSubmission('accepted');
    $event = $retenue->event;

    $session = $event->sessions()->create([
        'label' => 'Atelier 1 - séance 1', 'type' => 'atelier',
        'session_day' => '2026-09-03', 'starts_at' => '2026-09-03 09:00', 'ends_at' => '2026-09-03 12:00',
    ]);

    // Celle qui est au livre : résumé ET programme.
    Slot::create([
        'event_id' => $event->id, 'session_id' => $session->id,
        'submission_id' => $retenue->id, 'participant_id' => $retenue->participant_id,
        'starts_at' => '2026-09-03 09:00', 'ends_at' => '2026-09-03 09:30',
    ]);

    // Planifiée, puis REFUSÉE : son créneau lui survit.
    $refusee = $event->submissions()->create([
        'participant_id' => $retenue->participant_id,
        'ref_code' => '26-002',
        'title' => 'Communication refusée après planification',
        'abstract' => str_repeat('Contexte. ', 10),
        'track' => 'Sciences', 'type' => 'oral', 'status' => 'rejected', 'revision_of' => 0,
        'authors' => [['name' => 'Kossi Mensah', 'email' => 'kossi@example.tg', 'corresponding' => true]],
    ]);
    Slot::create([
        'event_id' => $event->id, 'session_id' => $session->id,
        'submission_id' => $refusee->id, 'participant_id' => $retenue->participant_id,
        'starts_at' => '2026-09-03 09:30', 'ends_at' => '2026-09-03 10:00',
    ]);

    // Une RESOUMISSION acceptée : c'est la version canonique qui porte le résumé,
    // pas elle - sinon la même communication paraîtrait deux fois.
    $revision = $event->submissions()->create([
        'participant_id' => $retenue->participant_id,
        'ref_code' => '26-003',
        'title' => 'Version corrigée qui doublonnerait',
        'abstract' => str_repeat('Contexte. ', 10),
        'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted', 'revision_of' => $retenue->id,
        'authors' => [['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'corresponding' => true]],
    ]);
    Slot::create([
        'event_id' => $event->id, 'session_id' => $session->id,
        'submission_id' => $revision->id, 'participant_id' => $retenue->participant_id,
        'starts_at' => '2026-09-03 10:00', 'ends_at' => '2026-09-03 10:30',
    ]);

    $xml = livretDocumentXml(
        $this->actingAs($staff, 'web')
            ->get(route('admin.documents.livret', ['event' => $event, 'format' => 'docx']))
            ->streamedContent()
    );

    expect($xml)->toContain('Programme')
        ->toContain('Innovation durable')                          // retenue : résumé + programme
        ->not->toContain('Communication refusée après planification')
        ->not->toContain('Kossi Mensah')                           // ni son auteur au programme
        ->not->toContain('Version corrigée qui doublonnerait');
});

it('écarte aussi les communications hors livre du programme servi au PDF', function () {
    $staff = adminStaff();
    $retenue = seededSubmission('accepted');
    $event = $retenue->event;

    $session = $event->sessions()->create([
        'label' => 'Atelier 1 - séance 1', 'type' => 'atelier',
        'session_day' => '2026-09-03', 'starts_at' => '2026-09-03 09:00', 'ends_at' => '2026-09-03 12:00',
    ]);
    $refusee = $event->submissions()->create([
        'participant_id' => $retenue->participant_id,
        'ref_code' => '26-002',
        'title' => 'Communication refusée après planification',
        'abstract' => str_repeat('Contexte. ', 10),
        'track' => 'Sciences', 'type' => 'oral', 'status' => 'rejected', 'revision_of' => 0,
        'authors' => [['name' => 'Kossi Mensah', 'email' => 'kossi@example.tg', 'corresponding' => true]],
    ]);
    Slot::create([
        'event_id' => $event->id, 'session_id' => $session->id,
        'submission_id' => $refusee->id, 'participant_id' => $retenue->participant_id,
        'starts_at' => '2026-09-03 09:30', 'ends_at' => '2026-09-03 10:00',
    ]);

    // ⚠️ Le PDF construit SON programme de son côté (`LivretView::data`), et c'est
    // là tout l'enjeu : le .docx corrigé ne dit rien de ce chemin-là. On lit la
    // donnée plutôt que les octets du PDF - dompdf comprime ses flux, donc y
    // chercher un titre absent rendrait un cas qui passe sans rien prouver.

    // ⚠️ Groupes VIDES à dessein : ils n'alimentent que les résumés et l'index.
    // Le programme, lui, est interrogé à l'intérieur - c'est lui qu'on éprouve,
    // et le cas ne doit pas dépendre de la requête des résumés pour le dire.
    $vue = LivretView::data($event, collect());

    $titres = collect($vue['programme'])->flatten(1)
        ->map(fn ($slot) => (string) ($slot->submission?->title ?? ''))
        ->filter()->values();

    expect($titres)->not->toContain('Communication refusée après planification');

    // ...et l'aperçu HTML, lui, ne porte que les résumés - il n'a pas de
    // programme, donc rien n'y fuit non plus.
    $this->actingAs($staff, 'web')
        ->get(route('admin.documents.livret', ['event' => $event]))
        ->assertOk()
        ->assertDontSee('Communication refusée après planification');
});

/** Rend le HTML de la vue PDF (dompdf) du livret, tel que le contrôleur l'alimente. */
function livretPdfHtml(Event $event): string
{
    $groups = $event->submissions()->acceptedCanonical()->orderBy('track')->get()
        ->groupBy(fn ($s) => $s->track ?: 'Autres');
    $data = LivretView::data($event, $groups);

    return view('documents.livret-pdf', $data)->render();
}

it('casse des auteurs : le .docx et le PDF concordent selon le style', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $submission->update(['authors' => [['name' => 'Jean Dupont', 'email' => 'jean@example.tg', 'corresponding' => true]]]);
    $event = $submission->event;

    $docx = fn () => livretDocumentXml(
        $this->actingAs($staff, 'web')
            ->get(route('admin.documents.livret', ['event' => $event, 'format' => 'docx']))
            ->streamedContent()
    );

    // Style par défaut : nom en MAJUSCULES dans les DEUX rendus (source unique
    // LivretDocx::authorCase). ⚠️ C'est la divergence corrigée : le PDF affichait
    // le nom TEL QUEL alors que le .docx le mettait en capitales.
    expect($docx())->toContain('JEAN DUPONT');
    expect(livretPdfHtml($event->fresh()))->toContain('JEAN DUPONT')->not->toContain('Jean Dupont');

    // Style « general » : « NOM Prénom » tel quel dans les DEUX rendus.
    EventSettings::set($event, 'livret.template', 'general');
    expect($docx())->toContain('Jean Dupont')->not->toContain('JEAN DUPONT');
    expect(livretPdfHtml($event->fresh()))->toContain('Jean Dupont')->not->toContain('JEAN DUPONT');
});
