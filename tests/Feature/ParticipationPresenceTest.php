<?php

use App\Models\Attestation;
use App\Support\Settings;

// openEdition() / adminStaff() : helpers partagés (tests/Pest.php).

it('n’émet l’attestation de participation qu’aux présents quand l’option est active', function () {
    Settings::set('attestations.require_presence', true);

    $event = openEdition();

    // Réglé ET présent (pointé à l'accueil) → éligible.
    $present = $event->participants()->create([
        'first_name' => 'A', 'last_name' => 'Présent', 'email' => 'a@example.tg',
        'role' => 'author', 'checked_in_at' => now(),
    ]);
    $event->payments()->create(['participant_id' => $present->id, 'amount' => 15000, 'status' => 'paid']);

    // Réglé mais absent → exclu.
    $absent = $event->participants()->create([
        'first_name' => 'B', 'last_name' => 'Absent', 'email' => 'b@example.tg', 'role' => 'author',
    ]);
    $event->payments()->create(['participant_id' => $absent->id, 'amount' => 15000, 'status' => 'paid']);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'event_id' => (string) $event->id,
        'type' => 'participation',
    ]);

    expect(Attestation::count())->toBe(1);
    expect(Attestation::first()->participant_id)->toBe($present->id);
});

it('émet à tous les réglés quand l’option présence est désactivée (défaut)', function () {
    Settings::set('attestations.require_presence', false);

    $event = openEdition();
    $paid = $event->participants()->create([
        'first_name' => 'A', 'last_name' => 'Payé', 'email' => 'a@example.tg', 'role' => 'author',
    ]);
    $event->payments()->create(['participant_id' => $paid->id, 'amount' => 15000, 'status' => 'paid']);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'event_id' => (string) $event->id,
        'type' => 'participation',
    ]);

    expect(Attestation::count())->toBe(1);
});
