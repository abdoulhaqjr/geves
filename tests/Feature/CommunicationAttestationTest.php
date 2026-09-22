<?php

use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Submission;
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
