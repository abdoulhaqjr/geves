<?php

use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\User;
use App\Support\ParticipantDashboard;

/**
 * **Le déposant n'est pas toujours l'auteur.**
 *
 * ⚠️⚠️ **TROIS POSITIONS, ET LE CODE N'EN CONNAISSAIT QUE DEUX.** Tout ne
 * demandait qu'une chose - « ce compte a-t-il DÉPOSÉ ? » - et rangeait tout le
 * reste en co-auteur. Un secrétariat dépose pourtant pour une équipe, et
 * **l'auteur correspondant, quand il créait enfin son compte, s'affichait en
 * co-auteur de sa propre communication** : « 0 déposée - 1 en co-auteur » sur le
 * tableau de bord, « Seul le déposant peut la modifier » sur la fiche, et aucun
 * moyen de répondre à une demande de correction - alors que c'est précisément
 * son travail.
 *
 * ⚠️ **Le déposant garde la main**, et c'est voulu : l'auteur correspondant peut
 * très bien ne jamais se connecter. Une communication déposée par un
 * secrétariat deviendrait sinon incorrigible.
 */

/**
 * Une communication déposée par QUELQU'UN D'AUTRE que son auteur correspondant.
 *
 * @return array{submission: Submission, deposant: User, correspondant: User, coauteur: User}
 */
function communicationDeposeePourAutrui(string $statut = 'submitted'): array
{
    $event = openEdition();

    $deposant = User::create([
        'first_name' => 'Sara', 'last_name' => 'Secrétaire',
        'email' => 'secretariat.depot@example.tg', 'password' => 'pass12345',
    ]);
    $deposant->markEmailAsVerified();

    $correspondant = User::create([
        'first_name' => 'Marcelle', 'last_name' => 'DANAME',
        'email' => 'marcelle.correspondante@example.tg', 'password' => 'pass12345',
    ]);
    $correspondant->markEmailAsVerified();

    $coauteur = User::create([
        'first_name' => 'Babénoun', 'last_name' => 'LARE',
        'email' => 'babenoun.coauteur@example.tg', 'password' => 'pass12345',
    ]);
    $coauteur->markEmailAsVerified();

    // La fiche de participation appartient au DÉPOSANT : c'est elle que porte
    // la communication.
    $participant = $event->participants()->create([
        'user_id' => $deposant->id, 'first_name' => 'Sara', 'last_name' => 'Secrétaire',
        'email' => $deposant->email,
    ]);

    $submission = $event->submissions()->create([
        'participant_id' => $participant->id,
        'ref_code' => '26-050',
        'title' => 'Les facteurs de précarité hydrique',
        'abstract' => 'Résumé de la communication déposée pour autrui.',
        'status' => $statut,
        'type' => 'oral',
        'revision_of' => 0,
    ]);

    // Les rattachements auteurs ↔ comptes, tels que les pose `LinkSubmissionAuthors`.
    SubmissionAuthor::create([
        'submission_id' => $submission->id, 'event_id' => $event->id,
        'role' => 'corresponding', 'author_index' => 0,
        'name' => 'DANAME Marcelle', 'email' => $correspondant->email,
        'token' => Str::random(48), 'status' => 'linked', 'user_id' => $correspondant->id,
    ]);
    SubmissionAuthor::create([
        'submission_id' => $submission->id, 'event_id' => $event->id,
        'role' => 'coauthor', 'author_index' => 1,
        'name' => 'LARE Babénoun', 'email' => $coauteur->email,
        'token' => Str::random(48), 'status' => 'linked', 'user_id' => $coauteur->id,
    ]);

    return compact('submission', 'deposant', 'correspondant', 'coauteur');
}

// ------------------------------------------------ LES TROIS POSITIONS -------

it('distingue le déposant, l’auteur correspondant et le co-auteur', function () {
    ['submission' => $s, 'deposant' => $d, 'correspondant' => $c, 'coauteur' => $co] = communicationDeposeePourAutrui();

    expect($s->wasDepositedBy($d))->toBeTrue();
    expect($s->isCorrespondingAuthor($d))->toBeFalse();

    // ⚠️ Le cas du défaut : il n'a rien déposé, il porte pourtant la communication.
    expect($s->wasDepositedBy($c))->toBeFalse();
    expect($s->isCorrespondingAuthor($c))->toBeTrue();

    expect($s->wasDepositedBy($co))->toBeFalse();
    expect($s->isCorrespondingAuthor($co))->toBeFalse();
});

/** ⚠️ **Les DEUX**, pas l'un à la place de l'autre. */
it('ouvre la modification au déposant ET à l’auteur correspondant', function () {
    ['submission' => $s, 'deposant' => $d, 'correspondant' => $c, 'coauteur' => $co] = communicationDeposeePourAutrui();

    expect($s->isEditableBy($d))->toBeTrue();
    expect($s->isEditableBy($c))->toBeTrue();
    expect($s->isEditableBy($co))->toBeFalse();
    expect($s->isEditableBy(null))->toBeFalse();
});

// ------------------------------------------------------------ LA FICHE ------

it('laisse l’auteur correspondant corriger sa communication', function () {
    ['submission' => $s, 'correspondant' => $c] = communicationDeposeePourAutrui('revision');

    // La page de correction s'ouvre…
    $this->actingAs($c, 'account')
        ->get(route('account.submissions.revise', $s))
        ->assertOk();

    // … et la resoumission aboutit.
    $this->actingAs($c, 'account')
        ->post(route('account.submissions.resubmit', $s), [
            'title' => 'Titre corrigé par l’auteur correspondant',
            'abstract' => str_repeat('Contenu corrigé. ', 6),
        ])
        ->assertSessionHasNoErrors();

    expect($s->fresh()->title)->toBe('Titre corrigé par l’auteur correspondant');
});

/** ⚠️ Le déposant garde la main : l'auteur correspondant peut ne jamais se connecter. */
it('laisse le déposant corriger, lui aussi', function () {
    ['submission' => $s, 'deposant' => $d] = communicationDeposeePourAutrui('revision');

    $this->actingAs($d, 'account')
        ->post(route('account.submissions.resubmit', $s), [
            'title' => 'Titre corrigé par le déposant',
            'abstract' => str_repeat('Contenu corrigé. ', 6),
        ])
        ->assertSessionHasNoErrors();

    expect($s->fresh()->title)->toBe('Titre corrigé par le déposant');
});

it('ferme la correction à un simple co-auteur', function () {
    ['submission' => $s, 'coauteur' => $co] = communicationDeposeePourAutrui('revision');

    $this->actingAs($co, 'account')->get(route('account.submissions.revise', $s))->assertNotFound();
    $this->actingAs($co, 'account')
        ->post(route('account.submissions.resubmit', $s), [
            'title' => 'Tentative', 'abstract' => str_repeat('Contenu. ', 6),
        ])
        ->assertNotFound();

    expect($s->fresh()->title)->toBe('Les facteurs de précarité hydrique');
});

it('nomme chaque position sur la fiche', function () {
    ['submission' => $s, 'deposant' => $d, 'correspondant' => $c, 'coauteur' => $co] = communicationDeposeePourAutrui();

    // ⚠️ L'auteur correspondant était accueilli par « Seul le déposant peut la modifier ».
    $this->actingAs($c, 'account')->get(route('account.submissions.show', $s))
        ->assertOk()
        ->assertSee('auteur correspondant', false)
        ->assertDontSee('Seul le déposant peut la modifier', false);

    $this->actingAs($co, 'account')->get(route('account.submissions.show', $s))
        ->assertOk()
        ->assertSee('co-auteur', false);

    // Le déposant, lui, n'a aucun bandeau de rattachement.
    $this->actingAs($d, 'account')->get(route('account.submissions.show', $s))
        ->assertOk()
        ->assertDontSee('Vous êtes rattaché(e)', false);
});

// ------------------------------------------------- LE TABLEAU DE BORD -------

/**
 * ⚠️⚠️ **« 0 déposée - 1 en co-auteur » : le symptôme visible.** L'indicateur
 * opposait « déposée » à « en co-auteur », et l'auteur correspondant dont un
 * tiers a déposé la communication n'entrait dans aucune des deux.
 */
it('compte l’auteur correspondant à sa place, pas en co-auteur', function () {
    ['submission' => $s, 'deposant' => $d, 'correspondant' => $c, 'coauteur' => $co] = communicationDeposeePourAutrui();
    $event = $s->event;

    $detail = fn (User $u) => collect((new ParticipantDashboard($u, $event))->indicateurs())
        ->firstWhere('libelle', 'Mes communications')['detail'];

    expect($detail($c))->toContain('auteur correspondant')->not->toContain('co-auteur');
    expect($detail($co))->toContain('co-auteur')->not->toContain('auteur correspondant');
    expect($detail($d))->toContain('déposée');
});

/** ⚠️ « Vous co-présentez » s'affichait sur son propre passage. */
it('ne fait pas co-présenter l’auteur correspondant', function () {
    ['submission' => $s, 'correspondant' => $c, 'coauteur' => $co] = communicationDeposeePourAutrui('accepted');
    $event = $s->event;

    $slot = $event->slots()->create([
        'submission_id' => $s->id,
        'participant_id' => $s->participant_id,
        'starts_at' => now()->addDays(3),
        'room' => 'Salle 1',
    ]);

    expect((new ParticipantDashboard($c, $event))->roleSur($slot))->toBe('correspondant');
    expect((new ParticipantDashboard($co, $event))->roleSur($slot))->toBe('coauteur');
});

/**
 * ⚠️ Un rattachement qui n'est pas CONFIRMÉ n'ouvre rien : une invitation
 * déclinée ou en attente laisse la communication fermée.
 */
it('n’ouvre rien sur un rattachement non confirmé', function () {
    ['submission' => $s, 'correspondant' => $c] = communicationDeposeePourAutrui('revision');

    SubmissionAuthor::where('submission_id', $s->id)
        ->where('user_id', $c->id)->update(['status' => 'invited']);

    $s->unsetRelation('authorLinks');

    expect($s->isCorrespondingAuthor($c))->toBeFalse();
    expect($s->isEditableBy($c))->toBeFalse();

    $this->actingAs($c, 'account')->get(route('account.submissions.revise', $s))->assertNotFound();
});
