<?php

// adminStaff() et seededSubmission() sont définis dans tests/Pest.php.

it('exporte les communications acceptées en BibTeX', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $edition = $submission->edition;

    $res = $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.bibliography', ['format' => 'bibtex', 'edition_id' => $edition->id]));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/x-bibtex')
        ->and($res->streamedContent())->toContain('@inproceedings')
        ->and($res->streamedContent())->toContain('Innovation durable');
});

it('exporte les communications acceptées en RIS', function () {
    $staff = adminStaff();
    $submission = seededSubmission('accepted');
    $edition = $submission->edition;

    $res = $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.bibliography', ['format' => 'ris', 'edition_id' => $edition->id]));

    $res->assertOk();
    expect($res->streamedContent())->toContain('TY  - CPAPER')
        ->and($res->streamedContent())->toContain('TI  - Innovation durable');
});

it('n’inclut pas les communications non acceptées', function () {
    $staff = adminStaff();
    $submission = seededSubmission('under_review'); // pas acceptée
    $edition = $submission->edition;

    $res = $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.bibliography', ['format' => 'bibtex', 'edition_id' => $edition->id]));

    $res->assertOk();
    expect($res->streamedContent())->not->toContain('Innovation durable');
});

it('rejette un format inconnu', function () {
    $staff = adminStaff();
    seededSubmission('accepted');

    $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.bibliography', ['format' => 'xml']))
        ->assertNotFound();
});
