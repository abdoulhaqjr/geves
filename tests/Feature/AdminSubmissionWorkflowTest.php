<?php

use App\Mail\SubmissionStatusMail;
use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

// adminStaff() et seededSubmission() sont définis dans tests/Pest.php (fabriques partagées).

it('affiche la liste des soumissions au staff habilité', function () {
    $staff = adminStaff();
    seededSubmission();

    $this->actingAs($staff, 'web')
        ->get(route('admin.submissions.index'))
        ->assertOk()
        ->assertSee('Innovation durable')
        ->assertSee('26-001');
});

it('accepte une soumission, notifie l’auteur et journalise l’e-mail', function () {
    Mail::fake();
    $staff = adminStaff();
    $submission = seededSubmission('under_review');

    $this->actingAs($staff, 'web')->post(route('admin.submissions.status', $submission), [
        'status' => 'accepted',
        'note' => 'Félicitations, présentation orale prévue.',
    ])->assertSessionHasNoErrors();

    expect($submission->fresh()->status)->toBe('accepted');

    Mail::assertQueued(SubmissionStatusMail::class, function ($mail) {
        return $mail->statusKey === 'accepted' && $mail->hasTo('awa@example.tg');
    });

    $log = Email::where('template', 'submission_accepted')->first();
    expect($log)->not->toBeNull();
    expect($log->recipient)->toBe('awa@example.tg');
    expect($log->status)->toBe('sent');
});

it('refuse un statut non autorisé', function () {
    $staff = adminStaff();
    $submission = seededSubmission();

    $this->actingAs($staff, 'web')
        ->post(route('admin.submissions.status', $submission), ['status' => 'submitted'])
        ->assertStatus(400);
});

it('protège les soumissions admin sans la permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get(route('admin.submissions.index'))->assertForbidden();
});

it('rend le Mailable de statut (le template markdown compile)', function () {
    $submission = seededSubmission();

    $html = (new SubmissionStatusMail($submission, 'accepted', 'Bravo !'))->render();

    expect($html)->toContain('Communication acceptée');
    expect($html)->toContain('26-001');
    expect($html)->toContain('Bravo');
});

it('envoie un accusé de réception à la création d’une soumission', function () {
    Mail::fake();
    $edition = openEdition();
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $edition->slug), [
            'title' => 'Ma communication',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
        ])
        ->assertSessionHasNoErrors();

    Mail::assertQueued(SubmissionStatusMail::class, fn ($mail) => $mail->statusKey === 'submitted');
});
