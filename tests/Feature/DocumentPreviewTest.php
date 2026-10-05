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

it('affiche l’aperçu du livret en PDF inline (rendu final)', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');

    $res = $this->actingAs($staff, 'web')->get(
        route('admin.documents.livret', ['event' => $submission->event, 'format' => 'pdf', 'inline' => 1])
    );

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('pdf');
    expect($res->headers->get('content-disposition'))->toContain('inline');
});

it('protège les aperçus derrière la capacité documents', function () {
    $event = openEdition();
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->get(route('admin.documents.templates.preview', ['event_id' => $event->id, 'kind' => 'attestation', 'type' => 'participation']))
        ->assertForbidden();
});
