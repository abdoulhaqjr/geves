<?php

use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Payment;

// openEdition()/verifiedAccount() -> tests/Pest.php ; publishedFormation() -> FormationEnrollmentTest

/** Formation payante, en direct, dans une édition où le compte participe. */
function livePaidFormation($edition): Formation
{
    $f = publishedFormation($edition, 10000);
    $f->update(['meeting_status' => 'live', 'meeting_provider' => 'jitsi']);

    return $f;
}

function participantOf($edition, $user): void
{
    $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'trainee', 'user_id' => $user->id,
    ]);
}

it('un inscrit non réglé ne voit pas le bouton visio et n’accède pas à la salle', function () {
    $edition = openEdition();
    $user = verifiedAccount();
    participantOf($edition, $user);
    $f = livePaidFormation($edition);
    FormationEnrollment::create([
        'formation_id' => $f->id, 'edition_id' => $edition->id, 'user_id' => $user->id, 'status' => 'registered', 'amount' => 10000,
    ]);

    $this->actingAs($user, 'account')->get(route('account.formations'))
        ->assertOk()
        ->assertDontSee(__('Rejoindre la visio'));

    // La salle elle-même refuse : aucun embarquement.
    $this->actingAs($user, 'account')->get(route('account.formations.visio', $f))
        ->assertOk()
        ->assertDontSee('jitsi-container', false);
});

it('un inscrit réglé voit le bouton visio', function () {
    $edition = openEdition();
    $user = verifiedAccount();
    participantOf($edition, $user);
    $f = livePaidFormation($edition);
    FormationEnrollment::create([
        'formation_id' => $f->id, 'edition_id' => $edition->id, 'user_id' => $user->id, 'status' => 'paid', 'amount' => 10000,
    ]);

    $this->actingAs($user, 'account')->get(route('account.formations'))
        ->assertOk()
        ->assertSee(__('Rejoindre la visio'));
});

it('un formateur rejoint sans payer et ne se voit pas proposer de régler', function () {
    $edition = openEdition();
    $user = verifiedAccount();
    participantOf($edition, $user);
    $f = livePaidFormation($edition);
    $f->trainers()->create(['name' => 'Awa Kodjo', 'email' => $user->email, 'user_id' => $user->id]);

    expect($f->isTrainer($user))->toBeTrue();

    $this->actingAs($user, 'account')->get(route('account.formations'))
        ->assertOk()
        ->assertSee(__('Rejoindre la visio'))
        ->assertSee(__('Vous animez cette formation'))
        ->assertDontSee(__('Régler'));

    // Et toute tentative d'inscription est neutralisée (aucun paiement créé).
    $this->actingAs($user, 'account')->post(route('account.formations.enroll', $f), ['gateway' => 'manual']);
    expect(Payment::count())->toBe(0);
    expect(FormationEnrollment::count())->toBe(0);
});

it('refuse le paiement d’une formation tant que le profil est incomplet', function () {
    $edition = openEdition();
    $user = verifiedAccount();
    $user->update(['phone' => null]); // profil incomplet
    participantOf($edition, $user);
    $f = publishedFormation($edition, 10000);

    expect($user->fresh()->profileComplete())->toBeFalse();
    expect($user->fresh()->missingProfileFields())->toContain('Téléphone');

    $this->actingAs($user->fresh(), 'account')
        ->post(route('account.formations.enroll', $f), ['gateway' => 'manual'])
        ->assertRedirect(route('account.profile'));

    expect(Payment::count())->toBe(0);
});

it('laisse s’inscrire à une formation gratuite même avec un profil incomplet', function () {
    $edition = openEdition();
    $user = verifiedAccount();
    $user->update(['phone' => null, 'affiliation' => null]);
    participantOf($edition, $user);
    $f = publishedFormation($edition, 0);

    $this->actingAs($user->fresh(), 'account')->post(route('account.formations.enroll', $f));

    expect(FormationEnrollment::where('formation_id', $f->id)->where('user_id', $user->id)->exists())->toBeTrue();
});

it('transmet le téléphone du payeur à la passerelle, avec repli sur le compte', function () {
    $edition = openEdition();
    $user = verifiedAccount();
    // Participant sans téléphone : la donnée doit venir du compte unifié.
    $participant = $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'trainee', 'user_id' => $user->id,
    ]);

    $payment = Payment::create([
        'edition_id' => $edition->id, 'participant_id' => $participant->id,
        'amount' => 10000, 'method' => 'manual', 'gateway' => 'manual',
    ]);

    expect($payment->payerContact()['phone'])->toBe('+22890000000');
    expect($payment->payerContact()['email'])->toBe($user->email);
});
