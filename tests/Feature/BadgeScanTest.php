<?php

use App\Models\Participant;

// openEdition() -> AccountSubmissionTest ; adminStaff() -> helpers partagés

function scannableParticipant(): Participant
{
    $edition = openEdition();

    return $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg',
        'role' => 'author', 'institution' => 'Université de Kara',
    ]);
}

it('retrouve un participant depuis le code du badge', function () {
    $p = scannableParticipant();

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.badges.lookup'), ['code' => $p->edition_id.'-'.$p->id])
        ->assertOk()
        ->assertJsonPath('found.id', $p->id)
        ->assertJsonPath('found.checkedIn', false)
        ->assertJsonPath('found.name', 'Awa Kodjo');
});

it('enregistre la présence puis reste idempotent', function () {
    $p = scannableParticipant();

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.badges.lookup'), ['code' => (string) $p->id])
        ->assertJsonPath('found.checkedIn', false);

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.badges.checkin', $p))
        ->assertOk()
        ->assertJsonPath('found.checkedIn', true)
        ->assertJsonPath('newlyCheckedIn', true);

    expect($p->fresh()->isCheckedIn())->toBeTrue();

    $second = $this->actingAs(adminStaff(), 'web')->postJson(route('admin.badges.checkin', $p));
    $second->assertJsonPath('newlyCheckedIn', false);
    expect($second->json('message'))->toContain('déjà');
});

it('signale un badge non reconnu', function () {
    $resp = $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.badges.lookup'), ['code' => '999999'])
        ->assertOk()
        ->assertJsonPath('found', null);

    expect($resp->json('error'))->toContain('non reconnu');
});
