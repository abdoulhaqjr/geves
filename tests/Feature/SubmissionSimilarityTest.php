<?php

// adminStaff() et seededSubmission() sont définis dans tests/Pest.php.

it('signale un résumé quasi-dupliqué sur la fiche soumission', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $event = $submission->event;

    // Un autre résumé très proche dans la même événement.
    $dupe = $event->submissions()->create([
        'participant_id' => $submission->participant_id,
        'ref_code' => '26-777',
        'title' => 'Innovation durable (variante)',
        'abstract' => (string) $submission->abstract.' Contexte.',
        'track' => 'Sciences',
        'type' => 'oral',
        'status' => 'submitted',
        'authors' => [],
    ]);

    $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.show', $submission))
        ->assertOk()
        ->assertSee('Similarité interne')
        ->assertSee('26-777');
});

it('n’affiche pas de similarité quand aucun résumé n’est proche', function () {
    $staff = adminStaff();
    $submission = seededSubmission('submitted');
    $submission->event->submissions()->create([
        'participant_id' => $submission->participant_id,
        'ref_code' => '26-888',
        'title' => 'Sujet sans rapport',
        'abstract' => 'Analyse spectrale des galaxies naines par interférométrie radio à très longue base.',
        'track' => 'Sciences',
        'type' => 'oral',
        'status' => 'submitted',
        'authors' => [],
    ]);

    $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.show', $submission))
        ->assertOk()
        ->assertDontSee('Similarité interne');
});
