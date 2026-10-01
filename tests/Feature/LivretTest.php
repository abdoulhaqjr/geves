<?php

use App\Models\User;

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
        ->toContain('Awa Kodjo')             // l'auteur indexé
        ->toContain('26-001');               // la référence (renvoi de l'index)
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
    \Illuminate\Support\Facades\Storage::fake('public');

    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $edition = $submission->edition;

    // Un vrai modèle à balises = le modèle de base fourni à l'admin.
    $tmp = tempnam(sys_get_temp_dir(), 'tpl');
    (new \PhpOffice\PhpWord\Writer\Word2007(\App\Documents\LivretTemplate::baseModel()))->save($tmp);
    \Illuminate\Support\Facades\Storage::disk('public')->put('livret-templates/modele.docx', (string) file_get_contents($tmp));
    @unlink($tmp);

    \App\Support\EditionSettings::set($edition, 'livret.templates', [['name' => 'modele.docx', 'path' => 'livret-templates/modele.docx']]);
    \App\Support\EditionSettings::set($edition, 'livret.template', 'modele.docx');

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
