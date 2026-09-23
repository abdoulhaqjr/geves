<?php

use App\Models\Attestation;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Participant;
use App\Models\User;

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
