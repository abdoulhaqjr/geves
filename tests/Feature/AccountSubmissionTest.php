<?php

use App\Models\Colloque;
use App\Models\Participant;
use App\Models\Submission;
use App\Models\User;

// openEdition() et verifiedAccount() sont définis dans tests/Pest.php (fabriques partagées).

it('redirige les invités du formulaire de soumission vers la connexion', function () {
    $edition = openEdition();

    $this->get(route('account.submissions.create', $edition->slug))
        ->assertRedirect(route('account.login'));
});

it('renvoie 404 pour une édition non publiée', function () {
    $colloque = Colloque::create(['name' => 'C', 'slug' => 'c']);
    $draft = $colloque->editions()->create(['title' => 'Brouillon', 'status' => 'draft', 'slug' => 'draft']);
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->get(route('account.submissions.create', $draft->slug))
        ->assertNotFound();
});

it('bloque la soumission si le profil est incomplet', function () {
    $edition = openEdition();
    $account = User::create([
        'first_name' => 'Kofi', 'last_name' => 'M', 'email' => 'kofi@example.tg', 'password' => 'pass12345',
    ]); // ni vérifié ni affilié

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $edition->slug), [
            'title' => 'Titre',
            'abstract' => str_repeat('a', 60),
        ])
        ->assertSessionHasErrors('abstract');

    expect(Submission::count())->toBe(0);
});

it('crée une communication reliée au compte avec co-auteur', function () {
    $edition = openEdition();
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $edition->slug), [
            'title' => 'Innovation et développement durable',
            'track' => 'Santé',
            'type' => 'oral',
            'keywords' => 'innovation, durable',
            'abstract' => str_repeat('Contexte et objectifs. ', 5),
            'coauthors' => [['name' => 'Yao Doe', 'email' => 'yao@example.tg', 'affiliation' => 'UL', 'grade' => 'Dr']],
        ])
        ->assertRedirect(route('account.submissions.create', $edition->slug))
        ->assertSessionHas('success_ref');

    $submission = Submission::first();
    expect($submission)->not->toBeNull();
    expect($submission->track)->toBe('Santé');
    expect($submission->status)->toBe('submitted');
    expect($submission->authors[0]['corresponding'])->toBeTrue();
    expect($submission->authors[0]['email'])->toBe('awa@example.tg');
    expect($submission->authors[1]['name'])->toBe('Yao Doe');

    // Participant créé et relié au compte (identité imposée).
    $participant = Participant::first();
    expect($participant->user_id)->toBe($account->id);
    expect($participant->last_name)->toBe('Kodjo');
    expect($submission->participant_id)->toBe($participant->id);
});

it('refuse un co-auteur identique à l’auteur correspondant (même e-mail)', function () {
    $edition = openEdition();
    $account = verifiedAccount(); // e-mail awa@example.tg (auteur correspondant « self »)

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $edition->slug), [
            'title' => 'Doublon auteur',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
            'coauthors' => [['name' => 'Awa Kodjo', 'email' => 'awa@example.tg']],
        ])
        ->assertSessionHasErrors('coauthors');

    expect(Submission::count())->toBe(0);
});

it('refuse deux co-auteurs avec le même e-mail', function () {
    $edition = openEdition();
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $edition->slug), [
            'title' => 'Doublon co-auteur',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
            'coauthors' => [['name' => 'A', 'email' => 'dup@x.tg'], ['name' => 'B', 'email' => 'dup@x.tg']],
        ])
        ->assertSessionHasErrors('coauthors');

    expect(Submission::count())->toBe(0);
});

it('applique le garde-fou du nombre max de communications', function () {
    $edition = openEdition(['max_communications' => 1]);
    $account = verifiedAccount();

    // 1re soumission : OK
    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $edition->slug), [
            'title' => 'Première',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
        ])
        ->assertSessionHasNoErrors();

    expect(Submission::count())->toBe(1);

    // 2e tentative : bloquée (limite atteinte)
    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $edition->slug), [
            'title' => 'Seconde',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
        ])
        ->assertSessionHasErrors('abstract');

    expect(Submission::count())->toBe(1);
});

it('liste les communications du compte', function () {
    $edition = openEdition();
    $account = verifiedAccount();
    $participant = $edition->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);
    $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-001', 'title' => 'Ma comm', 'status' => 'accepted',
    ]);

    $this->actingAs($account, 'account')
        ->get(route('account.submissions.index'))
        ->assertOk()
        ->assertSee('Ma comm')
        ->assertSee('26-001')
        ->assertSee('Acceptée');
});
