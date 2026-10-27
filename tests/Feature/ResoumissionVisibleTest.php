<?php

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

// openEdition()/verifiedAccount() -> tests/Pest.php ; adminStaff()/seededSubmission() -> helpers

/* ---- A. Le comité est prévenu quand une correction est resoumise ---- */

it('prévient les gestionnaires de l’événement quand une correction est resoumise', function () {
    Notification::fake();
    Mail::fake();

    $submission = seededSubmission('revision');
    $event = $submission->event;
    $account = $submission->participant->user;

    $gerant = User::factory()->create();
    $coGerant = User::factory()->create();
    $gerant->assignedEditions()->attach($event->id, ['role' => 'gerant']);
    $coGerant->assignedEditions()->attach($event->id, ['role' => 'co_gerant']);
    // Administrateur NON affecté : il ne doit rien recevoir tant qu'il y a un
    // gestionnaire - on ne diffuse pas à tout le back-office.
    $adminNonAffecte = adminStaff();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.resubmit', $submission), [
            'title' => 'Titre corrigé',
            'abstract' => str_repeat('Nouveau contenu. ', 6),
        ])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($gerant, SystemNotification::class, fn ($n) => $n->kind === 'submission_resubmitted');
    Notification::assertSentTo($coGerant, SystemNotification::class, fn ($n) => $n->kind === 'submission_resubmitted');
    Notification::assertNotSentTo($adminNonAffecte, SystemNotification::class);
});

it('replie sur les administrateurs quand l’événement n’a pas de gestionnaire', function () {
    Notification::fake();
    Mail::fake();

    $submission = seededSubmission('revision');
    $account = $submission->participant->user;
    $admin = adminStaff();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.resubmit', $submission), [
            'title' => 'Titre corrigé',
            'abstract' => str_repeat('Nouveau contenu. ', 6),
        ])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($admin, SystemNotification::class, fn ($n) => $n->kind === 'submission_resubmitted');
});

it('rend l’e-mail de resoumission décochable sans couper l’in-app', function () {
    $gerant = User::factory()->create();

    expect($gerant->notificationChannels('submission_resubmitted'))
        ->toContain('database')->toContain('mail');

    $gerant->saveNotificationPreferences(['submission_resubmitted' => ['inapp' => true, 'email' => false]], 'staff');

    expect($gerant->fresh()->notificationChannels('submission_resubmitted'))
        ->toContain('database')->not->toContain('mail');
});

/* ---- B. La liste distingue une resoumission d'un dépôt neuf ---- */

function submissionSurEvenement($event, string $ref, int $version)
{
    $p = $event->participants()->create([
        'first_name' => 'Awa'.$version, 'last_name' => 'Kodjo', 'email' => 'awa'.$version.'@example.tg',
    ]);

    return $event->submissions()->create([
        'participant_id' => $p->id, 'ref_code' => $ref, 'title' => 'Travaux '.$ref,
        'abstract' => str_repeat('Contexte. ', 10), 'track' => 'Sciences', 'type' => 'oral',
        'status' => 'submitted', 'version' => $version,
        'authors' => [['name' => 'Awa Kodjo', 'email' => 'awa'.$version.'@example.tg', 'corresponding' => true]],
    ]);
}

it('marque « Corrigée v2 » une communication resoumise dans la liste', function () {
    $event = openEdition();
    submissionSurEvenement($event, '26-200', 2);

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.submissions.index', ['event_id' => $event->id]))
        ->assertOk()
        ->assertSee('Corrigée v2');
});

it('ne marque PAS un dépôt neuf jamais corrigé', function () {
    $event = openEdition();
    submissionSurEvenement($event, '26-100', 1);

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.submissions.index', ['event_id' => $event->id]))
        ->assertOk()
        ->assertSee('26-100')
        ->assertDontSee('Corrigée');
});

it('le filtre « Resoumises après correction » ne garde que les versions corrigées', function () {
    $event = openEdition();
    submissionSurEvenement($event, '26-100', 1);
    submissionSurEvenement($event, '26-200', 2);

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.submissions.index', ['event_id' => $event->id, 'resubmitted' => 1]))
        ->assertOk()
        ->assertSee('26-200')
        ->assertDontSee('26-100');
});
