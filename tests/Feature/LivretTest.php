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

it('protège la génération du livret sans permission', function () {
    $submission = seededSubmission('accepted');
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->get(route('admin.documents.livret', ['edition' => $submission->edition, 'format' => 'docx']))
        ->assertForbidden();
});
