<?php

use App\Models\EventSeries;
use App\Models\Participant;
use App\Models\Submission;
use App\Models\User;

// openEdition() et verifiedAccount() sont définis dans tests/Pest.php (fabriques partagées).

it('redirige les invités du formulaire de soumission vers la connexion', function () {
    $event = openEdition();

    $this->get(route('account.submissions.create', $event->slug))
        ->assertRedirect(route('account.login'));
});

it('renvoie 404 pour un événement non publiée', function () {
    $series = EventSeries::create(['name' => 'C', 'slug' => 'c']);
    $draft = $series->events()->create(['title' => 'Brouillon', 'status' => 'draft', 'slug' => 'draft']);
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->get(route('account.submissions.create', $draft->slug))
        ->assertNotFound();
});

it('bloque la soumission si le profil est incomplet', function () {
    $event = openEdition();
    $account = User::create([
        'first_name' => 'Kofi', 'last_name' => 'M', 'email' => 'kofi@example.tg', 'password' => 'pass12345',
    ]); // ni vérifié ni affilié

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Titre',
            'abstract' => str_repeat('a', 60),
        ])
        ->assertSessionHasErrors('abstract');

    expect(Submission::count())->toBe(0);
});

it('crée une communication reliée au compte avec co-auteur', function () {
    $event = openEdition();
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Innovation et développement durable',
            'track' => 'Santé',
            'type' => 'oral',
            'keywords' => 'innovation, durable',
            'abstract' => str_repeat('Contexte et objectifs. ', 5),
            'coauthors' => [['first_name' => 'Yao', 'last_name' => 'Doe', 'email' => 'yao@example.tg', 'affiliation' => 'UL', 'grade' => 'Dr']],
        ])
        ->assertRedirect(route('account.submissions.create', $event->slug))
        ->assertSessionHas('success_ref');

    $submission = Submission::first();
    expect($submission)->not->toBeNull();
    expect($submission->track)->toBe('Santé');
    expect($submission->status)->toBe('submitted');
    expect($submission->authors[0]['corresponding'])->toBeTrue();
    expect($submission->authors[0]['email'])->toBe('awa@example.tg');
    expect($submission->authors[1]['name'])->toBe('Yao DOE'); // nom de famille en MAJUSCULES
    expect($submission->authors[1]['last_name'])->toBe('DOE');

    // Participant créé et relié au compte (identité imposée).
    $participant = Participant::first();
    expect($participant->user_id)->toBe($account->id);
    expect($participant->last_name)->toBe('Kodjo');
    expect($submission->participant_id)->toBe($participant->id);
});

it('refuse un co-auteur identique à l’auteur correspondant (même e-mail)', function () {
    $event = openEdition();
    $account = verifiedAccount(); // e-mail awa@example.tg (auteur correspondant « self »)

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Doublon auteur',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
            'coauthors' => [['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'affiliation' => 'UK']],
        ])
        ->assertSessionHasErrors('coauthors');

    expect(Submission::count())->toBe(0);
});

it('exige nom, e-mail et affiliation pour un co-auteur', function () {
    $event = openEdition();
    $account = verifiedAccount();

    // Nom + e-mail mais affiliation manquante → refusé.
    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Co-auteur incomplet',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
            'coauthors' => [['first_name' => 'Yao', 'last_name' => 'Doe', 'email' => 'yao@x.tg', 'affiliation' => '']],
        ])
        ->assertSessionHasErrors('coauthors');

    expect(Submission::count())->toBe(0);
});

it('refuse deux co-auteurs avec le même e-mail', function () {
    $event = openEdition();
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Doublon co-auteur',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
            'coauthors' => [
                ['first_name' => 'A', 'last_name' => 'Un', 'email' => 'dup@x.tg', 'affiliation' => 'UK'],
                ['first_name' => 'B', 'last_name' => 'Deux', 'email' => 'dup@x.tg', 'affiliation' => 'UK'],
            ],
        ])
        ->assertSessionHasErrors('coauthors');

    expect(Submission::count())->toBe(0);
});

it('refuse de dépasser le nombre maximum de co-auteurs', function () {
    $event = openEdition(['coauthors_max' => 1]);
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Trop de co-auteurs',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
            'coauthors' => [
                ['first_name' => 'A', 'last_name' => 'Un', 'email' => 'a1@x.tg', 'affiliation' => 'UK'],
                ['first_name' => 'B', 'last_name' => 'Deux', 'email' => 'b2@x.tg', 'affiliation' => 'UK'],
            ],
        ])
        ->assertSessionHasErrors('coauthors');

    expect(Submission::count())->toBe(0);
});

it('applique le garde-fou du nombre max de communications', function () {
    $event = openEdition(['max_communications' => 1]);
    $account = verifiedAccount();

    // 1re soumission : OK
    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Première',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
        ])
        ->assertSessionHasNoErrors();

    expect(Submission::count())->toBe(1);

    // 2e tentative : bloquée (limite atteinte)
    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Seconde',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
        ])
        ->assertSessionHasErrors('abstract');

    expect(Submission::count())->toBe(1);
});

/**
 * ⚠️ **Une pastille d'ÉTAT ne se coupe pas en deux.** « Correction demandée »
 * tombait sur deux lignes dans la colonne Statut et doublait la hauteur de la
 * rangée ; la cellule d'actions, elle, tenait sa ligne par un `style=` en
 * ligne - qui l'emporte sur toute règle, media query comprise.
 *
 * ⚠️ La règle se lit dans la FEUILLE, commentaires retirés : un commentaire qui
 * cite le sélecteur ferait passer la garde sans qu'aucune règle n'existe (même
 * leçon que `DegradeHerosTest`).
 */
it('garde la pastille d’état et les actions sur une seule ligne', function () {
    $css = (string) file_get_contents(resource_path('css/ukc.css'));
    $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);

    expect($css)
        ->toContain('.ukc-shell .ukc-table .ukc-pill { white-space: nowrap; }')
        ->toContain('.ukc-shell .ukc-table td.ukc-cell-actions');

    $vue = (string) file_get_contents(resource_path('views/account/submissions/index.blade.php'));
    expect($vue)
        ->toContain('class="ukc-cell-actions"')
        ->not->toContain('style="text-align:right;white-space:nowrap;"');
});

it('liste les communications du compte', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);
    $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-001', 'title' => 'Ma comm', 'status' => 'accepted',
    ]);

    $this->actingAs($account, 'account')
        ->get(route('account.submissions.index'))
        ->assertOk()
        ->assertSee('Ma comm')
        ->assertSee('26-001')
        ->assertSee('Acceptée');
});
