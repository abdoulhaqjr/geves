<?php

use App\Support\EventReport;

it('limite le rapport participants aux auteurs, co-auteurs et inscrits simples réels', function () {
    $event = openEdition();

    $depositor = $event->participants()->create([
        'first_name' => 'Admin', 'last_name' => 'Dépôt', 'email' => 'depot@example.tg',
        'role' => 'author', 'country' => 'Burkina Faso', 'participation_mode' => 'onsite',
    ]);
    $event->participants()->create([
        'first_name' => 'Rita', 'last_name' => 'Relecture', 'email' => 'review@example.tg',
        'role' => 'reviewer', 'country' => 'Ghana', 'participation_mode' => 'onsite',
    ]);
    $event->participants()->create([
        'first_name' => 'Tom', 'last_name' => 'Formation', 'email' => 'training@example.tg',
        'role' => 'trainee', 'country' => 'Ghana',
    ]);
    $author = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg',
        'role' => 'author', 'country' => 'Togo', 'participation_mode' => 'online',
    ]);
    $duplicate = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg',
        'role' => 'reviewer', 'country' => 'Togo',
    ]);
    $coauthor = $event->participants()->create([
        'first_name' => 'Ben', 'last_name' => 'Adjovi', 'email' => 'ben@example.tg',
        'role' => 'coauthor', 'country' => 'Bénin', 'participation_mode' => 'onsite',
    ]);
    $event->participants()->create([
        'first_name' => 'Céline', 'last_name' => 'Auditrice', 'email' => 'celine@example.tg',
        'role' => 'attendee', 'country' => 'Togo',
    ]);

    $event->submissions()->create([
        'participant_id' => $depositor->id,
        'ref_code' => '26-001',
        'title' => 'Communication déposée pour des auteurs',
        'status' => 'submitted',
        'revision_of' => 0,
        'authors' => [
            ['name' => 'Awa Kodjo', 'email' => $author->email],
            ['name' => 'Ben Adjovi', 'email' => $coauthor->email],
        ],
    ]);
    $event->payments()->create([
        'participant_id' => $duplicate->id,
        'amount' => 15000,
        'status' => 'paid',
    ]);

    $report = new EventReport($event);
    $rows = $report->participantRows();
    expect($report->summary()['participants'])->toBe(3);
    expect($report->summary()['paidCount'])->toBe(1);
    expect(collect($rows)->pluck(2)->all())->toEqualCanonicalizing([
        'awa@example.tg', 'ben@example.tg', 'celine@example.tg',
    ]);
    $authorRow = collect($rows)->first(fn (array $row) => $row[2] === 'awa@example.tg');
    expect($authorRow[8])->toBe('Oui');
    expect($authorRow[9])->toBe(15000.0);
    expect($report->participantsByMode()['online']['count'])->toBe(1);
    expect($report->participantsByMode()['onsite']['count'])->toBe(1);
    expect($report->participantsByMode()['unspecified']['count'])->toBe(1);
    expect(collect($report->participantsByRole())->pluck('role')->all())
        ->toEqualCanonicalizing(['Communicant', 'Co-auteur', 'Participant simple']);
    expect(collect($report->participantsByCountry())->pluck('country')->all())
        ->toEqualCanonicalizing(['Togo', 'Bénin']);

    $csv = $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.reports.export', [$event, 'participants', 'csv']))
        ->assertOk()->streamedContent();
    expect($csv)->toContain('awa@example.tg', 'ben@example.tg', 'celine@example.tg')
        ->not->toContain('depot@example.tg', 'review@example.tg', 'training@example.tg');
});
