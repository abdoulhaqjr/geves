<?php

use App\Models\Attestation;
use App\Support\Settings;

// openEdition() / adminStaff() : helpers partagés (tests/Pest.php).

it('n’émet l’attestation de participation qu’aux présents quand l’option est active', function () {
    Settings::set('attestations.require_presence', true);

    $edition = openEdition();

    // Réglé ET présent (pointé à l'accueil) → éligible.
    $present = $edition->participants()->create([
        'first_name' => 'A', 'last_name' => 'Présent', 'email' => 'a@example.tg',
        'role' => 'author', 'checked_in_at' => now(),
    ]);
    $edition->payments()->create(['participant_id' => $present->id, 'amount' => 15000, 'status' => 'paid']);

    // Réglé mais absent → exclu.
    $absent = $edition->participants()->create([
        'first_name' => 'B', 'last_name' => 'Absent', 'email' => 'b@example.tg', 'role' => 'author',
    ]);
    $edition->payments()->create(['participant_id' => $absent->id, 'amount' => 15000, 'status' => 'paid']);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'edition_id' => (string) $edition->id,
        'type' => 'participation',
    ]);

    expect(Attestation::count())->toBe(1);
    expect(Attestation::first()->participant_id)->toBe($present->id);
});

it('émet à tous les réglés quand l’option présence est désactivée (défaut)', function () {
    Settings::set('attestations.require_presence', false);

    $edition = openEdition();
    $paid = $edition->participants()->create([
        'first_name' => 'A', 'last_name' => 'Payé', 'email' => 'a@example.tg', 'role' => 'author',
    ]);
    $edition->payments()->create(['participant_id' => $paid->id, 'amount' => 15000, 'status' => 'paid']);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.documents.attestations.issue'), [
        'edition_id' => (string) $edition->id,
        'type' => 'participation',
    ]);

    expect(Attestation::count())->toBe(1);
});
