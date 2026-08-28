<?php

use App\Models\Account;
use App\Models\Colloque;
use App\Models\Edition;
use App\Support\AccountActivity;
use Illuminate\Support\Str;

// openEdition() -> AccountSubmissionTest

function accountWith(string $email = 'pro@uk.tg'): Account
{
    $account = Account::create([
        'first_name' => 'Pro', 'last_name' => 'Fesseur',
        'email' => $email, 'password' => 'pass12345',
        'grade' => 'Professeur', 'affiliation' => 'UK',
    ]);
    $account->markEmailAsVerified();

    return $account;
}

function otherEdition(string $slug = 'uk-2027'): Edition
{
    $colloque = Colloque::create(['name' => 'Colloque UK', 'slug' => 'uk-'.Str::random(4)]);

    return $colloque->editions()->create(['title' => 'UK 2027', 'status' => 'open', 'slug' => $slug]);
}

it('dérive « Communicant » depuis une communication de l’auteur', function () {
    $edition = openEdition();
    $account = accountWith();
    $p = $edition->participants()->create(['account_id' => $account->id, 'first_name' => 'Pro', 'last_name' => 'Fesseur', 'email' => 'pro@uk.tg', 'role' => 'author']);
    $edition->submissions()->create(['participant_id' => $p->id, 'title' => 'T', 'status' => 'submitted', 'revision_of' => 0, 'ref_code' => 'AA-001', 'access_token' => Str::random(64), 'authors' => [['name' => 'Pro Fesseur', 'email' => 'pro@uk.tg', 'corresponding' => true]]]);

    expect((new AccountActivity($account))->roles())->toContain('Communicant');
});

it('dérive « Communicant » depuis une communication en co-auteur (par e-mail)', function () {
    $edition = openEdition();
    $account = accountWith();
    // Communication déposée par quelqu'un d'autre, avec le compte en co-auteur.
    $other = $edition->participants()->create(['first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@uk.tg', 'role' => 'author']);
    $edition->submissions()->create(['participant_id' => $other->id, 'title' => 'Co', 'status' => 'accepted', 'revision_of' => 0, 'ref_code' => 'AA-002', 'access_token' => Str::random(64), 'authors' => [
        ['name' => 'X Y', 'email' => 'x@uk.tg', 'corresponding' => true],
        ['name' => 'Pro Fesseur', 'email' => 'pro@uk.tg', 'corresponding' => false],
    ]]);

    $activity = new AccountActivity($account);
    expect($activity->roles())->toContain('Communicant');
    expect($activity->communications()->first()['corresponding'])->toBeFalse();
});

it('dérive modérateur, rapporteur et comité par e-mail sur d’autres éditions', function () {
    $account = accountWith();

    $e1 = otherEdition('uk-a');
    $e1->sessions()->create(['type' => 'plenary', 'label' => 'Plénière', 'moderator_email' => 'pro@uk.tg']);

    $e2 = otherEdition('uk-b');
    $e2->sessions()->create(['type' => 'plenary', 'label' => 'Clôture', 'rapporteur_email' => 'pro@uk.tg']);

    $e3 = otherEdition('uk-c');
    $e3->committeeMembers()->create(['person_name' => 'Pro Fesseur', 'person_title' => 'Président', 'role' => 'scientifique', 'email' => 'pro@uk.tg']);

    $roles = (new AccountActivity($account))->roles();

    expect($roles)->toContain('Modérateur');
    expect($roles)->toContain('Rapporteur');
    expect($roles)->toContain('Membre de comité');
});

it('rapproche un membre de comité sans e-mail par le nom complet', function () {
    $account = accountWith();
    $e = otherEdition('uk-name');
    $e->committeeMembers()->create(['person_name' => 'Pro Fesseur', 'person_title' => 'Membre', 'role' => 'organisation', 'email' => '']);

    expect((new AccountActivity($account))->committees())->toHaveCount(1);
});

it('dérive « Relecteur » d’une participation relecteur', function () {
    $edition = openEdition();
    $account = accountWith();
    $edition->participants()->create(['account_id' => $account->id, 'first_name' => 'Pro', 'last_name' => 'Fesseur', 'email' => 'pro@uk.tg', 'role' => 'reviewer']);

    expect((new AccountActivity($account))->roles())->toContain('Relecteur');
});

it('retombe sur « Auditeur » pour une simple participation sans rôle fort', function () {
    $edition = openEdition();
    $account = accountWith();
    $edition->participants()->create(['account_id' => $account->id, 'first_name' => 'Pro', 'last_name' => 'Fesseur', 'email' => 'pro@uk.tg', 'role' => 'attendee']);

    expect((new AccountActivity($account))->roles())->toBe(['Auditeur']);
});

it('n’attribue aucun rôle à un compte sans activité', function () {
    $account = accountWith();

    expect((new AccountActivity($account))->roles())->toBe([]);
});

it('affiche les rôles dérivés sur le tableau de bord participant', function () {
    $account = accountWith();
    $e = otherEdition('uk-dash');
    $e->sessions()->create(['type' => 'plenary', 'label' => 'Plénière', 'moderator_email' => 'pro@uk.tg']);

    $this->actingAs($account, 'account')
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Mes rôles')
        ->assertSee('Modérateur');
});
