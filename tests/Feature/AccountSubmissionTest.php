<?php

use App\Livewire\Account\Submissions\Create;
use App\Livewire\Account\Submissions\Index;
use App\Models\Account;
use App\Models\Colloque;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Submission;
use Livewire\Livewire;

function openEdition(array $settings = []): Edition
{
    $colloque = Colloque::create(['name' => 'Colloque UK', 'slug' => 'uk']);

    return $colloque->editions()->create([
        'title' => 'UK 2026',
        'edition_label' => '3e édition',
        'year' => 2026,
        'status' => 'open',
        'slug' => 'uk-2026',
        'submission_open' => true,
        'settings' => array_merge(['tracks' => ['Sciences', 'Santé'], 'max_communications' => 3], $settings),
    ]);
}

function verifiedAccount(): Account
{
    $account = Account::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => 'awa@example.tg', 'password' => 'pass12345',
        'grade' => 'Doctorante', 'affiliation' => 'Université de Kara',
    ]);
    $account->markEmailAsVerified();

    return $account;
}

it('redirige les invités du formulaire de soumission vers la connexion', function () {
    $edition = openEdition();

    $this->get(route('account.submissions.create', $edition->slug))
        ->assertRedirect(route('account.login'));
});

it('renvoie 404 pour une édition non publiée', function () {
    $colloque = Colloque::create(['name' => 'C', 'slug' => 'c']);
    $draft = $colloque->editions()->create(['title' => 'Brouillon', 'status' => 'draft', 'slug' => 'draft']);
    $account = verifiedAccount();

    Livewire::actingAs($account, 'account')
        ->test(Create::class, ['slug' => $draft->slug])
        ->assertStatus(404);
});

it('bloque la soumission si le profil est incomplet', function () {
    $edition = openEdition();
    $account = Account::create([
        'first_name' => 'Kofi', 'last_name' => 'M', 'email' => 'kofi@example.tg', 'password' => 'pass12345',
    ]); // ni vérifié ni affilié

    Livewire::actingAs($account, 'account')
        ->test(Create::class, ['slug' => $edition->slug])
        ->set('title', 'Titre')
        ->set('abstract', str_repeat('a', 60))
        ->call('submit')
        ->assertHasErrors('abstract');

    expect(Submission::count())->toBe(0);
});

it('crée une communication reliée au compte avec co-auteur', function () {
    $edition = openEdition();
    $account = verifiedAccount();

    Livewire::actingAs($account, 'account')
        ->test(Create::class, ['slug' => $edition->slug])
        ->set('title', 'Innovation et développement durable')
        ->set('track', 'Santé')
        ->set('type', 'oral')
        ->set('keywords', 'innovation, durable')
        ->set('abstract', str_repeat('Contexte et objectifs. ', 5))
        ->set('coauthors', [['name' => 'Yao Doe', 'email' => 'yao@example.tg', 'affiliation' => 'UL', 'grade' => 'Dr']])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('successRef', fn ($ref) => filled($ref));

    $submission = Submission::first();
    expect($submission)->not->toBeNull();
    expect($submission->track)->toBe('Santé');
    expect($submission->status)->toBe('submitted');
    expect($submission->authors[0]['corresponding'])->toBeTrue();
    expect($submission->authors[0]['email'])->toBe('awa@example.tg');
    expect($submission->authors[1]['name'])->toBe('Yao Doe');

    // Participant créé et relié au compte (identité imposée).
    $participant = Participant::first();
    expect($participant->account_id)->toBe($account->id);
    expect($participant->last_name)->toBe('Kodjo');
    expect($submission->participant_id)->toBe($participant->id);
});

it('applique le garde-fou du nombre max de communications', function () {
    $edition = openEdition(['max_communications' => 1]);
    $account = verifiedAccount();

    // 1re soumission : OK
    Livewire::actingAs($account, 'account')
        ->test(Create::class, ['slug' => $edition->slug])
        ->set('title', 'Première')
        ->set('track', 'Sciences')
        ->set('abstract', str_repeat('a', 60))
        ->call('submit')
        ->assertHasNoErrors();

    expect(Submission::count())->toBe(1);

    // 2e tentative : bloquée (limite atteinte)
    Livewire::actingAs($account, 'account')
        ->test(Create::class, ['slug' => $edition->slug])
        ->set('title', 'Seconde')
        ->set('track', 'Sciences')
        ->set('abstract', str_repeat('a', 60))
        ->call('submit')
        ->assertHasErrors('abstract');

    expect(Submission::count())->toBe(1);
});

it('liste les communications du compte', function () {
    $edition = openEdition();
    $account = verifiedAccount();
    $participant = $edition->participants()->create([
        'account_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);
    $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-001', 'title' => 'Ma comm', 'status' => 'accepted',
    ]);

    Livewire::actingAs($account, 'account')
        ->test(Index::class)
        ->assertSee('Ma comm')
        ->assertSee('26-001')
        ->assertSee('Acceptée');
});
