<?php

use App\Actions\IssueAttestation;
use App\Models\Attestation;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// openEdition()/verifiedAccount()/adminStaff() -> tests/Pest.php + AdminSubmissionWorkflowTest

function enrolledFormation(Formation $formation, User $user, string $status): Participant
{
    $participant = $formation->edition->participants()->create([
        'first_name' => $user->first_name ?: 'X', 'last_name' => $user->last_name ?: 'Y',
        'email' => $user->email, 'role' => 'trainee', 'user_id' => $user->id,
    ]);
    FormationEnrollment::create([
        'formation_id' => $formation->id, 'edition_id' => $formation->edition_id,
        'user_id' => $user->id, 'participant_id' => $participant->id, 'status' => $status, 'amount' => $formation->fee,
    ]);

    return $participant;
}

it('émet participation aux inscrits réglés et animation aux formateurs, en nommant la formation', function () {
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Rédaction scientifique', 'fee' => 10000, 'published' => true]);

    $participant = enrolledFormation($formation, verifiedAccount(), 'paid');
    $formation->trainers()->create(['name' => 'Pr. Ben Co', 'email' => 'ben@example.tg', 'is_moderator' => true]);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.formations.attestations', $formation));

    // Participation (inscrit réglé) nommant la formation.
    $att = Attestation::where('type', 'formation')->where('participant_id', $participant->id)->first();
    expect($att)->not->toBeNull();
    expect((int) $att->formation_id)->toBe($formation->id);
    expect($att->meta['formation_title'])->toBe('Rédaction scientifique');

    // Animation (formateur -> participant support créé par e-mail).
    $trainerP = Participant::where('edition_id', $edition->id)->where('email', 'ben@example.tg')->first();
    expect($trainerP)->not->toBeNull();
    $anim = Attestation::where('type', 'formation_animation')->where('participant_id', $trainerP->id)->first();
    expect($anim)->not->toBeNull();
    expect((int) $anim->formation_id)->toBe($formation->id);
});

it('n’émet pas d’attestation à un inscrit non réglé d’une formation payante', function () {
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Payante', 'fee' => 10000, 'published' => true]);
    $participant = enrolledFormation($formation, verifiedAccount(), 'registered');

    $this->actingAs(adminStaff(), 'web')->post(route('admin.formations.attestations', $formation));

    expect(Attestation::where('type', 'formation')->where('participant_id', $participant->id)->exists())->toBeFalse();
});

it('émet l’attestation à un inscrit d’une formation gratuite', function () {
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Gratuite', 'fee' => 0, 'published' => true]);
    $participant = enrolledFormation($formation, verifiedAccount(), 'registered');

    $this->actingAs(adminStaff(), 'web')->post(route('admin.formations.attestations', $formation));

    expect(Attestation::where('type', 'formation')->where('participant_id', $participant->id)->exists())->toBeTrue();
});

it('expose le type d’attestation d’animation de formation', function () {
    expect(Attestation::TYPES)->toHaveKey('formation_animation');
});

it('téléverse la signature d’un formateur', function () {
    Storage::fake('public');
    $edition = openEdition(['formation_enabled' => true]);
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Atelier', 'fee' => 0, 'published' => true]);
    $trainer = $formation->trainers()->create(['name' => 'Awa Kodjo', 'title' => 'Pr.']);

    $res = $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.formations.trainers.signature', [$formation, $trainer]), [
            'signature' => UploadedFile::fake()->image('sign.png'),
        ]);

    $res->assertOk()->assertJson(['ok' => true]);
    expect($trainer->fresh()->signature_path)->not->toBeNull();
    expect($trainer->fresh()->signatureUrl())->toContain('/storage/');
    expect($trainer->fresh()->displayName())->toBe('Pr. Awa Kodjo');
});

it('fige le thème et les signatures des formateurs sur l’attestation de formation', function () {
    Storage::fake('public');
    $edition = openEdition(['formation_enabled' => true]);
    $formation = Formation::create([
        'edition_id' => $edition->id, 'title' => 'Rédaction scientifique',
        'theme' => 'Publier dans une revue indexée', 'fee' => 0, 'published' => true,
    ]);
    $trainer = $formation->trainers()->create(['name' => 'Awa Kodjo', 'title' => 'Pr.']);
    $trainer->update(['signature_path' => 'formation-signatures/sign.png']);

    $user = verifiedAccount();
    $participant = enrolledFormation($formation, $user, 'registered');

    $attestation = app(IssueAttestation::class)->handle($participant, 'formation', null, $formation);

    expect($attestation->meta['formation_title'])->toBe('Rédaction scientifique');
    expect($attestation->meta['formation_theme'])->toBe('Publier dans une revue indexée');
    expect($attestation->meta['formation_trainers'])->toBe(['Pr. Awa Kodjo']);
    expect($attestation->meta['formation_signatures'][0]['name'])->toBe('Pr. Awa Kodjo');
    expect($attestation->meta['formation_signatures'][0]['src'])->toContain('sign.png');
});
