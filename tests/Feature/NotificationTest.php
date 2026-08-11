<?php

use App\Actions\AssignReviewer;
use App\Actions\ConfirmPayment;
use App\Actions\IssueAttestation;
use App\Events\SubmissionStatusChanged;
use App\Livewire\Account\Submissions\Create;
use App\Livewire\Notifications\Bell;
use App\Livewire\Notifications\Center;
use App\Livewire\Public\ContactForm;
use App\Models\Account;
use App\Support\Notifier;
use Livewire\Livewire;

// helpers globaux : openEdition()/verifiedAccount() -> AccountSubmissionTest ;
// adminStaff()/seededSubmission() -> AdminSubmissionWorkflowTest

/** Participant lié à un compte sur une édition. */
function notifiedParticipant($edition, $account, array $attrs = [])
{
    return $edition->participants()->create(array_merge([
        'account_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => $account->email, 'role' => 'author',
    ], $attrs));
}

// --- Déclencheurs participant ---------------------------------------------

it('notifie le compte auteur d’une décision de soumission', function () {
    $submission = seededSubmission('submitted');
    $account = $submission->participant->account;

    SubmissionStatusChanged::dispatch($submission, 'accepted');

    expect($account->fresh()->notifications->pluck('data.kind'))->toContain('submission_status');
});

it('notifie le participant et le staff à la confirmation d’un paiement', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $account = verifiedAccount();
    $participant = notifiedParticipant($edition, $account);
    $payment = $edition->payments()->create([
        'participant_id' => $participant->id, 'amount' => 15000, 'currency' => 'FCFA', 'status' => 'pending',
    ]);

    (new ConfirmPayment)->handle($payment);

    expect($account->fresh()->notifications->pluck('data.kind'))->toContain('payment');
    expect($staff->fresh()->notifications->pluck('data.kind'))->toContain('payment');
});

it('notifie le participant à l’émission d’une attestation', function () {
    $edition = openEdition();
    $account = verifiedAccount();
    $participant = notifiedParticipant($edition, $account);

    (new IssueAttestation)->handle($participant, 'participation');

    expect($account->fresh()->notifications->pluck('data.kind'))->toContain('attestation');
});

// --- Déclencheurs staff ----------------------------------------------------

it('notifie le staff d’une nouvelle communication', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $account = verifiedAccount();

    Livewire::actingAs($account, 'account')
        ->test(Create::class, ['slug' => $edition->slug])
        ->set('title', 'Innovation durable')
        ->set('track', 'Santé')
        ->set('type', 'oral')
        ->set('abstract', str_repeat('Contexte et objectifs. ', 5))
        ->call('submit')
        ->assertHasNoErrors();

    expect($staff->fresh()->notifications->pluck('data.kind'))->toContain('submission_new');
});

it('notifie le staff d’un message de contact', function () {
    $staff = adminStaff();
    $edition = openEdition();

    Livewire::test(ContactForm::class, ['editionId' => $edition->id])
        ->set('loadedAt', time() - 10)
        ->set('name', 'Jean Dupont')
        ->set('email', 'jean@example.tg')
        ->set('message', 'Bonjour, je souhaite des informations sur le colloque.')
        ->call('submit')
        ->assertSet('sent', true);

    expect($staff->fresh()->notifications->pluck('data.kind'))->toContain('contact');
});

// --- UI : cloche & page ----------------------------------------------------

it('la cloche affiche les non-lus, ouvre et marque comme lu', function () {
    $account = verifiedAccount();
    Notifier::account($account, 'test', 'Titre de test', 'Corps', route('account.dashboard'), 'bell');
    $id = $account->fresh()->notifications->first()->id;

    Livewire::actingAs($account, 'account')
        ->test(Bell::class)
        ->assertSee('Titre de test')
        ->call('open', $id)
        ->assertRedirect(route('account.dashboard'));

    expect($account->fresh()->unreadNotifications)->toHaveCount(0);
});

it('la page Notifications marque tout comme lu', function () {
    $account = verifiedAccount();
    Notifier::account($account, 'test', 'Notif 1');
    Notifier::account($account, 'test', 'Notif 2');

    expect($account->fresh()->unreadNotifications)->toHaveCount(2);

    Livewire::actingAs($account, 'account')
        ->test(Center::class)
        ->assertSee('Notif 1')
        ->call('markAllRead');

    expect($account->fresh()->unreadNotifications)->toHaveCount(0);
});

it('la page Notifications est protégée par le guard account', function () {
    $this->get(route('account.notifications'))->assertRedirect(route('account.login'));
});

// --- Relecteur assigné -----------------------------------------------------

it('notifie le compte du relecteur à l’assignation', function () {
    $submission = seededSubmission('submitted');
    $edition = $submission->edition;

    $reviewerAccount = Account::create([
        'first_name' => 'Rel', 'last_name' => 'Ecteur', 'email' => 'rev@example.tg', 'password' => 'pass12345',
    ]);
    $edition->participants()->create([
        'account_id' => $reviewerAccount->id, 'first_name' => 'Rel', 'last_name' => 'Ecteur',
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
    $edition = openEdition();
    $account = verifiedAccount();
    $account->saveNotificationPreferences(['attestation' => ['inapp' => false, 'email' => false]], 'account');
    $participant = notifiedParticipant($edition, $account);

    (new IssueAttestation)->handle($participant, 'participation');

    expect($account->fresh()->notifications)->toHaveCount(0);
});

it('ajoute le canal mail quand l’e-mail est activé pour un type', function () {
    $account = verifiedAccount();
    $account->saveNotificationPreferences(['submission_status' => ['inapp' => true, 'email' => true]], 'account');

    expect($account->notificationChannels('submission_status'))->toContain('mail');
});

it('enregistre les préférences depuis la page Notifications', function () {
    $account = verifiedAccount();

    Livewire::actingAs($account, 'account')
        ->test(Center::class)
        ->set('prefs.submission_status.email', true)
        ->call('savePrefs');

    expect($account->fresh()->wantsNotification('submission_status', 'email'))->toBeTrue();
});
