<?php

use App\Actions\NotifyAttendeePaymentOpen;
use App\Actions\RegisterAttendee;
use App\Actions\UpsertParticipantForAccount;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Participant;
use App\Models\Payment;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Notification;

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
 *
 * ⚠️ Le paiement des participants simples est **ouvert explicitement** ici :
 * s'inscrire et régler sont deux moments distincts (cf. plus bas).
 */
it('lui ouvre le paiement, au tarif de son grade', function () {
    $event = evenementAvecTarifs(['attendee_payment_open' => true]);
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

// ------------------------------------------- S'INSCRIRE N'EST PAS PAYER -----

/**
 * ⚠️⚠️ **DEUX MOMENTS DISTINCTS.** L'usage est d'ouvrir le paiement des
 * participants simples **après la clôture des dépôts** : jusque-là,
 * l'organisation ne sait pas encore combien de communicants seront retenus.
 * Sans cette seconde porte, une inscription ouvrait aussitôt un encaissement
 * que personne n'avait décidé.
 */
it('n’ouvre pas le paiement au participant simple tant que l’événement ne l’a pas décidé', function () {
    $event = evenementAvecTarifs();
    $account = verifiedAccount();

    expect($event->attendeePaymentIsOpen())->toBeFalse();

    app(RegisterAttendee::class)->handle($event, $account, 'Doctorant');
    $participant = Participant::where('user_id', $account->id)->firstOrFail();

    expect($participant->isRegisteredAttendee())->toBeTrue();
    expect($participant->owesParticipationFees())->toBeFalse();

    // ⚠️ Et la page le DIT : le message générique lui parlait d'une
    // communication à faire accepter, ce qui ne le concerne pas.
    $this->actingAs($account, 'account')
        ->get(route('account.payment', $event))
        ->assertOk()
        ->assertSee('Votre inscription est enregistrée')
        ->assertDontSee('lorsqu’au moins une de vos communications aura été acceptée', false);
});

it('ouvre le paiement dès que l’événement le décide', function () {
    $event = evenementAvecTarifs();
    $account = verifiedAccount();
    app(RegisterAttendee::class)->handle($event, $account, 'Doctorant');

    expect(Participant::where('user_id', $account->id)->firstOrFail()->owesParticipationFees())->toBeFalse();

    $event->update(['settings' => array_merge($event->settings, ['attendee_payment_open' => true])]);

    $participant = Participant::where('user_id', $account->id)->firstOrFail();
    expect($participant->owesParticipationFees())->toBeTrue();
});

/**
 * ⚠️ **Le communicant ne dépend PAS de ce réglage** : sa décision est tombée,
 * ses frais sont dus. L'y soumettre bloquerait le parcours qui marchait.
 */
it('laisse régler le communicant accepté, réglage fermé ou non', function () {
    $event = evenementAvecTarifs();
    $account = verifiedAccount();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);
    $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-900',
        'title' => 'Acceptée', 'abstract' => 'Résumé.', 'status' => 'accepted', 'revision_of' => 0,
    ]);

    expect($event->attendeePaymentIsOpen())->toBeFalse();
    expect($participant->fresh()->owesParticipationFees())->toBeTrue();
});

/** ⚠️ Fermé par défaut : ouvrir un encaissement est un geste délibéré. */
it('garde le paiement des participants simples fermé par défaut', function () {
    expect(openEdition()->attendeePaymentIsOpen())->toBeFalse();
});

// ------------------------------- ON PRÉVIENT QUAND LE PAIEMENT S'OUVRE ------

/**
 * ⚠️⚠️ **SANS CE MESSAGE, « PLUS TARD » VEUT DIRE : REVENIR TOUS LES JOURS.**
 * Le participant simple s'inscrit, lit que le règlement ouvrira « en général
 * après la clôture des dépôts », et n'a ensuite aucun moyen de savoir quand.
 */
it('prévient les inscrits quand le paiement s’ouvre', function () {
    Notification::fake();

    $event = evenementAvecTarifs();
    $inscrit = verifiedAccount();
    app(RegisterAttendee::class)->handle($event, $inscrit, 'Doctorant');

    $event->update(['settings' => array_merge($event->settings, ['attendee_payment_open' => true])]);
    $prevenus = app(NotifyAttendeePaymentOpen::class)->handle($event->fresh());

    expect($prevenus)->toBe(1);
    Notification::assertSentTo($inscrit, SystemNotification::class,
        fn (SystemNotification $n) => $n->kind === 'payment_open');
});

/** ⚠️ On ne réclame pas à qui a déjà payé. */
it('ne prévient pas un inscrit qui a déjà réglé', function () {
    Notification::fake();

    $event = evenementAvecTarifs();
    $inscrit = verifiedAccount();
    $participant = app(RegisterAttendee::class)->handle($event, $inscrit, 'Doctorant');
    Payment::create([
        'event_id' => $event->id, 'participant_id' => $participant->id,
        'amount' => 5000, 'currency' => 'XOF', 'method' => 'cash', 'gateway' => 'manual',
        'status' => 'paid',
    ]);

    expect(app(NotifyAttendeePaymentOpen::class)->handle($event))->toBe(0);
    Notification::assertNothingSent();
});

/**
 * ⚠️ **Le communicant n'a rien à y faire** : ses frais étaient dus dès
 * l'acceptation, sans attendre ce réglage. Lui annoncer une « ouverture » ne lui
 * apprendrait rien, et sèmerait le doute sur ce qu'il a déjà pu régler.
 */
it('ne prévient pas un communicant accepté', function () {
    Notification::fake();

    $event = evenementAvecTarifs();
    $account = verifiedAccount();
    $participant = app(RegisterAttendee::class)->handle($event, $account, 'Doctorant');
    $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-901',
        'title' => 'Acceptée', 'abstract' => 'Résumé.', 'status' => 'accepted', 'revision_of' => 0,
    ]);

    expect(app(NotifyAttendeePaymentOpen::class)->handle($event))->toBe(0);
    Notification::assertNothingSent();
});

/** ⚠️ Ni celui qui n'a jamais pris d'inscription (co-auteur rattaché, par exemple). */
it('ne prévient pas quelqu’un qui ne s’est pas inscrit', function () {
    Notification::fake();

    $event = evenementAvecTarifs();
    $account = verifiedAccount();
    $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => $account->email, 'role' => 'coauthor',
    ]);

    expect(app(NotifyAttendeePaymentOpen::class)->handle($event))->toBe(0);
    Notification::assertNothingSent();
});

/**
 * ⚠️⚠️ **C'EST UNE BASCULE QU'ON GUETTE, PAS UN ÉTAT.** La fiche d'événement
 * poste TOUS ses onglets à chaque enregistrement : lu sur l'état courant, le
 * message repartirait à chaque sauvegarde, et les inscrits recevraient le même
 * e-mail autant de fois que le gestionnaire corrige une virgule.
 */
it('n’envoie le message qu’à l’ouverture, pas à chaque enregistrement', function () {
    Notification::fake();

    $event = evenementAvecTarifs();
    $inscrit = verifiedAccount();
    app(RegisterAttendee::class)->handle($event, $inscrit, 'Doctorant');

    $champs = fn (bool $ouvert) => [
        'event_series_id' => $event->event_series_id,
        'title' => $event->title,
        'year' => $event->year,
        'status' => 'open',
        'max_communications' => 3,
        'attendee_payment_open' => $ouvert ? '1' : '0',
        'fees' => [['label' => 'Doctorants', 'amount' => '5000']],
    ];

    $staff = adminStaff();

    // 1er enregistrement : la bascule a lieu, le message part.
    $this->actingAs($staff, 'web')
        ->put(route('admin.evenements.update', $event), $champs(true))
        ->assertSessionHasNoErrors();

    Notification::assertSentToTimes($inscrit, SystemNotification::class, 1);

    // 2e enregistrement, réglage DÉJÀ ouvert : rien ne repart.
    $this->actingAs($staff, 'web')
        ->put(route('admin.evenements.update', $event->fresh()), $champs(true))
        ->assertSessionHasNoErrors();

    Notification::assertSentToTimes($inscrit, SystemNotification::class, 1);
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
    $event = evenementAvecTarifs(['attendee_payment_open' => true]);
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
