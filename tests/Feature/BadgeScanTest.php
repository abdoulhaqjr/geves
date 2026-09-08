<?php

use App\Models\EventSession;
use App\Models\Participant;
use App\Models\SessionAttendance;

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

it('en mode séance, pointe la présence à la séance (et vaut présence à l’accueil)', function () {
    $p = scannableParticipant();
    $session = EventSession::create([
        'edition_id' => $p->edition_id, 'label' => 'Session inaugurale',
        'session_day' => '2026-09-03', 'starts_at' => '2026-09-03 09:00',
    ]);

    $resp = $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.badges.checkin', $p), ['session_id' => $session->id])
        ->assertOk()
        ->assertJsonPath('newlyCheckedIn', true)
        ->assertJsonPath('found.inSession', true);

    expect($resp->json('message'))->toContain('Session inaugurale');
    expect(SessionAttendance::where('session_id', $session->id)->where('participant_id', $p->id)->exists())->toBeTrue();
    expect($p->fresh()->isCheckedIn())->toBeTrue(); // présence séance ⇒ présence accueil
    expect($p->fresh()->wasPresent())->toBeTrue();

    // Idempotent : un second scan ne crée pas de doublon.
    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.badges.checkin', $p), ['session_id' => $session->id])
        ->assertJsonPath('newlyCheckedIn', false);
    expect(SessionAttendance::where('session_id', $session->id)->count())->toBe(1);
});

it('refuse de pointer un participant d’une autre édition à la séance', function () {
    $p = scannableParticipant();
    $other = openEdition();
    $session = EventSession::create([
        'edition_id' => $other->id, 'label' => 'Séance ailleurs', 'session_day' => '2026-09-03',
    ]);

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.badges.checkin', $p), ['session_id' => $session->id])
        ->assertStatus(422)
        ->assertJsonPath('found.sessionMismatch', true);

    expect(SessionAttendance::count())->toBe(0);
});
