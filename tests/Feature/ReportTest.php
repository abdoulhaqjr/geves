<?php

use App\Models\Event;
use App\Support\EventReport;
use App\Support\XlsxWriter;
use Illuminate\Support\Str;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

function reportEdition(): Event
{
    $event = openEdition(); // titre défini par le helper

    $p1 = $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author', 'title' => 'Docteur']);
    $p2 = $event->participants()->create(['first_name' => 'Ben', 'last_name' => 'Adjo', 'email' => 'ben@example.tg', 'role' => 'attendee']);

    $event->payments()->create(['participant_id' => $p1->id, 'amount' => 15000, 'currency' => 'XOF', 'status' => 'paid', 'gateway' => 'manual', 'method' => 'cash', 'receipt_no' => 'REC-2026-001', 'paid_at' => now()]);
    $event->payments()->create(['participant_id' => $p2->id, 'amount' => 10000, 'currency' => 'XOF', 'status' => 'pending', 'gateway' => 'manual', 'method' => 'transfer']);

    $s = $event->submissions()->create(['participant_id' => $p1->id, 'title' => 'Sur les colloques', 'status' => 'accepted', 'revision_of' => 0, 'ref_code' => 'AA-001', 'access_token' => Str::random(64), 'track' => 'Axe 1', 'type' => 'oral', 'authors' => [['name' => 'Awa Kodjo', 'email' => 'awa@example.tg']]]);
    $s->reviews()->create(['recommendation' => 'accept', 'score' => 16, 'status' => 'submitted']);
    $s->reviews()->create(['recommendation' => 'accept', 'score' => 18, 'status' => 'submitted']);

    $event->submissions()->create(['participant_id' => $p2->id, 'title' => 'Refusée', 'status' => 'rejected', 'revision_of' => 0, 'ref_code' => 'AA-002', 'access_token' => Str::random(64), 'track' => 'Axe 2', 'type' => 'poster']);

    return $event;
}

it('calcule la synthèse d’un événement', function () {
    $event = reportEdition();
    $summary = (new EventReport($event))->summary();

    expect($summary['participants'])->toBe(2);
    expect($summary['submissions'])->toBe(2);
    expect($summary['accepted'])->toBe(1);
    expect($summary['submissionsByStatus']['rejected'])->toBe(1);
    expect($summary['paidCount'])->toBe(1);
    expect($summary['collected'])->toBe(15000.0);
    expect($summary['pending'])->toBe(10000.0);
});

it('produit les lignes avec note moyenne des relectures', function () {
    $event = reportEdition();
    $report = new EventReport($event);

    $rows = collect($report->submissionRows());
    $accepted = $rows->firstWhere(0, 'AA-001');

    expect($accepted[6])->toBe('Acceptée');
    expect($accepted[7])->toBe(17.0); // (16 + 18) / 2

    expect($report->participantRows())->toHaveCount(2);
    expect($report->paymentRows())->toHaveCount(2);
});

it('génère un .xlsx valide et relisible', function () {
    $xlsx = (new XlsxWriter)
        ->sheet('Synthèse', ['Indicateur', 'Valeur'], [['Participants', 42]])
        ->sheet('Détail', ['A', 'B'], [['x', 1.5]])
        ->contents();

    // Signature ZIP (PK\x03\x04)
    expect(substr($xlsx, 0, 2))->toBe('PK');

    $tmp = tempnam(sys_get_temp_dir(), 'test_xlsx_');
    file_put_contents($tmp, $xlsx);

    $zip = new ZipArchive;
    expect($zip->open($tmp))->toBeTrue();
    expect($zip->locateName('xl/workbook.xml'))->not->toBeFalse();
    expect($zip->locateName('xl/worksheets/sheet1.xml'))->not->toBeFalse();
    expect($zip->locateName('xl/worksheets/sheet2.xml'))->not->toBeFalse();

    $sheet1 = $zip->getFromName('xl/worksheets/sheet1.xml');
    expect($sheet1)->toContain('Participants');
    expect($sheet1)->toContain('<v>42</v>');
    $zip->close();
    @unlink($tmp);
});

it('échappe le XML et supporte les colonnes au-delà de Z', function () {
    $headers = array_map(fn ($i) => 'Col'.$i, range(1, 28));
    $row = array_fill(0, 28, 'v');
    $row[27] = 'a & b < c';

    $xlsx = (new XlsxWriter)->sheet('S', $headers, [$row])->contents();
    $tmp = tempnam(sys_get_temp_dir(), 'test_xlsx_');
    file_put_contents($tmp, $xlsx);
    $zip = new ZipArchive;
    $zip->open($tmp);
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    @unlink($tmp);

    expect($sheet)->toContain('r="AB1"');           // 28e colonne = AB
    expect($sheet)->toContain('a &amp; b &lt; c');  // échappement
});

it('exporte le classeur Excel depuis le back-office', function () {
    $staff = adminStaff();
    $event = reportEdition();

    $response = $this->actingAs($staff, 'web')->get(route('admin.reports.xlsx', $event));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect(substr($response->getContent(), 0, 2))->toBe('PK');
});

it('affiche le rapport PDF imprimable', function () {
    $staff = adminStaff();
    $event = reportEdition();

    $this->actingAs($staff, 'web')->get(route('admin.reports.pdf', $event))
        ->assertOk()
        ->assertSee('Rapport d’événement', false)
        ->assertSee('AA-001')
        ->assertSee('Awa');
});

it('refuse l’accès aux invités', function () {
    $event = reportEdition();
    $this->get(route('admin.reports.xlsx', $event))->assertRedirect(route('account.login'));
});
