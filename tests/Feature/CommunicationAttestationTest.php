<?php

use App\Actions\IssueAttestation;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\SessionAttendance;
use App\Models\Slot;
use App\Models\Submission;
use App\Support\AttestationPolicy;
use App\Support\EditionSettings;
use Illuminate\Support\Str;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> tests/Pest.php

/** Crée une communication acceptée avec un auteur correspondant + des co-auteurs. */
function acceptedComm(Edition $edition, Participant $author, string $ref, array $coauthors = []): Submission
{
    $authors = [['name' => $author->full_name, 'email' => $author->email, 'corresponding' => true]];
    foreach ($coauthors as $co) {
        $authors[] = ['name' => $co->full_name, 'email' => $co->email, 'corresponding' => false];
    }

    return $edition->submissions()->create([
        'participant_id' => $author->id,
        'title' => 'Communication '.$ref,
        'status' => 'accepted',
        'revision_of' => 0,
        'ref_code' => $ref,
        'access_token' => Str::random(64),
        'track' => 'Sciences',
        'type' => 'oral',
        'authors' => $authors,
    ]);
}

function payParticipant(Edition $edition, Participant $p): void
{
    $edition->payments()->create([
        'participant_id' => $p->id, 'amount' => 20000, 'status' => 'paid',
        'gateway' => 'manual', 'method' => 'cash', 'paid_at' => now(),
    ]);
}

it('émet une attestation de communication par personne ET par communication (au nom de chacun)', function () {
    $edition = openEdition();
    $author = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $co = $edition->participants()->create(['first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'ben@example.tg', 'role' => 'coauthor']);
    payParticipant($edition, $co);

    $sub = acceptedComm($edition, $author, 'AA-001', [$co]);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'edition_id' => (string) $edition->id, 'type' => 'communication',
    ]);

    // Auteur correspondant + co-auteur payé, chacun à son nom, liés à la communication.
    $authorAtt = Attestation::where('participant_id', $author->id)->where('type', 'communication')->first();
    $coAtt = Attestation::where('participant_id', $co->id)->where('type', 'communication')->first();

    expect($authorAtt)->not->toBeNull();
    expect($coAtt)->not->toBeNull();
    expect($authorAtt->submission_id)->toBe($sub->id);
    expect($coAtt->submission_id)->toBe($sub->id);
    expect($authorAtt->recipient_name)->toBe($author->full_name);
    expect($coAtt->recipient_name)->toBe($co->full_name);
    expect($authorAtt->meta['communication_title'])->toBe($sub->title);
});

it('ne donne pas d’attestation à un co-auteur non payé', function () {
    $edition = openEdition();
    $author = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $co = $edition->participants()->create(['first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'ben@example.tg', 'role' => 'coauthor']);

    acceptedComm($edition, $author, 'AA-001', [$co]);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'edition_id' => (string) $edition->id, 'type' => 'communication',
    ]);

    expect(Attestation::where('participant_id', $co->id)->exists())->toBeFalse();
    expect(Attestation::where('participant_id', $author->id)->where('type', 'communication')->exists())->toBeTrue();
});

it('plafonne le nombre de co-auteurs attestés par communication (coauthors_max)', function () {
    $edition = openEdition(['coauthors_max' => 1]);
    $author = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $co1 = $edition->participants()->create(['first_name' => 'Ben', 'last_name' => 'Un', 'email' => 'co1@example.tg', 'role' => 'coauthor']);
    $co2 = $edition->participants()->create(['first_name' => 'Cid', 'last_name' => 'Deux', 'email' => 'co2@example.tg', 'role' => 'coauthor']);
    payParticipant($edition, $co1);
    payParticipant($edition, $co2);

    acceptedComm($edition, $author, 'AA-001', [$co1, $co2]);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'edition_id' => (string) $edition->id, 'type' => 'communication',
    ]);

    // Seul le 1er co-auteur (ordre de la liste) est attesté.
    expect(Attestation::where('participant_id', $co1->id)->exists())->toBeTrue();
    expect(Attestation::where('participant_id', $co2->id)->exists())->toBeFalse();
});

it('plafonne par personne et privilégie l’auteur correspondant (attestation_max)', function () {
    $edition = openEdition(['attestation_max' => 1]);

    // La personne P est auteur correspondant de A et co-auteur (payé) de B.
    $p = $edition->participants()->create(['first_name' => 'Pia', 'last_name' => 'Prime', 'email' => 'p@example.tg', 'role' => 'author']);
    payParticipant($edition, $p);
    $q = $edition->participants()->create(['first_name' => 'Qua', 'last_name' => 'Autre', 'email' => 'q@example.tg', 'role' => 'author']);

    $subA = acceptedComm($edition, $p, 'AA-001');           // P = correspondant
    acceptedComm($edition, $q, 'AA-002', [$p]);             // P = co-auteur payé

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'edition_id' => (string) $edition->id, 'type' => 'communication',
    ]);

    $pAtts = Attestation::where('participant_id', $p->id)->where('type', 'communication')->get();
    expect($pAtts)->toHaveCount(1);
    // On garde celle où P est auteur correspondant (communication A).
    expect($pAtts->first()->submission_id)->toBe($subA->id);
});

it('nomme la communication dans un modèle personnalisé via {communication}', function () {
    $edition = openEdition();
    $author = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $sub = acceptedComm($edition, $author, 'CI-042');

    // Modèle personnalisé d'attestation de communication utilisant {communication}.
    DocumentTemplate::create([
        'edition_id' => $edition->id,
        'kind' => 'attestation',
        'type' => 'communication',
        'layout' => ['width' => 600, 'height' => 400, 'bg' => '#ffffff', 'accent' => '#223ea4', 'elements' => [
            ['type' => 'text', 'x' => 20, 'y' => 20, 'w' => 560, 'h' => 40, 'text' => 'Communication : {communication} ({communication_ref})', 'size' => 16, 'weight' => 700, 'color' => '#111111', 'align' => 'center'],
        ]],
    ]);

    $att = app(IssueAttestation::class)->handle($author, 'communication', $sub);

    $this->get(route('attestation.document', $att->uuid))
        ->assertOk()
        ->assertSee($sub->title)
        ->assertSee('CI-042');
});

/**
 * ⚠️ L'émission en masse suit la MÊME option que la salle : « seulement les
 * présents » s'appuie sur le pointage de la séance où la communication est
 * programmée. Deux chemins qui en décideraient chacun de leur côté enverraient
 * des attestations différentes selon le bouton utilisé.
 */
it('réserve l’émission en masse aux présents quand l’édition le demande', function () {
    $edition = openEdition();
    EditionSettings::set($edition, 'attestations.communication_scope', AttestationPolicy::SCOPE_PRESENT);

    $auteur = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $co = $edition->participants()->create(['first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'ben@example.tg', 'role' => 'coauthor']);
    payParticipant($edition, $co);
    $sub = acceptedComm($edition, $auteur, 'AA-010', [$co]);

    $session = $edition->sessions()->create(['label' => 'Séance', 'type' => 'atelier', 'starts_at' => now()->subHour()]);
    Slot::create([
        'edition_id' => $edition->id, 'session_id' => $session->id,
        'submission_id' => $sub->id, 'participant_id' => $auteur->id,
        'starts_at' => $session->starts_at, 'ends_at' => $session->starts_at->copy()->addMinutes(20),
    ]);
    SessionAttendance::create([
        'session_id' => $session->id, 'participant_id' => $auteur->id,
        'status' => 'present', 'source' => SessionAttendance::SOURCE_MANUAL,
    ]);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'edition_id' => (string) $edition->id, 'type' => 'communication',
    ]);

    expect(Attestation::where('participant_id', $auteur->id)->where('type', 'communication')->exists())->toBeTrue();
    expect(Attestation::where('participant_id', $co->id)->where('type', 'communication')->exists())->toBeFalse();
});

/**
 * ⚠️ Une communication **non programmée** n'a aucune séance où se pointer : la
 * réserve aux présents l'écarterait pour de bon. C'est au programme qu'il manque
 * quelque chose, pas à l'auteur.
 */
it('n’écarte pas une communication sans séance au programme', function () {
    $edition = openEdition();
    EditionSettings::set($edition, 'attestations.communication_scope', AttestationPolicy::SCOPE_PRESENT);

    $auteur = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    acceptedComm($edition, $auteur, 'AA-011');

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'edition_id' => (string) $edition->id, 'type' => 'communication',
    ]);

    expect(Attestation::where('participant_id', $auteur->id)->where('type', 'communication')->exists())->toBeTrue();
});

/** Les options de délivrance font l'aller-retour par le formulaire du hub. */
it('enregistre le mode de délivrance depuis les options générales', function () {
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.options'), [
        'edition_id' => (string) $edition->id,
        'attDelivery' => AttestationPolicy::DELIVERY_PAPER,
        'attCommunicationScope' => AttestationPolicy::SCOPE_PRESENT,
        'attSms' => '1',
    ])->assertRedirect();

    $edition->refresh();
    expect(AttestationPolicy::isPaper($edition))->toBeTrue();
    expect(AttestationPolicy::requiresPresence($edition))->toBeTrue();
});
