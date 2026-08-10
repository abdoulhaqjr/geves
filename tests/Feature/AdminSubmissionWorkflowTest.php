<?php

use App\Livewire\Admin\Submissions\Index;
use App\Livewire\Admin\Submissions\Show;
use App\Mail\SubmissionStatusMail;
use App\Models\Email;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

function adminStaff(): User
{
    Permission::firstOrCreate(['name' => 'manage_colloques', 'guard_name' => 'web']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    $user->givePermissionTo('manage_colloques');

    return $user;
}

function seededSubmission(string $status = 'submitted'): Submission
{
    $edition = openEdition(); // défini dans AccountSubmissionTest
    $account = verifiedAccount();
    $participant = $edition->participants()->create([
        'account_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);

    return $edition->submissions()->create([
        'participant_id' => $participant->id,
        'ref_code' => '26-001',
        'title' => 'Innovation durable',
        'abstract' => str_repeat('Contexte. ', 10),
        'track' => 'Sciences',
        'type' => 'oral',
        'status' => $status,
        'authors' => [['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'corresponding' => true]],
    ]);
}

it('affiche la liste des soumissions au staff habilité', function () {
    $staff = adminStaff();
    seededSubmission();

    Livewire::actingAs($staff, 'web')
        ->test(Index::class)
        ->assertOk()
        ->assertSee('Innovation durable')
        ->assertSee('26-001');
});

it('accepte une soumission, notifie l’auteur et journalise l’e-mail', function () {
    Mail::fake();
    $staff = adminStaff();
    $submission = seededSubmission('under_review');

    Livewire::actingAs($staff, 'web')
        ->test(Show::class, ['submission' => $submission])
        ->set('note', 'Félicitations, présentation orale prévue.')
        ->call('applyStatus', 'accepted')
        ->assertHasNoErrors();

    expect($submission->fresh()->status)->toBe('accepted');

    Mail::assertSent(SubmissionStatusMail::class, function ($mail) {
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

    Livewire::actingAs($staff, 'web')
        ->test(Show::class, ['submission' => $submission])
        ->call('applyStatus', 'submitted')
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

    Livewire::actingAs($account, 'account')
        ->test(\App\Livewire\Account\Submissions\Create::class, ['slug' => $edition->slug])
        ->set('title', 'Ma communication')
        ->set('track', 'Sciences')
        ->set('abstract', str_repeat('a', 60))
        ->call('submit')
        ->assertHasNoErrors();

    Mail::assertSent(SubmissionStatusMail::class, fn ($mail) => $mail->statusKey === 'submitted');
});
