<?php

use App\Models\User;

// adminStaff() / openEdition() : fabriques partagées de tests/Pest.php.

it('ouvre l’aperçu d’attestation sur le concepteur verrouillé (canevas exact + valeurs d’exemple)', function () {
    $staff = adminStaff();
    $event = openEdition();

    $res = $this->actingAs($staff, 'web')->get(route('admin.documents.templates.preview', [
        'event_id' => $event->id, 'kind' => 'attestation', 'type' => 'participation',
    ]));

    $res->assertOk();
    expect($res->getContent())
        ->toContain('"preview":true')  // concepteur ouvert en mode aperçu verrouillé
        ->toContain('Awa Kodjo');      // valeurs d'exemple fournies au canevas (jetons remplis côté client)
});

it('ouvre l’aperçu de badge sur le concepteur verrouillé', function () {
    $staff = adminStaff();
    $event = openEdition();

    $res = $this->actingAs($staff, 'web')->get(route('admin.documents.templates.preview', [
        'event_id' => $event->id, 'kind' => 'badge',
    ]));

    $res->assertOk();
    expect($res->getContent())->toContain('"preview":true')->toContain('Awa Kodjo');
});

it('affiche un aperçu de reçu avec des valeurs d’exemple', function () {
    $staff = adminStaff();
    $event = openEdition();

    $res = $this->actingAs($staff, 'web')->get(route('admin.payments.receipt.sample', ['event_id' => $event->id]));

    $res->assertOk();
    expect($res->getContent())
        ->toContain('EXEMPLE-0001')            // reçu réel rendu avec des valeurs d'exemple
        ->toContain('Awa Kodjo')
        ->toContain('Aperçu du reçu - valeurs d\'exemple') // cadre d'aperçu verrouillé (comme attestation/badge)
        ->toContain('Fermer');                 // bouton de retour aux paiements
});

/**
 * ⚠️ **L'aperçu inline est désormais une PAGE qui PORTE le PDF**, depuis
 * « Afficher les PDF avec l'identité GEVES » : barre GEVES, titre, bouton de
 * téléchargement, et le document dans un cadre. Ce cas exigeait encore un
 * `content-type` de PDF - il mesurait l'ancienne forme, pas l'intention.
 *
 * ⚠️ Ce qu'il faut tenir n'a pas changé : le document **s'affiche**, il ne se
 * télécharge pas. On le vérifie donc sur les DEUX maillons - la page porte bien
 * l'adresse du document, et cette adresse le sert **inline**. S'arrêter à la
 * page laisserait passer un cadre vide.
 */
it('affiche l’aperçu du livret dans une page GEVES, document inline', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');

    $pdfUrl = route('admin.documents.livret', [
        'event' => $submission->event, 'format' => 'pdf', 'inline' => 1, 'raw' => 1,
    ]);

    $page = $this->actingAs($staff, 'web')->get(
        route('admin.documents.livret', ['event' => $submission->event, 'format' => 'pdf', 'inline' => 1])
    )->assertOk();

    expect($page->headers->get('content-type'))->toContain('text/html');
    // ⚠️ L'adresse voyage ÉCHAPPÉE dans l'attribut (`&amp;`) : la comparer brute
    // échoue sur une page pourtant correcte.
    $page->assertSee('Télécharger le PDF')->assertSee(e($pdfUrl), false);

    $document = $this->actingAs($staff, 'web')->get($pdfUrl)->assertOk();
    expect($document->headers->get('content-type'))->toContain('pdf');
    expect($document->headers->get('content-disposition'))->toContain('inline');
});

it('protège les aperçus derrière la capacité documents', function () {
    $event = openEdition();
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->get(route('admin.documents.templates.preview', ['event_id' => $event->id, 'kind' => 'attestation', 'type' => 'participation']))
        ->assertForbidden();
});
