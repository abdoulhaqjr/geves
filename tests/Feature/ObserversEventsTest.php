<?php

use App\Events\SubmissionStatusChanged;
use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Submission;
use Illuminate\Support\Facades\Event;

// adminStaff() -> AdminSubmissionWorkflowTest

it('génère ref_code et access_token à la création d’une communication', function () {
    $edition = Edition::factory()->create(['year' => 2027]);
    $participant = Participant::factory()->for($edition)->create();

    // Aucun ref_code / access_token fourni → l'observer les génère.
    $submission = $edition->submissions()->create([
        'participant_id' => $participant->id,
        'title' => 'Sans référence',
        'status' => 'submitted',
        'revision_of' => 0,
    ]);

    expect($submission->ref_code)->toMatch('/^27-\d{3}/');
    expect(strlen($submission->access_token))->toBe(64);
});

it('ne remplace pas un ref_code fourni explicitement', function () {
    $edition = Edition::factory()->create();
    $participant = Participant::factory()->for($edition)->create();

    $submission = $edition->submissions()->create([
        'participant_id' => $participant->id,
        'title' => 'Avec réf',
        'ref_code' => 'ZZ-999',
        'status' => 'submitted',
        'revision_of' => 0,
    ]);

    expect($submission->ref_code)->toBe('ZZ-999');
});

it('génère l’access_token d’un participant à la création', function () {
    $edition = Edition::factory()->create();

    $participant = $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);

    expect(strlen($participant->access_token))->toBe(64);
});

it('génère l’uuid d’une attestation à la création', function () {
    $edition = Edition::factory()->create();
    $participant = Participant::factory()->for($edition)->create();

    $attestation = Attestation::create([
        'edition_id' => $edition->id,
        'participant_id' => $participant->id,
        'type' => 'participation',
        'recipient_name' => $participant->full_name,
        'recipient_email' => $participant->email,
        'revoked' => false,
        'issued_at' => now(),
    ]);

    expect($attestation->uuid)->not->toBeEmpty();
    expect(Attestation::where('uuid', $attestation->uuid)->exists())->toBeTrue();
});

it('émet SubmissionStatusChanged à l’application d’un statut', function () {
    Event::fake([SubmissionStatusChanged::class]);
    $staff = adminStaff();
    $edition = Edition::factory()->create();
    $participant = Participant::factory()->for($edition)->create();
    $submission = Submission::factory()->for($edition)->for($participant)->create();

    $this->actingAs($staff, 'web')
        ->post(route('admin.submissions.status', $submission), ['status' => 'accepted']);

    Event::assertDispatched(SubmissionStatusChanged::class, fn ($e) => $e->status === 'accepted' && $e->submission->is($submission));
});
