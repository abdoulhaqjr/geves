<?php

use App\Documents\LivretView;
use App\Models\Participant;
use App\Models\User;
use App\Support\EventSettings;

/*
|--------------------------------------------------------------------------
| Visibilité du livre des résumés dans l'espace participant
|--------------------------------------------------------------------------
|
| Même grammaire que les badges : réglage propre à l'événement, repli global,
| ouvert par défaut. Le livre compile les résumés acceptés - tant que le
| programme n'est pas arrêté, l'organisation ne veut pas qu'il circule.
|
*/

/** Un événement dont le livre a du contenu, et un participant inscrit. */
function evenementAvecLivret(): array
{
    $submission = seededSubmission('accepted');
    $event = $submission->event;
    $participant = $submission->participant;

    return [$event, $participant->user ?? User::find($participant->user_id)];
}

it('ouvre le livre par défaut', function () {
    // ⚠️ C'était le comportement jusqu'ici : un défaut à « caché » retirerait
    // le livre à tous les événements déjà en base sans que personne ne l'ait
    // décidé.
    [$event, $compte] = evenementAvecLivret();

    expect(LivretView::visibleToParticipant($event))->toBeTrue();

    $this->actingAs($compte, 'account')->get(route('account.documents'))
        ->assertOk()
        ->assertSee('Livret des résumés');

    $this->actingAs($compte, 'account')->get(route('account.documents.livret', $event))
        ->assertOk();
});

it('retire le livre de « Mes documents » quand il est caché', function () {
    [$event, $compte] = evenementAvecLivret();
    EventSettings::set($event, 'livret.visible_to_participants', false);

    expect(LivretView::visibleToParticipant($event->fresh()))->toBeFalse();

    $this->actingAs($compte, 'account')->get(route('account.documents'))
        ->assertOk()
        ->assertDontSee('Livret des résumés');
});

it('FERME LA PORTE, pas seulement la carte', function () {
    // ⚠️⚠️ Retirer le lien n'empêche personne de poster l'adresse : sans garde
    // sur la route, le livre resterait téléchargeable alors que l'organisation
    // l'a fermé - et c'est précisément le cas où elle ne veut pas qu'il circule.
    [$event, $compte] = evenementAvecLivret();
    EventSettings::set($event, 'livret.visible_to_participants', false);

    $this->actingAs($compte, 'account')->get(route('account.documents.livret', $event))
        ->assertForbidden();
});

it('ne retire JAMAIS le livre au gestionnaire', function () {
    // ⚠️ Ce réglage ne gouverne que l'espace participant : fermer la porte au
    // gestionnaire lui retirerait l'outil qui sert à décider si le livre est prêt.
    [$event] = evenementAvecLivret();
    EventSettings::set($event, 'livret.visible_to_participants', false);

    $admin = adminStaff();

    $this->actingAs($admin, 'web')
        ->get(route('admin.documents.livret', $event).'?format=pdf&inline=1&raw=1')
        ->assertOk();
});

it('se règle par ÉVÉNEMENT', function () {
    // Un colloque peut ouvrir son livre pendant que celui d'à côté le garde
    // fermé. ⚠️ Le second événement vient d'`openEdition()` et non du helper :
    // celui-ci fabrique son déposant avec un téléphone FIXE, et `users.phone`
    // est unique - deux appels tombent sur la contrainte.
    [$ouvert] = evenementAvecLivret();
    $ferme = openEdition();
    EventSettings::set($ferme, 'livret.visible_to_participants', false);

    expect(LivretView::visibleToParticipant($ouvert->fresh()))->toBeTrue()
        ->and(LivretView::visibleToParticipant($ferme->fresh()))->toBeFalse();
});

it('se règle depuis les options du livre, et le dit', function () {
    [$event] = evenementAvecLivret();
    $admin = adminStaff();

    $this->actingAs($admin, 'web')
        ->get(route('admin.documents.attestations', ['event_id' => $event->id, 'tab' => 'livret']))
        ->assertOk()
        ->assertSee('Visibilité dans l’espace participant', false)
        ->assertSee('livretVisible', false);

    $this->actingAs($admin, 'web')
        ->post(route('admin.documents.livret.options'), [
            'event_id' => $event->id,
            'livretVisible' => '0',
        ])->assertRedirect();

    expect(LivretView::visibleToParticipant($event->fresh()))->toBeFalse();

    // Et on peut le rouvrir.
    $this->actingAs($admin, 'web')
        ->post(route('admin.documents.livret.options'), [
            'event_id' => $event->id,
            'livretVisible' => '1',
        ]);

    expect(LivretView::visibleToParticipant($event->fresh()))->toBeTrue();
});

it('ne s’affiche pas davantage quand le livre est vide', function () {
    // Les deux conditions comptent : contenu ET visibilité. Un livre ouvert mais
    // sans aucun résumé accepté n'a rien à montrer.
    $submission = seededSubmission('submitted');
    $event = $submission->event;
    $compte = User::find($submission->participant->user_id);

    expect(LivretView::visibleToParticipant($event))->toBeTrue();

    $this->actingAs($compte, 'account')->get(route('account.documents'))
        ->assertOk()
        ->assertDontSee('Livret des résumés');
});

it('ne ferme pas le livre quand le champ n’est pas posté', function () {
    // ⚠️⚠️ C'est la raison d'être du `select` et de `input(..., '1')` plutôt que
    // de `boolean()` : une case à cocher décochée n'envoie RIEN, et `boolean()`
    // ne distingue pas « caché » de « champ absent ». Un enregistrement du
    // formulaire qui ne porterait pas le champ fermerait alors le livre dans le
    // dos du gestionnaire, sans un mot.
    [$event] = evenementAvecLivret();
    $admin = adminStaff();

    $this->actingAs($admin, 'web')
        ->post(route('admin.documents.livret.options'), [
            'event_id' => $event->id,
            'forewordTitle' => 'Avant-propos',
        ])->assertRedirect();

    expect(LivretView::visibleToParticipant($event->fresh()))->toBeTrue();
});

it('garde le réglage quand on enregistre les autres options du livre', function () {
    // ⚠️ Le formulaire « Contenu du livre » poste tout d'un coup : un
    // enregistrement qui ne renverrait pas le champ rouvrirait le livre dans le
    // dos du gestionnaire.
    [$event] = evenementAvecLivret();
    EventSettings::set($event, 'livret.visible_to_participants', false);
    $admin = adminStaff();

    $this->actingAs($admin, 'web')
        ->post(route('admin.documents.livret.options'), [
            'event_id' => $event->id,
            'livretVisible' => '0',
            'forewordTitle' => 'Avant-propos',
            'forewordText' => 'Un mot du comité.',
        ])->assertRedirect();

    expect(LivretView::visibleToParticipant($event->fresh()))->toBeFalse()
        ->and((string) EventSettings::get($event->fresh(), 'livret.foreword_text'))->toBe('Un mot du comité.');
});
