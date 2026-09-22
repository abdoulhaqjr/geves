<?php

use App\Models\User;

// Fabriques partagées : verifiedAccount() (tests/Pest.php).

it('confirme un compte sur l’e-mail saisi et renvoie ses champs pour l’auto-complétion', function () {
    $submitter = verifiedAccount();
    User::create([
        'name' => 'Yao Doe', 'first_name' => 'Yao', 'last_name' => 'Doe',
        'email' => 'yao@uk.tg', 'password' => 'pass12345',
        'affiliation' => 'Université de Kara', 'grade' => 'Doctorant',
    ]);

    $this->actingAs($submitter, 'account')
        ->postJson(route('account.authors.check'), ['email' => 'yao@uk.tg'])
        ->assertOk()
        ->assertJsonPath('type', 'ok')
        ->assertJsonPath('account.name', 'Yao Doe')
        ->assertJsonPath('account.affiliation', 'Université de Kara')
        ->assertJsonPath('account.grade', 'Doctorant');
});

it('signale qu’un e-mail inconnu donnera lieu à une invitation', function () {
    $submitter = verifiedAccount();

    $this->actingAs($submitter, 'account')
        ->postJson(route('account.authors.check'), ['email' => 'inconnu@x.tg'])
        ->assertOk()
        ->assertJsonPath('type', 'info');
});

it('ne révèle pas l’existence d’un compte sur un nom seul mais propose une mise en forme', function () {
    $submitter = verifiedAccount();
    User::create([
        'name' => 'Ama Koffi', 'first_name' => 'Ama', 'last_name' => 'Koffi',
        'email' => 'ama@uk.tg', 'password' => 'pass12345',
    ]);

    $res = $this->actingAs($submitter, 'account')
        ->postJson(route('account.authors.check'), ['name' => 'ama KOFFI'])
        ->assertOk();

    expect($res->json('type'))->toBe('muted');       // pas de divulgation d'existence
    expect($res->json('suggestion'))->toBe('Ama Koffi');
});
