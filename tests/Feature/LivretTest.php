<?php

use App\Documents\LivretTemplate;
use App\Models\CommitteeMember;
use App\Models\User;
use App\Support\EditionSettings;
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
    $edition = $submission->edition;

    $res = $this->actingAs($staff, 'web')
        ->get(route('admin.documents.livret', ['edition' => $edition, 'format' => 'docx']));

    $res->assertOk();
    expect($res->headers->get('content-type'))
        ->toContain('officedocument.wordprocessingml.document');

    $xml = livretDocumentXml($res->streamedContent());
    expect($xml)->toContain('Résumés des communications')
        ->toContain('Innovation durable')   // titre de la communication acceptée
        ->toContain('Index des auteurs')     // la nouvelle section
        ->toContain('AWA KODJO')             // l'auteur indexé (nom en MAJUSCULES)
        ->toContain('26-001');               // la référence (renvoi de l'index)
});

it('insère les sections de tête génériques seulement si elles sont remplies', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $edition = $submission->edition;

    // 1) Rien de rempli : aucune section de tête (hors avant-propos éventuel).
    $xml = livretDocumentXml(
        $this->actingAs($staff, 'web')
            ->get(route('admin.documents.livret', ['edition' => $edition, 'format' => 'docx']))
            ->streamedContent()
    );
    expect($xml)->not->toContain('Haut patronage de la ministre')
        ->not->toContain('Monsieur le Président de l’Université');

    // 2) Sections remplies : elles apparaissent dans le .docx.
    EditionSettings::set($edition, 'livret.patronage', "Sous le haut patronage de la ministre\nProf Exemple");
    EditionSettings::set($edition, 'livret.acknowledgements', "Monsieur le Président de l’Université\nMonsieur le Préfet");
    EditionSettings::set($edition, 'livret.welcome_text', 'Chères participantes, chers participants, bienvenue.');
    EditionSettings::set($edition, 'livret.welcome_title', 'Mot de bienvenue du Doyen');
    EditionSettings::set($edition, 'livret.president_text', 'C’est avec plaisir que nous vous accueillons.');

    $xml = livretDocumentXml(
        $this->actingAs($staff, 'web')
            ->get(route('admin.documents.livret', ['edition' => $edition, 'format' => 'docx']))
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
    $edition = $submission->edition;

    // Un comité pour rendre visible la différence de titre entre les deux styles.
    CommitteeMember::create([
        'edition_id' => $edition->id,
        'person_name' => 'Jean Test',
        'role' => 'organisation',
        'ordre' => 1,
    ]);

    // Style par défaut (UK/UP) : « Différentes commissions… », commissions numérotées.
    $xmlDefault = livretDocumentXml(
        $this->actingAs($staff, 'web')
            ->get(route('admin.documents.livret', ['edition' => $edition, 'format' => 'docx']))
            ->streamedContent()
    );
    expect($xmlDefault)->toContain('Différentes commissions et programmes du colloque');

    // Style FSS sélectionné : titre « Comités », sans le libellé UK/UP.
    EditionSettings::set($edition, 'livret.template', 'fss');
    $xmlFss = livretDocumentXml(
        $this->actingAs($staff, 'web')
            ->get(route('admin.documents.livret', ['edition' => $edition, 'format' => 'docx']))
            ->streamedContent()
    );
    expect($xmlFss)->toContain('Comités')
        ->toContain('INNOVATION DURABLE')   // titres de résumé en MAJUSCULES (style FSS)
        ->not->toContain('Différentes commissions et programmes du colloque');
});

it('génère le livret en PDF', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');

    $res = $this->actingAs($staff, 'web')
        ->get(route('admin.documents.livret', ['edition' => $submission->edition, 'format' => 'pdf']));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('pdf');
    expect(substr((string) $res->getContent(), 0, 5))->toBe('%PDF-');
});

it('génère le livret à partir d’un modèle .docx personnalisé (balises remplies)', function () {
    Storage::fake('public');

    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $edition = $submission->edition;

    // Un vrai modèle à balises = le modèle de base fourni à l'admin.
    $tmp = tempnam(sys_get_temp_dir(), 'tpl');
    (new Word2007(LivretTemplate::baseModel()))->save($tmp);
    Storage::disk('public')->put('livret-templates/modele.docx', (string) file_get_contents($tmp));
    @unlink($tmp);

    EditionSettings::set($edition, 'livret.templates', [['name' => 'modele.docx', 'path' => 'livret-templates/modele.docx']]);
    EditionSettings::set($edition, 'livret.template', 'modele.docx');

    $res = $this->actingAs($staff, 'web')
        ->get(route('admin.documents.livret', ['edition' => $edition, 'format' => 'docx']));

    $res->assertOk();
    $xml = livretDocumentXml($res->streamedContent());

    // Les balises bloc/inline ont été remplacées par le contenu généré.
    expect($xml)->toContain('Innovation durable')   // [[RESUMES]] -> résumés
        ->toContain($edition->title)                 // [[TITRE]] -> titre de l'édition
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
        ->get(route('admin.documents.livret', ['edition' => $submission->edition, 'format' => 'docx']))
        ->assertForbidden();
});
