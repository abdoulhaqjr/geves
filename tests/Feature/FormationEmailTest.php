<?php

use App\Mail\CampaignMail;
use App\Mail\FormationEnrollmentMail;
use App\Models\Email;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

// openEdition()/verifiedAccount()/adminStaff() -> tests/Pest.php + AdminSubmissionWorkflowTest

it('envoie une confirmation d’inscription et notifie à l’inscription', function () {
    Mail::fake();
    $edition = openEdition();
    $user = verifiedAccount();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Atelier data', 'fee' => 0, 'published' => true]);

    $this->actingAs($user, 'account')->post(route('account.formations.enroll', $formation));

    Mail::assertQueued(FormationEnrollmentMail::class, 1);
    expect($user->fresh()->notifications()->count())->toBeGreaterThan(0);
});

it('ne renvoie pas la confirmation si déjà inscrit', function () {
    Mail::fake();
    $edition = openEdition();
    $user = verifiedAccount();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Atelier data', 'fee' => 0, 'published' => true]);

    $this->actingAs($user, 'account')->post(route('account.formations.enroll', $formation));
    $this->actingAs($user, 'account')->post(route('account.formations.enroll', $formation));

    Mail::assertQueued(FormationEnrollmentMail::class, 1); // une seule fois
});

it('envoie un message admin aux inscrits et le journalise', function () {
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Rédaction', 'fee' => 0, 'published' => true]);

    $u1 = verifiedAccount();
    $u2 = User::create(['name' => 'Ben Co', 'first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'ben@example.tg', 'password' => 'pass12345']);
    foreach ([$u1, $u2] as $u) {
        $p = $edition->participants()->create(['first_name' => $u->first_name, 'last_name' => $u->last_name, 'email' => $u->email, 'role' => 'trainee', 'user_id' => $u->id]);
        FormationEnrollment::create(['formation_id' => $formation->id, 'edition_id' => $edition->id, 'user_id' => $u->id, 'participant_id' => $p->id, 'status' => 'registered', 'amount' => 0]);
    }

    Mail::fake();
    $this->actingAs(adminStaff(), 'web')->post(route('admin.formations.message', $formation), [
        'subject' => 'Convocation', 'body' => 'Rendez-vous demain à 9h.', 'audience' => 'all',
    ]);

    Mail::assertSent(CampaignMail::class, 2);
    expect(Email::where('template', 'formation')->where('batch_id', '!=', null)->count())->toBe(2);
});

it('cible uniquement les inscrits ayant réglé', function () {
    $edition = openEdition();
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Payante', 'fee' => 10000, 'published' => true]);

    $paid = verifiedAccount();
    $unpaid = User::create(['name' => 'Non Réglé', 'first_name' => 'Non', 'last_name' => 'Réglé', 'email' => 'unpaid@example.tg', 'password' => 'pass12345']);
    foreach ([['u' => $paid, 's' => 'paid'], ['u' => $unpaid, 's' => 'registered']] as $row) {
        $p = $edition->participants()->create(['first_name' => 'X', 'last_name' => 'Y', 'email' => $row['u']->email, 'role' => 'trainee', 'user_id' => $row['u']->id]);
        FormationEnrollment::create(['formation_id' => $formation->id, 'edition_id' => $edition->id, 'user_id' => $row['u']->id, 'participant_id' => $p->id, 'status' => $row['s'], 'amount' => 10000]);
    }

    Mail::fake();
    $this->actingAs(adminStaff(), 'web')->post(route('admin.formations.message', $formation), [
        'subject' => 'Rappel', 'body' => 'Merci.', 'audience' => 'paid',
    ]);

    Mail::assertSent(CampaignMail::class, 1); // seul l'inscrit réglé
});
