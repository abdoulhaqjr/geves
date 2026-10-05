<?php

use App\Actions\AssignReviewer;
use App\Actions\ConfirmPayment;
use App\Actions\IssueAttestation;
use App\Actions\NotifySubmissionStatus;
use App\Events\SubmissionStatusChanged;
use App\Mail\SubmissionStatusMail;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Support\Facades\Mail;

// helpers globaux : openEdition()/verifiedAccount() -> AccountSubmissionTest ;
// adminStaff()/seededSubmission() -> AdminSubmissionWorkflowTest

/** Participant lié à un compte sur un événement. */
function notifiedParticipant($event, $account, array $attrs = [])
{
    return $event->participants()->create(array_merge([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => $account->email, 'role' => 'author',
    ], $attrs));
}

// --- Déclencheurs participant ---------------------------------------------

it('notifie le compte auteur d’une décision de soumission', function () {
    $submission = seededSubmission('submitted');
    $account = $submission->participant->user;

    SubmissionStatusChanged::dispatch($submission, 'accepted');

    expect($account->fresh()->notifications->pluck('data.kind'))->toContain('submission_status');
});

it('notifie le participant et le staff à la confirmation d’un paiement', function () {
    $staff = adminStaff();
    $event = openEdition();
    $account = verifiedAccount();
    $participant = notifiedParticipant($event, $account);
    $payment = $event->payments()->create([
        'participant_id' => $participant->id, 'amount' => 15000, 'currency' => 'FCFA', 'status' => 'pending',
    ]);

    (new ConfirmPayment)->handle($payment);

    expect($account->fresh()->notifications->pluck('data.kind'))->toContain('payment');
    expect($staff->fresh()->notifications->pluck('data.kind'))->toContain('payment');
});

it('notifie le participant à l’émission d’une attestation', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $participant = notifiedParticipant($event, $account);

    (new IssueAttestation)->handle($participant, 'participation');

    expect($account->fresh()->notifications->pluck('data.kind'))->toContain('attestation');
});

// --- Déclencheurs staff ----------------------------------------------------

it('notifie le staff d’une nouvelle communication', function () {
    $staff = adminStaff();
    $event = openEdition();
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Innovation durable',
            'track' => 'Santé',
            'type' => 'oral',
            'abstract' => str_repeat('Contexte et objectifs. ', 5),
        ])
        ->assertSessionHasNoErrors();

    expect($staff->fresh()->notifications->pluck('data.kind'))->toContain('submission_new');
});

it('notifie le staff d’un message de contact', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->post(route('public.contact', $event->id), [
        'loaded_at' => time() - 10,
        'name' => 'Jean Dupont',
        'email' => 'jean@example.tg',
        'message' => 'Bonjour, je souhaite des informations sur le colloque.',
    ])->assertRedirect();

    expect($staff->fresh()->notifications->pluck('data.kind'))->toContain('contact');
});

// --- UI : cloche & page ----------------------------------------------------

it('la cloche affiche les non-lus, ouvre et marque comme lu', function () {
    $account = verifiedAccount();
    Notifier::account($account, 'test', 'Titre de test', 'Corps', route('account.dashboard'), 'bell');
    $id = $account->fresh()->notifications->first()->id;

    // La cloche est rendue dans la topbar du layout participant.
    $this->actingAs($account, 'account')
        ->get(route('account.dashboard'))
        ->assertSee('Titre de test');

    $this->actingAs($account, 'account')
        ->post(route('notifications.open', $id))
        ->assertRedirect(route('account.dashboard'));

    expect($account->fresh()->unreadNotifications)->toHaveCount(0);
});

it('la page Notifications marque tout comme lu', function () {
    $account = verifiedAccount();
    Notifier::account($account, 'test', 'Notif 1');
    Notifier::account($account, 'test', 'Notif 2');

    expect($account->fresh()->unreadNotifications)->toHaveCount(2);

    $this->actingAs($account, 'account')
        ->get(route('account.notifications'))
        ->assertOk()
        ->assertSee('Notif 1');

    $this->actingAs($account, 'account')
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect($account->fresh()->unreadNotifications)->toHaveCount(0);
});

it('la page Notifications est protégée par le guard account', function () {
    $this->get(route('account.notifications'))->assertRedirect(route('account.login'));
});

// --- Relecteur assigné -----------------------------------------------------

it('notifie le compte du relecteur à l’assignation', function () {
    $submission = seededSubmission('submitted');
    $event = $submission->event;

    $reviewerAccount = User::create([
        'first_name' => 'Rel', 'last_name' => 'Ecteur', 'email' => 'rev@example.tg', 'password' => 'pass12345',
    ]);
    $event->participants()->create([
        'user_id' => $reviewerAccount->id, 'first_name' => 'Rel', 'last_name' => 'Ecteur',
        'email' => 'rev@example.tg', 'role' => 'author',
    ]);

    (new AssignReviewer)->handle($submission, 'Rel Ecteur', 'rev@example.tg');

    expect($reviewerAccount->fresh()->notifications->pluck('data.kind'))->toContain('review_assigned');
});

// --- Préférences & canal e-mail --------------------------------------------

it('résout les canaux selon les défauts du registre', function () {
    $account = verifiedAccount();

    // e-mail OFF par défaut (Mailable dédié) → in-app seul.
    expect($account->notificationChannels('submission_status'))->toBe(['database']);
    // e-mail ON par défaut (aucun mail existant) → in-app + mail.
    expect($account->notificationChannels('attestation'))->toBe(['database', 'mail']);
});

it('ne crée pas de notification en base quand l’in-app est désactivé', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $account->saveNotificationPreferences(['attestation' => ['inapp' => false, 'email' => false]], 'account');
    $participant = notifiedParticipant($event, $account);

    (new IssueAttestation)->handle($participant, 'participation');

    expect($account->fresh()->notifications)->toHaveCount(0);
});

it('n’ajoute le canal mail générique que pour les types sans Mailable dédié', function () {
    $account = verifiedAccount();

    // Type sans Mailable dédié → e-mail générique via le canal mail.
    expect($account->notificationChannels('attestation'))->toContain('mail');

    // Type avec Mailable dédié → pas de mail générique (évite le doublon),
    // mais la préférence e-mail reste vraie (le Mailable dédié est gaté par elle).
    expect($account->notificationChannels('submission_status'))->not->toContain('mail');
    expect($account->wantsNotification('submission_status', 'email'))->toBeTrue();
});

it('la préférence e-mail gate le Mailable dédié (décision de soumission)', function () {
    Mail::fake();
    $submission = seededSubmission('submitted');
    $account = $submission->participant->user;

    // Par défaut (préférence ON) : le Mailable dédié part (en file).
    (new NotifySubmissionStatus)->handle($submission, 'accepted');
    Mail::assertQueued(SubmissionStatusMail::class);

    // Préférence e-mail désactivée : le Mailable dédié est supprimé.
    $account->saveNotificationPreferences(['submission_status' => ['inapp' => true, 'email' => false]], 'account');
    Mail::fake();
    (new NotifySubmissionStatus)->handle($submission, 'rejected');
    Mail::assertNotQueued(SubmissionStatusMail::class);
});

it('enregistre les préférences depuis la page Notifications', function () {
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->post(route('notifications.prefs'), [
            'prefs' => ['submission_status' => ['inapp' => '1']], // e-mail décoché → false
        ])
        ->assertRedirect();

    expect($account->fresh()->wantsNotification('submission_status', 'email'))->toBeFalse();
});
