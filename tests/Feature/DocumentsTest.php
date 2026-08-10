<?php

use App\Actions\IssueAttestation;
use App\Livewire\Admin\Documents\Attestations;
use App\Models\Attestation;
use App\Models\User;
use Livewire\Livewire;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest ; seededSubmission() -> idem

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

    Livewire::actingAs($staff, 'web')
        ->test(Attestations::class)
        ->set('edition_id', (string) $edition->id)
        ->set('type', 'participation')
        ->set('audience', 'paid')
        ->call('issue');

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

it('génère la planche de badges', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    $this->actingAs($staff, 'web')->get(route('admin.documents.badges', $edition))
        ->assertOk()
        ->assertSee('Awa Kodjo')
        ->assertSee('Communicant');
});

it('protège les documents admin sans permission', function () {
    $edition = openEdition();
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get(route('admin.documents.livret', $edition))->assertForbidden();
});
