<?php

use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

function gestionnaireSansCorrectionDuResume(Submission $submission): User
{
    $user = User::factory()->create(['phone' => null]);
    $submission->event->team()->attach($user->id, ['role' => 'gerant']);

    return $user;
}

it('laisse un administrateur corriger silencieusement un résumé accepté et archivé', function () {
    Notification::fake();
    Log::spy();

    $submission = seededSubmission('accepted');
    $submission->event->update(['status' => 'archived']);
    $submission->update(['version' => 3]);
    $admin = User::factory()->create([
        'phone' => null,
        'access_level' => User::ACCESS_COLLOQUE_ADMIN,
    ]);
    $originalStatus = $submission->status;
    $originalVersion = $submission->version;

    $this->actingAs($admin, 'web')
        ->post(route('admin.submissions.abstract.update', $submission), [
            'admin_abstract' => '<p onclick="alert(1)">'.str_repeat('Texte corrigé administrativement. ', 12).'</p><script>alert(1)</script>',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $submission->refresh();
    expect($submission->abstract)
        ->toContain('Texte corrigé administrativement.')
        ->not->toContain('onclick', '<script>')
        ->and($submission->status)->toBe($originalStatus)
        ->and($submission->version)->toBe($originalVersion)
        ->and($submission->versions()->count())->toBe(0);
    Notification::assertNothingSent();
    Log::shouldHaveReceived('info')->once()->with(
        'Administrative submission abstract corrected',
        Mockery::on(fn (array $context) => $context['submission_id'] === $submission->id
            && $context['actor_id'] === $admin->id
            && isset($context['before_sha256'], $context['after_sha256']))
    );
});

it('ouvre le bouton aux administrateurs et le masque au gestionnaire', function () {
    $submission = seededSubmission('accepted');
    $admin = User::factory()->create([
        'phone' => null,
        'access_level' => User::ACCESS_COLLOQUE_ADMIN,
    ]);
    $gestionnaire = gestionnaireSansCorrectionDuResume($submission);

    $this->actingAs($admin, 'web')
        ->get(route('admin.submissions.show', $submission))
        ->assertOk()
        ->assertSee('Corriger le résumé');

    $this->actingAs($gestionnaire, 'web')
        ->get(route('admin.submissions.show', $submission))
        ->assertOk()
        ->assertDontSee('Corriger le résumé');
});

it('refuse la correction administrative au gestionnaire même par requête directe', function () {
    $submission = seededSubmission('accepted');
    $gestionnaire = gestionnaireSansCorrectionDuResume($submission);
    $original = $submission->abstract;

    $this->actingAs($gestionnaire, 'web')
        ->post(route('admin.submissions.abstract.update', $submission), [
            'admin_abstract' => str_repeat('Tentative interdite. ', 12),
        ])
        ->assertForbidden();

    expect($submission->fresh()->abstract)->toBe($original);
});

it('applique aussi les limites configurées au texte administratif', function () {
    $submission = seededSubmission('accepted');
    $submission->event->update([
        'settings' => array_merge($submission->event->settings ?? [], [
            'submission_form' => ['rules' => ['word_min' => 20, 'word_limit' => 40]],
        ]),
    ]);
    $admin = User::factory()->create([
        'phone' => null,
        'access_level' => User::ACCESS_SUPER_ADMIN,
    ]);

    $this->actingAs($admin, 'web')
        ->post(route('admin.submissions.abstract.update', $submission), [
            'admin_abstract' => 'Trop court',
        ])
        ->assertSessionHasErrors('admin_abstract');
});
