<?php

use App\Livewire\Admin\BadgeScan;
use App\Models\Participant;
use Livewire\Livewire;

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

    Livewire::actingAs(adminStaff(), 'web')
        ->test(BadgeScan::class)
        ->call('lookup', $p->edition_id.'-'.$p->id)
        ->assertSet('found', fn ($f) => $f['id'] === $p->id && $f['checkedIn'] === false)
        ->assertSee('Awa Kodjo');
});

it('enregistre la présence puis reste idempotent', function () {
    $p = scannableParticipant();

    $test = Livewire::actingAs(adminStaff(), 'web')
        ->test(BadgeScan::class)
        ->call('lookup', (string) $p->id)
        ->call('checkIn')
        ->assertSet('found', fn ($f) => $f['checkedIn'] === true);

    expect($p->fresh()->isCheckedIn())->toBeTrue();

    $test->call('checkIn')->assertSet('message', fn ($m) => str_contains($m, 'déjà'));
});

it('signale un badge non reconnu', function () {
    Livewire::actingAs(adminStaff(), 'web')
        ->test(BadgeScan::class)
        ->call('lookup', '999999')
        ->assertSet('found', null)
        ->assertSee('non reconnu', false);
});
