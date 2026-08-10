<?php

use App\Livewire\Account\Submissions\Show as AccountSubmissionShow;
use App\Livewire\Admin\Program\Sessions as ProgramSessions;
use App\Livewire\Admin\Program\Slots as ProgramSlots;
use App\Models\Atelier;
use App\Models\EventSession;
use App\Models\Slot;
use Livewire\Livewire;

// adminStaff(), seededSubmission() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

it('crée un atelier et des sessions pour une édition', function () {
    $staff = adminStaff();
    $edition = openEdition();

    Livewire::actingAs($staff, 'web')
        ->test(ProgramSessions::class)
        ->set('edition_id', (string) $edition->id)
        ->set('atelierTitle', 'Atelier Santé')
        ->set('atelierTracks', ['Santé'])
        ->call('addAtelier')
        ->assertHasNoErrors();

    $atelier = Atelier::first();
    expect($atelier->title)->toBe('Atelier Santé');
    expect($atelier->tracks)->toBe(['Santé']);

    Livewire::actingAs($staff, 'web')
        ->test(ProgramSessions::class)
        ->set('edition_id', (string) $edition->id)
        ->set('sType', 'plenary')
        ->set('sLabel', 'Cérémonie d’ouverture')
        ->set('sDay', '2026-11-18')
        ->set('sStart', '09:00')
        ->call('addSession')
        ->assertHasNoErrors();

    $session = EventSession::first();
    expect($session->type)->toBe('plenary');
    expect($session->label)->toBe('Cérémonie d’ouverture');
    expect($session->starts_at->format('H:i'))->toBe('09:00');
});

it('crée un créneau et y assigne une communication acceptée', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $edition = $submission->edition;

    $c = Livewire::actingAs($staff, 'web')
        ->test(ProgramSlots::class)
        ->set('edition_id', (string) $edition->id)
        ->set('slRoom', 'Amphi A')
        ->set('slDay', '2026-11-18')
        ->set('slStart', '10:00')
        ->call('addSlot')
        ->assertHasNoErrors();

    $slot = Slot::first();
    expect($slot->room)->toBe('Amphi A');

    $c->call('assign', $slot->id, $submission->id)->assertHasNoErrors();
    expect($slot->fresh()->submission_id)->toBe($submission->id);
});

it('laisse le participant choisir un créneau libre pour sa communication acceptée', function () {
    $submission = seededSubmission('accepted');
    $account = $submission->participant->account;
    $slot = Slot::create(['edition_id' => $submission->edition_id, 'room' => 'Amphi B', 'submission_id' => 0]);

    Livewire::actingAs($account, 'account')
        ->test(AccountSubmissionShow::class, ['submission' => $submission->fresh()])
        ->call('chooseSlot', $slot->id)
        ->assertHasNoErrors();

    $slot->refresh();
    expect($slot->submission_id)->toBe($submission->id);
    expect($slot->chosen_by_participant)->toBeTrue();
});

it('refuse le choix de créneau si la communication n’est pas acceptée', function () {
    $submission = seededSubmission('under_review');
    $account = $submission->participant->account;
    $slot = Slot::create(['edition_id' => $submission->edition_id, 'room' => 'Amphi B', 'submission_id' => 0]);

    Livewire::actingAs($account, 'account')
        ->test(AccountSubmissionShow::class, ['submission' => $submission->fresh()])
        ->call('chooseSlot', $slot->id)
        ->assertStatus(403);
});
