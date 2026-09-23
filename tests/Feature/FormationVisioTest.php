<?php

use App\Enums\MeetingRole;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\User;

// openEdition()/verifiedAccount()/adminStaff() -> tests/Pest.php

function liveFormation($edition, float $fee = 10000): Formation
{
    return Formation::create([
        'edition_id' => $edition->id, 'title' => 'Formation visio', 'fee' => $fee,
        'published' => true, 'meeting_status' => 'live', 'meeting_provider' => 'jitsi',
    ]);
}

it('calcule le rôle visio : staff & formateur = modérateur, inscrit réglé = participant, autre = aucun', function () {
    $edition = openEdition();
    $formation = liveFormation($edition);

    $trainerUser = verifiedAccount();
    $formation->trainers()->create(['name' => 'Formateur', 'email' => $trainerUser->email, 'user_id' => $trainerUser->id]);

    $paid = User::create(['name' => 'Inscrit Payé', 'first_name' => 'Ins', 'last_name' => 'Payé', 'email' => 'paid@example.tg', 'password' => 'pass12345']);
    $p = $edition->participants()->create(['first_name' => 'Ins', 'last_name' => 'Payé', 'email' => 'paid@example.tg', 'role' => 'trainee', 'user_id' => $paid->id]);
    FormationEnrollment::create(['formation_id' => $formation->id, 'edition_id' => $edition->id, 'user_id' => $paid->id, 'participant_id' => $p->id, 'status' => 'paid', 'amount' => 10000]);

    $random = User::create(['name' => 'Autre', 'first_name' => 'Au', 'last_name' => 'Tre', 'email' => 'autre@example.tg', 'password' => 'pass12345']);

    expect($formation->meetingRoleFor(null, true))->toBe(MeetingRole::Moderator);
    expect($formation->meetingRoleFor($trainerUser, false))->toBe(MeetingRole::Moderator);
    expect($formation->meetingRoleFor($paid, false))->toBe(MeetingRole::Attendee);
    expect($formation->meetingRoleFor($random, false))->toBe(MeetingRole::None);
    expect($formation->canJoin($paid, false))->toBeTrue();
    expect($formation->canJoin($random, false))->toBeFalse();
});

it('un inscrit non réglé d’une formation payante ne peut pas rejoindre', function () {
    $edition = openEdition();
    $formation = liveFormation($edition);
    $user = verifiedAccount();
    $p = $edition->participants()->create(['first_name' => 'A', 'last_name' => 'K', 'email' => $user->email, 'role' => 'trainee', 'user_id' => $user->id]);
    FormationEnrollment::create(['formation_id' => $formation->id, 'edition_id' => $edition->id, 'user_id' => $user->id, 'participant_id' => $p->id, 'status' => 'registered', 'amount' => 10000]);

    expect($formation->canJoin($user, false))->toBeFalse();
});

it('redirige vers la salle quand la visio est en cours et l’accès autorisé', function () {
    $edition = openEdition();
    $formation = liveFormation($edition);
    $user = verifiedAccount();
    $p = $edition->participants()->create(['first_name' => 'A', 'last_name' => 'K', 'email' => $user->email, 'role' => 'trainee', 'user_id' => $user->id]);
    FormationEnrollment::create(['formation_id' => $formation->id, 'edition_id' => $edition->id, 'user_id' => $user->id, 'participant_id' => $p->id, 'status' => 'paid', 'amount' => 10000]);

    $resp = $this->actingAs($user, 'account')->get(route('account.formations.visio', $formation));
    $resp->assertRedirect();
    expect($resp->headers->get('Location'))->toContain('meet.jit.si');
});

it('bloque la visio si non en cours ou accès refusé', function () {
    $edition = openEdition();
    // Non en cours
    $off = Formation::create(['edition_id' => $edition->id, 'title' => 'Off', 'fee' => 0, 'published' => true, 'meeting_status' => 'none']);
    $this->actingAs(verifiedAccount(), 'account')->get(route('account.formations.visio', $off))
        ->assertRedirect(route('account.formations'));

    // En cours mais non inscrit
    $live = liveFormation($edition);
    $stranger = User::create(['name' => 'Etranger', 'first_name' => 'E', 'last_name' => 'T', 'email' => 'stranger@example.tg', 'password' => 'pass12345']);
    $this->actingAs($stranger, 'account')->get(route('account.formations.visio', $live))
        ->assertRedirect(route('account.formations'));
});

it('permet à l’admin de configurer la visio', function () {
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'X', 'fee' => 0, 'published' => true]);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.formations.meeting', $formation), [
        'meeting_provider' => 'jitsi', 'meeting_status' => 'live', 'meeting_room' => 'salle-form-1', 'meeting_visibility' => 'participants',
    ]);

    $formation->refresh();
    expect($formation->isLive())->toBeTrue();
    expect($formation->meetingRoom())->toBe('salle-form-1');
});
