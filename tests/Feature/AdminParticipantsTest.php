<?php

use App\Livewire\Admin\Participants\Index;
use App\Mail\ParticipantInvitationMail;
use App\Models\Participant;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

it('liste les participants d’une édition avec rôle et paiement', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $edition->payments()->create(['participant_id' => $p->id, 'amount' => 15000, 'status' => 'paid']);

    Livewire::actingAs($staff, 'web')
        ->test(Index::class)
        ->set('edition_id', (string) $edition->id)
        ->assertSee('Awa Kodjo')
        ->assertSee('Communicant')
        ->assertSee('Réglé');
});

it('invite un participant et le marque invité', function () {
    Mail::fake();
    $staff = adminStaff();
    $edition = openEdition();
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    Livewire::actingAs($staff, 'web')
        ->test(Index::class)
        ->set('edition_id', (string) $edition->id)
        ->call('invite', $p->id);

    Mail::assertSent(ParticipantInvitationMail::class, fn ($m) => $m->hasTo('awa@example.tg'));
    expect($p->fresh()->isInvited())->toBeTrue();
});

it('invite en masse les payés non invités uniquement', function () {
    Mail::fake();
    $staff = adminStaff();
    $edition = openEdition();

    $paid = $edition->participants()->create(['first_name' => 'A', 'last_name' => 'Payé', 'email' => 'paid@example.tg', 'role' => 'author']);
    $edition->payments()->create(['participant_id' => $paid->id, 'amount' => 15000, 'status' => 'paid']);

    $unpaid = $edition->participants()->create(['first_name' => 'B', 'last_name' => 'Impayé', 'email' => 'unpaid@example.tg', 'role' => 'author']);

    $alreadyInvited = $edition->participants()->create(['first_name' => 'C', 'last_name' => 'Invité', 'email' => 'inv@example.tg', 'role' => 'author', 'meta' => ['invited_at' => now()->toDateTimeString()]]);
    $edition->payments()->create(['participant_id' => $alreadyInvited->id, 'amount' => 15000, 'status' => 'paid']);

    Livewire::actingAs($staff, 'web')
        ->test(Index::class)
        ->set('edition_id', (string) $edition->id)
        ->call('invitePaidUninvited');

    // Seul le payé non invité reçoit l'e-mail.
    Mail::assertSent(ParticipantInvitationMail::class, 1);
    Mail::assertSent(ParticipantInvitationMail::class, fn ($m) => $m->hasTo('paid@example.tg'));
    expect($paid->fresh()->isInvited())->toBeTrue();
    expect($unpaid->fresh()->isInvited())->toBeFalse();
});
