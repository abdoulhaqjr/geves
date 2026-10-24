<?php

use App\Actions\RegisterAttendee;
use App\Actions\UpsertParticipantForAccount;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Participant;

/**
 * **S'inscrire sans présenter de communication.**
 *
 * ⚠️⚠️ **IL N'Y AVAIT AUCUNE PORTE** (2026-10-24). On ne devenait `Participant`
 * qu'en déposant un résumé, en étant rapproché comme co-auteur, en s'inscrivant
 * à une formation, ou en étant saisi au guichet. Celui qui vient seulement
 * écouter n'avait aucun chemin - et `PaymentController::pay()` exigeait de
 * toute façon une **communication acceptée**, donc il n'aurait rien pu régler.
 *
 * Le plus gênant : le rôle « Participant simple » existait déjà dans
 * `Participant::ROLES`, la page publique promettait noir sur blanc de pouvoir
 * « s'inscrire comme participant simple », et la page d'inscription renvoyait
 * vers « contactez l'organisation ».
 */

/** Un événement ouvert, avec une grille tarifaire par grade. */
function evenementAvecTarifs(array $settings = []): Event
{
    $serie = EventSeries::create(['name' => 'Série inscription', 'slug' => 'serie-inscription']);

    return $serie->events()->create([
        'title' => 'Journées 2026', 'year' => 2026, 'status' => 'open',
        'slug' => 'journees-inscription-2026',
        'submission_open' => false,
        'settings' => array_merge([
            'tracks' => ['Sciences'],
            'max_communications' => 3,
            'fees' => [
                ['label' => 'Doctorants', 'amount' => 5000, 'grades' => ['Doctorant'], 'default' => false],
                ['label' => 'Autres participants', 'amount' => 25000, 'grades' => [], 'default' => true],
            ],
        ], $settings),
    ]);
}

// ------------------------------------------------------------- LA PORTE -----

it('inscrit un compte qui ne présente aucune communication', function () {
    $event = evenementAvecTarifs();
    $account = verifiedAccount();

    expect(Participant::where('event_id', $event->id)->where('user_id', $account->id)->exists())->toBeFalse();

    $this->actingAs($account, 'account')
        ->post(route('account.registration.store', $event), [
            'grade' => 'Doctorant',
            'affiliation' => 'Université de Kara',
        ])
        ->assertRedirect(route('account.payment', $event))
        ->assertSessionHasNoErrors();

    $participant = Participant::where('event_id', $event->id)->where('user_id', $account->id)->firstOrFail();

    expect($participant->role)->toBe('attendee');
    expect($participant->title)->toBe('Doctorant');
    expect($participant->institution)->toBe('Université de Kara');
    // ⚠️ La DATE, pas le rôle : c'est elle qui prouve qu'il y a des frais dus.
    expect($participant->isRegisteredAttendee())->toBeTrue();
});

/**
 * ⚠️⚠️ **LE CAS QUI PORTE TOUT LE RESTE.** `pay()` exigeait
 * `hasAcceptedSubmission()` : même avec une fiche, un participant simple ne
 * pouvait rien régler. L'invariant est « il y a des frais à régler », pas
 * « il a une communication acceptée ».
 */
it('lui ouvre le paiement, au tarif de son grade', function () {
    $event = evenementAvecTarifs();
    $account = verifiedAccount();

    app(RegisterAttendee::class)->handle($event, $account, 'Doctorant');

    $participant = Participant::where('event_id', $event->id)->where('user_id', $account->id)->firstOrFail();
    expect($participant->owesParticipationFees())->toBeTrue();
    expect($participant->hasAcceptedSubmission())->toBeFalse();

    $html = $this->actingAs($account, 'account')
        ->get(route('account.payment', $event))->assertOk()->getContent();

    // Le montant de SON grade, et plus le renvoi vers « contactez l'organisation ».
    expect($html)
        ->toContain('5 000')
        ->not->toContain('Le paiement n’est pas encore ouvert')
        ->not->toContain('contactez l’organisation');
});

it('montre le formulaire d’inscription à qui n’a pas de fiche', function () {
    $event = evenementAvecTarifs();
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->get(route('account.payment', $event))
        ->assertOk()
        ->assertSee('M’inscrire à cet événement', false)
        ->assertSee('Ma catégorie')
        ->assertDontSee('contactez l’organisation', false);
});

// ----------------------------------------------------------- LA FENÊTRE -----

/**
 * ⚠️ **Les inscriptions ont leur propre fenêtre.** L'appel à communications
 * ferme des mois avant l'événement ; les adosser à `submission_open` les
 * fermerait bien trop tôt. Ici les soumissions sont CLOSES et l'inscription
 * reste possible - c'est précisément le cas d'usage.
 */
it('reste ouverte alors que les soumissions sont fermées', function () {
    $event = evenementAvecTarifs();

    expect($event->submissionsAreOpen())->toBeFalse();
    expect($event->registrationIsOpen())->toBeTrue();
});

it('refuse l’inscription quand la fenêtre est fermée', function () {
    $event = evenementAvecTarifs(['registration_open' => false]);
    $account = verifiedAccount();

    expect($event->registrationIsOpen())->toBeFalse();

    $this->actingAs($account, 'account')
        ->post(route('account.registration.store', $event), ['grade' => 'Doctorant'])
        ->assertForbidden();

    expect(Participant::where('event_id', $event->id)->exists())->toBeFalse();
});

it('refuse l’inscription après la date limite', function () {
    $event = evenementAvecTarifs(['registration_deadline' => now()->subDay()->format('Y-m-d')]);

    expect($event->registrationIsOpen())->toBeFalse();

    $this->actingAs(verifiedAccount(), 'account')
        ->post(route('account.registration.store', $event), ['grade' => 'Doctorant'])
        ->assertForbidden();
});

/** ⚠️ Ouvert par défaut : sinon la fonctionnalité naîtrait éteinte sur tous les événements déjà en base. */
it('est ouverte par défaut sur un événement qui ignore le réglage', function () {
    expect(openEdition()->registrationIsOpen())->toBeTrue();
});

it('est fermée sur un événement archivé', function () {
    $event = evenementAvecTarifs();
    $event->update(['status' => 'archived']);

    expect($event->fresh()->registrationIsOpen())->toBeFalse();
});

// ------------------------------------------------------- CE QU'ON PRÉSERVE --

/**
 * ⚠️ **Un rôle fort ne se rétrograde pas** : un évaluateur qui s'inscrit reste
 * évaluateur, et prend seulement sa date d'inscription.
 */
it('ne rétrograde pas un évaluateur qui s’inscrit', function () {
    $event = evenementAvecTarifs();
    $account = verifiedAccount();
    Participant::create([
        'event_id' => $event->id, 'user_id' => $account->id,
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email, 'role' => 'reviewer',
    ]);

    app(RegisterAttendee::class)->handle($event, $account, 'Doctorant');

    $participant = Participant::where('event_id', $event->id)->where('user_id', $account->id)->firstOrFail();
    expect($participant->role)->toBe('reviewer');
    expect($participant->isRegisteredAttendee())->toBeTrue();
});

/**
 * ⚠️⚠️ **C'EST POURQUOI L'INSCRIPTION EST UNE DATE ET NON UN RÔLE.** Un
 * participant simple qui dépose ensuite un résumé passe en `author`. Lu sur le
 * rôle, il perdrait d'un coup le droit de régler l'inscription qu'il venait de
 * prendre - et jusqu'à l'acceptation de son résumé.
 */
it('garde ses frais à régler quand il dépose ensuite une communication', function () {
    $event = evenementAvecTarifs();
    $account = verifiedAccount();

    app(RegisterAttendee::class)->handle($event, $account, 'Doctorant');
    // Le dépôt repasse la fiche en « auteur » (UpsertParticipantForAccount).
    app(UpsertParticipantForAccount::class)->handle($event, $account, 'author');

    $participant = Participant::where('event_id', $event->id)->where('user_id', $account->id)->firstOrFail();

    expect($participant->role)->toBe('author');
    expect($participant->hasAcceptedSubmission())->toBeFalse();
    expect($participant->owesParticipationFees())->toBeTrue();
});

/**
 * ⚠️ Se réinscrire ne repousse pas la date : elle dit depuis quand on est attendu.
 *
 * ⚠️⚠️ **L'HORLOGE VOYAGE ENTRE LES DEUX APPELS, ET C'EST TOUT LE CAS.**
 * `toIso8601String()` est précis à la SECONDE : enchaînés, les deux appels
 * tombent dans la même seconde, et une écriture systématique rend exactement la
 * même chaîne qu'une écriture unique. Mesuré - en remplaçant le `??=` par une
 * affectation sèche, ce cas restait VERT. Un cas qui ne mord pas ne garde rien.
 */
it('ne repousse pas la date d’inscription', function () {
    $event = evenementAvecTarifs();
    $account = verifiedAccount();

    app(RegisterAttendee::class)->handle($event, $account, 'Doctorant');
    $premiere = Participant::where('user_id', $account->id)->firstOrFail()->meta['registered_at'];

    $this->travel(2)->days();
    app(RegisterAttendee::class)->handle($event, $account, 'Enseignant');

    $participant = Participant::where('user_id', $account->id)->firstOrFail();
    expect($participant->meta['registered_at'])->toBe($premiere);
    // Le grade, lui, se corrige.
    expect($participant->title)->toBe('Enseignant');
});

it('exige la catégorie, qui détermine le tarif', function () {
    $event = evenementAvecTarifs();

    $this->actingAs(verifiedAccount(), 'account')
        ->post(route('account.registration.store', $event), ['grade' => ''])
        ->assertSessionHasErrors('grade');

    expect(Participant::where('event_id', $event->id)->exists())->toBeFalse();
});

// ---------------------------------------------------------- LA PAGE PUBLIQUE -

/**
 * ⚠️ Le bouton menait à la page de CONNEXION, qui ne rattache à aucun événement :
 * on se connectait, on atterrissait sur son tableau de bord, et l'inscription
 * n'avait pas avancé d'un pas.
 */
it('mène de la page publique à l’inscription de CET événement', function () {
    $event = evenementAvecTarifs();

    $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertSee(route('account.payment', $event), false);
});

it('ne propose pas de s’inscrire quand la fenêtre est fermée', function () {
    $event = evenementAvecTarifs(['registration_open' => false]);

    $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertDontSee(route('account.payment', $event), false);
});

/** Un compte étranger à l'événement ne doit pas pouvoir s'inscrire sans être connecté. */
it('renvoie un visiteur anonyme vers la connexion', function () {
    $event = evenementAvecTarifs();

    $this->post(route('account.registration.store', $event), ['grade' => 'Doctorant'])
        ->assertRedirect(route('account.login'));
});
