<?php

use App\Models\Slot;
use App\Support\CalendarExport;
use Illuminate\Support\Carbon;

// openEdition() et verifiedAccount() sont définis dans tests/Pest.php.

it('génère un ICS avec événement, rappel et échappement', function () {
    $ics = (new CalendarExport)->ics([[
        'uid' => 'e1@ukc',
        'summary' => 'Présentation — Titre, avec; ponctuation',
        'start' => Carbon::parse('2026-09-03 09:00'),
        'end' => Carbon::parse('2026-09-03 09:20'),
        'location' => 'Amphi A',
        'description' => 'Axe Sciences',
    ]]);

    expect($ics)->toContain('BEGIN:VCALENDAR')
        ->and($ics)->toContain('BEGIN:VEVENT')
        ->and($ics)->toContain('UID:e1@ukc')
        ->and($ics)->toContain('SUMMARY:Présentation — Titre\\, avec\\; ponctuation') // virgule/; échappés
        ->and($ics)->toContain('BEGIN:VALARM')
        ->and($ics)->toContain('TRIGGER:-PT60M')
        ->and($ics)->toContain('END:VCALENDAR');
});

it('construit un lien Google Agenda', function () {
    $url = (new CalendarExport)->googleUrl([
        'summary' => 'Présentation',
        'start' => Carbon::parse('2026-09-03 09:00'),
        'end' => Carbon::parse('2026-09-03 09:20'),
        'location' => 'Amphi A',
        'description' => 'Axe Sciences',
    ]);

    expect($url)->toContain('calendar.google.com/calendar/render')
        ->and($url)->toContain('action=TEMPLATE')
        ->and($url)->toContain('dates=');
});

it('exporte l’agenda du participant en ICS (ses créneaux)', function () {
    $edition = openEdition();
    $account = verifiedAccount();
    $participant = $edition->participants()->create([
        'account_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);
    $submission = $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-1', 'title' => 'Ma communication',
        'abstract' => 'a', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted', 'authors' => [],
    ]);
    Slot::create([
        'edition_id' => $edition->id, 'submission_id' => $submission->id, 'participant_id' => $participant->id,
        'session_label' => 'Session 1', 'room' => 'Amphi A',
        'starts_at' => '2026-09-03 09:00', 'ends_at' => '2026-09-03 09:20',
    ]);

    $res = $this->actingAs($account, 'account')->get(route('account.agenda.ics'));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('text/calendar')
        ->and($res->streamedContent())->toContain('BEGIN:VEVENT')
        ->and($res->streamedContent())->toContain('Ma communication')
        ->and($res->streamedContent())->toContain('Amphi A');
});
