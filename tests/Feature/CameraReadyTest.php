<?php

use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// openEdition() et verifiedAccount() sont définis dans tests/Pest.php.

/** @return array{0: User, 1: Submission} */
function acceptedOwnedSubmission(string $status = 'accepted', string $ref = '26-CR'): array
{
    $edition = openEdition();
    $account = verifiedAccount();
    $participant = $edition->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);
    $submission = $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => $ref, 'title' => 'Communication',
        'abstract' => 'Résumé.', 'track' => 'Sciences', 'type' => 'oral', 'status' => $status, 'authors' => [],
    ]);

    return [$account, $submission];
}

/** @return array<string, string> */
function fullChecklist(): array
{
    return collect(array_keys(Submission::CAMERA_READY_CHECKLIST))
        ->mapWithKeys(fn ($k) => [$k => '1'])->all();
}

it('dépose une version définitive quand la communication est acceptée', function () {
    Storage::fake('public');
    [$account, $submission] = acceptedOwnedSubmission();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.camera-ready', $submission), [
            'file' => UploadedFile::fake()->create('final.pdf', 120, 'application/pdf'),
            'checklist' => fullChecklist(),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $cr = $submission->fresh()->cameraReady();
    expect($cr)->not->toBeNull()
        ->and($cr['file_url'])->toContain('camera-ready');

    // La fiche participant affiche la version définitive déposée.
    $this->actingAs($account, 'account')
        ->get(route('account.submissions.show', $submission))
        ->assertOk()
        ->assertSee('Version définitive');
});

it('refuse le dépôt si la checklist n’est pas entièrement confirmée', function () {
    Storage::fake('public');
    [$account, $submission] = acceptedOwnedSubmission();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.camera-ready', $submission), [
            'file' => UploadedFile::fake()->create('final.pdf', 120, 'application/pdf'),
            'checklist' => ['template' => '1'], // incomplet
        ])
        ->assertSessionHasErrors();

    expect($submission->fresh()->hasCameraReady())->toBeFalse();
});

it('interdit le dépôt tant que la communication n’est pas acceptée', function () {
    Storage::fake('public');
    [$account, $submission] = acceptedOwnedSubmission('under_review', '26-CR2');

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.camera-ready', $submission), [
            'file' => UploadedFile::fake()->create('final.pdf', 120, 'application/pdf'),
            'checklist' => fullChecklist(),
        ])
        ->assertForbidden();
});

it('interdit le dépôt sur la communication d’un autre compte', function () {
    Storage::fake('public');
    [, $submission] = acceptedOwnedSubmission();

    $other = User::create([
        'first_name' => 'Kofi', 'last_name' => 'Autre', 'email' => 'autre@example.tg', 'password' => 'pass12345',
    ]);
    $other->markEmailAsVerified();

    $this->actingAs($other, 'account')
        ->post(route('account.submissions.camera-ready', $submission), [
            'file' => UploadedFile::fake()->create('final.pdf', 120, 'application/pdf'),
            'checklist' => fullChecklist(),
        ])
        ->assertNotFound(); // authorizeOwnership masque l'existence (404)
});
