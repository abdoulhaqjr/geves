<?php

use App\Mail\ParticipantInvitationMail;
use Illuminate\Support\Facades\Mail;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

it('liste les participants d’une événement avec rôle et paiement', function () {
    $staff = adminStaff();
    $event = openEdition();
    $p = $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $event->payments()->create(['participant_id' => $p->id, 'amount' => 15000, 'status' => 'paid']);

    $this->actingAs($staff, 'web')
        ->get(route('admin.participants.index', ['event_id' => $event->id]))
        ->assertOk()
        ->assertSee('Awa Kodjo')
        ->assertSee('Communicant')
        ->assertSee('Réglé');
});

it('invite un participant et le marque invité', function () {
    Mail::fake();
    $staff = adminStaff();
    $event = openEdition();
    $p = $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    $this->actingAs($staff, 'web')
        ->post(route('admin.participants.invite', $p))
        ->assertRedirect();

    Mail::assertQueued(ParticipantInvitationMail::class, fn ($m) => $m->hasTo('awa@example.tg'));
    expect($p->fresh()->isInvited())->toBeTrue();
});

it('invite en masse les payés non invités uniquement', function () {
    Mail::fake();
    $staff = adminStaff();
    $event = openEdition();

    $paid = $event->participants()->create(['first_name' => 'A', 'last_name' => 'Payé', 'email' => 'paid@example.tg', 'role' => 'author']);
    $event->payments()->create(['participant_id' => $paid->id, 'amount' => 15000, 'status' => 'paid']);

    $unpaid = $event->participants()->create(['first_name' => 'B', 'last_name' => 'Impayé', 'email' => 'unpaid@example.tg', 'role' => 'author']);

    $alreadyInvited = $event->participants()->create(['first_name' => 'C', 'last_name' => 'Invité', 'email' => 'inv@example.tg', 'role' => 'author', 'meta' => ['invited_at' => now()->toDateTimeString()]]);
    $event->payments()->create(['participant_id' => $alreadyInvited->id, 'amount' => 15000, 'status' => 'paid']);

    $this->actingAs($staff, 'web')
        ->post(route('admin.participants.invite-bulk'), ['event_id' => (string) $event->id])
        ->assertRedirect();

    // Seul le payé non invité reçoit l'e-mail.
    Mail::assertQueued(ParticipantInvitationMail::class, 1);
    Mail::assertQueued(ParticipantInvitationMail::class, fn ($m) => $m->hasTo('paid@example.tg'));
    expect($paid->fresh()->isInvited())->toBeTrue();
    expect($unpaid->fresh()->isInvited())->toBeFalse();
});
