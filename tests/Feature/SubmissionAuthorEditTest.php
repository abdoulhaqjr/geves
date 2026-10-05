<?php

use App\Models\Participant;
use App\Models\Submission;
use App\Models\User;

// Fabriques partagées : openEdition(), verifiedAccount() (tests/Pest.php).

/** Communication canonique appartenant au compte donné. */
function ownedSubmission(User $account, string $status = 'submitted'): Submission
{
    $event = openEdition();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => $account->email, 'title' => 'Doctorante',
    ]);

    return $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-777',
        'title' => 'Titre initial', 'abstract' => str_repeat('Mot ', 20),
        'track' => 'Sciences', 'type' => 'oral', 'status' => $status,
    ]);
}

// --- Auteur correspondant & grade par colloque -------------------------------

it('enregistre le grade saisi POUR CE COLLOQUE sur le participant', function () {
    $event = openEdition();
    $account = verifiedAccount(); // grade global : Doctorante

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Étude X',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
            'corresponding' => ['self' => '1', 'grade' => 'Professeur titulaire', 'affiliation' => 'ENS'],
        ])
        ->assertSessionHasNoErrors();

    $participant = Participant::first();
    expect($participant->title)->toBe('Professeur titulaire');
    expect($participant->institution)->toBe('ENS');

    $sub = Submission::first();
    expect($sub->authors[0]['grade'])->toBe('Professeur titulaire');
    expect($sub->authors[0]['is_submitter'])->toBeTrue();

    // Le profil global du compte n'est PAS modifié (grade propre à l'événement).
    expect($account->fresh()->grade)->toBe('Doctorante');
});

it('permet de désigner un autre auteur correspondant sans changer le déposant', function () {
    $event = openEdition();
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Étude Y',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
            'corresponding' => ['self' => '0', 'first_name' => 'Kossi', 'last_name' => 'Adjo', 'email' => 'kossi@example.tg', 'affiliation' => 'UL', 'grade' => 'Professeur'],
        ])
        ->assertSessionHasNoErrors();

    $sub = Submission::first();
    expect($sub->authors[0]['name'])->toBe('Kossi ADJO'); // Prénom + NOM en MAJUSCULES
    expect($sub->authors[0]['email'])->toBe('kossi@example.tg');
    expect($sub->authors[0]['is_submitter'])->toBeFalse();

    // Le déposant reste le compte connecté ; son grade d'événement n'est pas écrasé.
    $participant = Participant::first();
    expect($participant->user_id)->toBe($account->id);
    expect($participant->title)->toBe('Doctorante');
});

it('force l’identité du compte quand l’utilisateur se déclare auteur correspondant', function () {
    $event = openEdition();
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.store', $event->slug), [
            'title' => 'Étude Z',
            'track' => 'Sciences',
            'abstract' => str_repeat('a', 60),
            'corresponding' => ['self' => '1', 'first_name' => 'Usurpateur', 'last_name' => 'Faux', 'email' => 'fake@example.tg'],
        ])
        ->assertSessionHasNoErrors();

    $sub = Submission::first();
    expect($sub->authors[0]['name'])->toBe('Awa KODJO'); // identité forcée depuis le compte (NOM en majuscules)
    expect($sub->authors[0]['email'])->toBe('awa@example.tg');
});

// --- Correction du résumé tant que non évalué --------------------------------

it('permet à l’auteur de corriger son résumé tant qu’aucun relecteur n’y a touché', function () {
    $account = verifiedAccount();
    $sub = ownedSubmission($account, 'submitted');

    expect($sub->isAuthorEditable())->toBeTrue();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.update', $sub), [
            'title' => 'Titre corrigé',
            'abstract' => str_repeat('nouveau ', 20),
            'keywords' => 'a, b',
        ])
        ->assertRedirect(route('account.submissions.show', $sub));

    $sub->refresh();
    expect($sub->title)->toBe('Titre corrigé');
    expect(strip_tags((string) $sub->abstract))->toContain('nouveau');
});

it('bloque la correction du résumé dès qu’une relecture existe', function () {
    $account = verifiedAccount();
    $sub = ownedSubmission($account, 'submitted');
    $sub->reviews()->create(['status' => 'invited']); // un relecteur a été affecté

    expect($sub->fresh()->isAuthorEditable())->toBeFalse();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.update', $sub), [
            'title' => 'Tentative', 'abstract' => str_repeat('x ', 20),
        ])
        ->assertForbidden();
});

it('bloque la correction du résumé après une décision', function () {
    $account = verifiedAccount();
    $sub = ownedSubmission($account, 'accepted');

    expect($sub->isAuthorEditable())->toBeFalse();

    $this->actingAs($account, 'account')
        ->post(route('account.submissions.update', $sub), [
            'title' => 'Tentative', 'abstract' => str_repeat('x ', 20),
        ])
        ->assertForbidden();
});
