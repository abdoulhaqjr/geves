<?php

use App\Enums\EventPhase;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Slot;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\User;
use App\Support\ParticipantDashboard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

// openEdition(), verifiedAccount() -> tests/Pest.php

/** Compte participant vérifié, à identifiants uniques (plusieurs par cas). */
function compteParticipant(): User
{
    $n = (string) random_int(100000, 999999);
    $user = User::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => 'awa-'.$n.'@example.tg', 'password' => 'pass12345',
        'grade' => 'Professeur', 'affiliation' => 'Université de Kara',
        'phone' => '+2289'.$n.'0',
    ]);
    $user->markEmailAsVerified();

    return $user;
}

/** Co-signature CONFIRMÉE du compte sur une communication déposée par un tiers. */
function cosigne(Submission $submission, User $account, string $statut = 'linked'): SubmissionAuthor
{
    return SubmissionAuthor::create([
        'event_id' => $submission->event_id,
        'submission_id' => $submission->id,
        'user_id' => $account->id,
        'email' => $account->email,
        'name' => $account->full_name,
        'status' => $statut,
        'token' => Str::random(48),
    ]);
}

/**
 * Colloque daté, avec un participant, une communication acceptée, une séance et
 * un créneau de passage. `$decalage` déplace la FENÊTRE du colloque autour
 * d'aujourd'hui : c'est lui qui pilote la bascule du tableau de bord.
 *
 * @return array{event: Event, account: User, participant: Participant, submission: Submission, session: EventSession, slot: Slot}
 */
function colloqueDate(int $decalageJours = 0, array $attrs = []): array
{
    // ⚠️ **L'HORLOGE EST FIGÉE**, et c'est indispensable ici : tout ce qu'on
    // éprouve dépend de l'heure. Avec l'horloge réelle, le créneau de 14h00 était
    // « à venir » le matin et « passé » l'après-midi, donc la carte annonçait
    // « Mon prochain passage » ou « Mon passage » selon le moment où la suite
    // tournait - deux cas verts à 10h, rouges à 15h. Un cas éprouve le CODE,
    // jamais l'heure du poste. (Laravel remet l'horloge à zéro en `tearDown`.)
    Carbon::setTestNow(Carbon::create(2026, 6, 15, 9, 0, 0));

    $series = EventSeries::create(['name' => 'Série UK', 'slug' => 'uk-'.Str::random(5)]);
    $debut = Carbon::today()->addDays($decalageJours);

    $event = $series->events()->create(array_merge([
        'title' => 'Colloque daté',
        'slug' => 'date-'.Str::random(5),
        'year' => (int) $debut->format('Y'),
        'status' => 'open',
        'submission_open' => true,
        'date_start' => $debut,
        'date_end' => $debut->copy()->addDays(2),
        'settings' => ['tracks' => ['Sciences'], 'max_communications' => 3],
    ], $attrs));

    // ⚠️ Compte PROPRE à chaque appel : `verifiedAccount()` pose toujours le même
    // e-mail et le même téléphone, et `users.phone` porte un index UNIQUE. Deux
    // scénarios dans un même cas (pour comparer deux moments du parcours) se
    // heurtaient donc à la contrainte.
    $account = compteParticipant();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => $account->email, 'role' => 'author',
    ]);
    $submission = $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => 'AVD-001',
        'title' => 'Approches interdisciplinaires', 'status' => 'accepted',
        'revision_of' => 0, 'track' => 'Sciences', 'type' => 'oral',
        'access_token' => Str::random(64),
    ]);
    $session = $event->sessions()->create([
        'type' => 'atelier', 'label' => 'Atelier 1 - séance 1', 'room' => 'Salle 1',
        'session_day' => $debut, 'ordre' => 1,
        'starts_at' => $debut->copy()->setTime(14, 0),
        'ends_at' => $debut->copy()->setTime(17, 0),
    ]);
    $slot = Slot::create([
        'event_id' => $event->id, 'submission_id' => $submission->id,
        'participant_id' => $participant->id, 'session_id' => $session->id,
        'room' => 'Salle 1',
        'starts_at' => $debut->copy()->setTime(14, 0),
        'ends_at' => $debut->copy()->setTime(14, 30),
    ]);

    return compact('event', 'account', 'participant', 'submission', 'session', 'slot');
}

// =====================================================================
// La bascule elle-même : une seule règle, dérivée de Event::phase()
// =====================================================================

it('range chaque phase d’événement dans un seul moment du parcours', function () {
    // ⚠️ Règle PURE : la bascule du tableau de bord n'a qu'une définition, et elle
    // part de `Event::phase()` - celle dont le site public se sert déjà
    // (`publicView()`). Deux comparaisons de dates finiraient par se contredire
    // sur le même écran.
    expect(EventPhase::Draft->participantView())->toBe('avant');
    expect(EventPhase::CallOpen->participantView())->toBe('avant');
    expect(EventPhase::UnderReview->participantView())->toBe('avant');
    expect(EventPhase::Registration->participantView())->toBe('avant');
    expect(EventPhase::Ongoing->participantView())->toBe('pendant');
    expect(EventPhase::Closed->participantView())->toBe('apres');

    // Aucune phase ne doit rester sans moment : la liste ci-dessus les couvre
    // toutes, donc un `case` ajouté à l'enum fait échouer ce cas.
    foreach (EventPhase::cases() as $phase) {
        expect($phase->participantView())->toBeIn(['avant', 'pendant', 'apres']);
    }
});

it('bascule le tableau de bord sur les dates de l’événement', function () {
    // AVANT : le colloque commence dans dix jours.
    ['account' => $avant, 'event' => $e1] = colloqueDate(10);
    expect($e1->phase())->toBe(EventPhase::CallOpen);
    expect((new ParticipantDashboard($avant, $e1))->vue())->toBe('avant');

    $this->actingAs($avant, 'account')->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Ma prochaine étape')
        ->assertSee('Mon parcours')            // le déroulé en entier renseigne ici
        ->assertDontSee('Mon prochain passage');
});

it('bascule sur « pendant » quand le colloque se déroule', function () {
    // PENDANT : commencé aujourd'hui, il dure trois jours.
    ['account' => $account, 'event' => $event] = colloqueDate(0);
    expect($event->phase())->toBe(EventPhase::Ongoing);

    $this->actingAs($account, 'account')->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Mon prochain passage')
        ->assertSee('Mon programme')
        // ⚠️ Le déroulé en six étapes n'annoncerait ici qu'un travail déjà fait.
        ->assertDontSee('Mon parcours');
});

it('bascule sur « apres » quand les dates sont dépassées', function () {
    // APRÈS : terminé depuis une semaine.
    ['account' => $account, 'event' => $event] = colloqueDate(-10);
    expect($event->phase())->toBe(EventPhase::Closed);

    $this->actingAs($account, 'account')->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Ce qu’il vous reste à retirer')
        ->assertDontSee('Ma prochaine étape')
        ->assertDontSee('Mon prochain passage');
});

it('suit l’archivage explicite, pas seulement l’horloge', function () {
    // ⚠️ La bascule part de `Event::phase()`, qui tient compte de l'ARCHIVAGE : un
    // colloque dont les dates sont encore à venir mais que l'organisation a
    // archivé est un colloque terminé pour le participant. Comparer des dates à
    // la main, ici, aurait manqué ce cas.
    ['account' => $account, 'event' => $event] = colloqueDate(10);
    $event->update(['status' => 'archived']);

    expect($event->fresh()->phase())->toBe(EventPhase::Closed);
    expect((new ParticipantDashboard($account, $event->fresh()))->vue())->toBe('apres');
});

it('compte les jours du colloque pendant son déroulement seulement', function () {
    ['account' => $a1, 'event' => $e1] = colloqueDate(0);
    expect((new ParticipantDashboard($a1, $e1))->jour())->toBe(['n' => 1, 'total' => 3]);

    ['account' => $a2, 'event' => $e2] = colloqueDate(-1);
    expect((new ParticipantDashboard($a2, $e2))->jour())->toBe(['n' => 2, 'total' => 3]);

    ['account' => $a3, 'event' => $e3] = colloqueDate(10);
    expect((new ParticipantDashboard($a3, $e3))->jour())->toBeNull();
});

// =====================================================================
// Le prochain passage : la donnée que l'écran possédait sans la dire
// =====================================================================

it('annonce l’heure, la salle et la séance du prochain passage', function () {
    // ⚠️ Le contrôleur calculait déjà ces créneaux pour n'en garder qu'un booléen
    // (`$hasAgenda`), qui ne servait qu'à afficher un bouton « Télécharger
    // (.ics) » : l'écran savait quand et où le participant présente, et ne le
    // disait pas. C'est le manque qui a motivé toute la refonte.
    ['account' => $account, 'slot' => $slot] = colloqueDate(0);

    $this->actingAs($account, 'account')->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Mon prochain passage')
        ->assertSee($slot->starts_at->format('H\hi'))
        ->assertSee('Salle 1')
        ->assertSee('Atelier 1 - séance 1')
        ->assertSee('AVD-001');
});

it('ne retient pas le passage d’une communication seulement co-signée', function () {
    // ⚠️ Un co-signataire ne monte pas forcément à la tribune : lui annoncer
    // « vous présentez à 14h00 » quand c'est son collègue qui présente serait
    // pire que de ne rien dire. Sa co-signature reste visible dans « Mes
    // communications », avec son rôle.
    ['event' => $event, 'account' => $account] = colloqueDate(0);
    $autre = $event->participants()->create([
        'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@uk.tg', 'role' => 'author',
    ]);
    $sienne = $event->submissions()->create([
        'participant_id' => $autre->id, 'ref_code' => 'AVD-118', 'title' => 'Travail du collègue',
        'status' => 'accepted', 'revision_of' => 0, 'access_token' => Str::random(64),
    ]);
    cosigne($sienne, $account);
    Slot::create([
        'event_id' => $event->id, 'submission_id' => $sienne->id,
        'participant_id' => $autre->id, 'room' => 'Salle 9',
        'starts_at' => Carbon::today()->setTime(9, 0),
        'ends_at' => Carbon::today()->setTime(9, 30),
    ]);

    $tdb = new ParticipantDashboard($account, $event);

    expect($tdb->passages()->pluck('submission_id')->all())->not->toContain($sienne->id);
    expect($tdb->prochainPassage()?->submission_id)->not->toBe($sienne->id);
});

// =====================================================================
// Les co-signatures comptent
// =====================================================================

it('compte les communications co-signées comme la page qui les détaille', function () {
    // ⚠️ La carte et l'indicateur comptaient les communications DÉPOSÉES
    // (`participant_id`) quand `/compte/communications` y ajoute les
    // co-signatures : mesuré sur la base de démonstration, le tableau de bord
    // annonçait « 1 » là où la page d'à côté en listait trois.
    ['event' => $event, 'account' => $account] = colloqueDate(0);
    $autre = $event->participants()->create([
        'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@uk.tg', 'role' => 'author',
    ]);
    $cosignee = $event->submissions()->create([
        'participant_id' => $autre->id, 'ref_code' => 'AVD-118', 'title' => 'Co-signée',
        'status' => 'under_review', 'revision_of' => 0, 'access_token' => Str::random(64),
    ]);
    cosigne($cosignee, $account);

    $tdb = new ParticipantDashboard($account, $event);
    expect($tdb->communications())->toHaveCount(2);
    expect($tdb->estCorrespondant($cosignee))->toBeFalse();

    $indicateur = collect($tdb->indicateurs())->firstWhere('libelle', 'Mes communications');
    expect($indicateur['valeur'])->toBe('2');
    expect($indicateur['detail'])->toContain('co-signée');

    $this->actingAs($account, 'account')->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('AVD-118')
        ->assertSee('Co-auteur');
});

it('écarte une liaison d’auteur déclinée', function () {
    // Même règle que `/compte/communications` : seules les liaisons CONFIRMÉES
    // (`linked`) comptent. Une invitation déclinée n'est pas une co-signature.
    ['event' => $event, 'account' => $account] = colloqueDate(0);
    $autre = $event->participants()->create([
        'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@uk.tg', 'role' => 'author',
    ]);
    $refusee = $event->submissions()->create([
        'participant_id' => $autre->id, 'ref_code' => 'AVD-900', 'title' => 'Déclinée',
        'status' => 'accepted', 'revision_of' => 0, 'access_token' => Str::random(64),
    ]);
    cosigne($refusee, $account, 'declined');

    expect((new ParticipantDashboard($account, $event))->communications())->toHaveCount(1);
});

// =====================================================================
// Les indicateurs portent des CHIFFRES
// =====================================================================

it('ne met plus de coche dans la rangée d’indicateurs', function () {
    // ⚠️ Deux des quatre cartes affichaient « ✓ » (profil complet, paiement) ou
    // des points de suspension. Une TÂCHE n'est pas une grandeur : elle ne se
    // compare à rien, et elle n'était pas cliquable. Elle est passée dans
    // « À faire ».
    //
    // ⚠️ La garde porte sur la page RENDUE, pas sur le fichier de la vue : le
    // commentaire qui explique le défaut contient justement le caractère qu'on
    // interdit, et un scan du source échouerait sur sa propre documentation.
    ['account' => $account, 'event' => $event] = colloqueDate(0);

    $html = $this->actingAs($account, 'account')
        ->get(route('account.dashboard'))->assertOk()->getContent();

    expect($html)->not->toContain('✓');

    // Et chaque indicateur rend une valeur NON VIDE (un libellé seul ne dit rien).
    foreach ((new ParticipantDashboard($account, $event))->indicateurs() as $i) {
        expect(trim($i['valeur']))->not->toBe('');
        expect(trim($i['libelle']))->not->toBe('');
    }
});

it('donne quatre indicateurs, adaptés au moment du parcours', function () {
    ['account' => $a1, 'event' => $e1] = colloqueDate(10);
    $avant = collect((new ParticipantDashboard($a1, $e1))->indicateurs());
    expect($avant)->toHaveCount(4);
    expect($avant->pluck('libelle'))->toContain('Avant la clôture');

    ['account' => $a2, 'event' => $e2] = colloqueDate(0);
    $pendant = collect((new ParticipantDashboard($a2, $e2))->indicateurs());
    expect($pendant)->toHaveCount(4);
    expect($pendant->pluck('libelle'))->toContain('Acceptées');
    expect($pendant->pluck('libelle'))->not->toContain('Avant la clôture');
});

// =====================================================================
// Agenda personnel & à faire
// =====================================================================

it('bâtit un agenda personnel groupé par jour, ouvert sur le bon jour', function () {
    ['account' => $account, 'event' => $event] = colloqueDate(0);
    $tdb = new ParticipantDashboard($account, $event);

    $agenda = $tdb->agenda();
    expect($agenda)->not->toBeEmpty();
    expect(array_key_first($agenda))->toBe(Carbon::today()->format('Y-m-d'));
    expect($tdb->jourOuvert())->toBe(Carbon::today()->format('Y-m-d'));

    // Le passage y figure, marqué comme tel.
    $genres = collect($agenda)->flatten(1)->pluck('genre');
    expect($genres)->toContain('passage');
});

it('ne détaille dans « À faire » que ce qui reste à faire', function () {
    // ⚠️ Le déroulé en six étapes restait affiché en entier, coches comprises,
    // jusqu'à la fin du colloque. Ici on ne liste que ce qui attend ; la
    // progression globale tient dans une barre.
    ['account' => $account, 'event' => $event] = colloqueDate(0);
    $tdb = new ParticipantDashboard($account, $event);

    // Profil complet + e-mail vérifié + aucun frais dû (aucun paiement attendu
    // tant que rien n'est à régler) : il ne reste rien.
    $titres = collect($tdb->aFaire())->pluck('titre');
    expect($titres)->not->toContain('Confirmer mon adresse e-mail');

    // Un e-mail non vérifié fait apparaître UNE tâche, cliquable.
    $account->forceFill(['email_verified_at' => null])->save();
    $tache = collect((new ParticipantDashboard($account->fresh(), $event))->aFaire())
        ->firstWhere('titre', 'Confirmer mon adresse e-mail');

    expect($tache)->not->toBeNull();
    expect($tache['url'])->toBe(route('account.verification.notice'));
});

it('garde ses fonctions à qui n’est inscrit à aucun colloque', function () {
    // ⚠️ Un modérateur est rapproché par E-MAIL : il peut n'avoir aucun
    // `Participant`, donc aucun colloque sélectionnable. L'état vide le renvoyait
    // sur « votre espace se remplira bientôt » en lui cachant les séances qu'on
    // lui a confiées - défaut introduit par la refonte, attrapé par le cas des
    // rôles dérivés et figé ici.
    $series = EventSeries::create(['name' => 'Série UK', 'slug' => 'uk-'.Str::random(5)]);
    $event = $series->events()->create([
        'title' => 'Sans inscription', 'slug' => 'sans-'.Str::random(5), 'status' => 'open',
    ]);
    $mod = User::create([
        'first_name' => 'Pro', 'last_name' => 'Fesseur',
        'email' => 'mod-sans-inscription@uk.tg', 'password' => 'pass12345',
    ]);
    $mod->markEmailAsVerified();
    $event->sessions()->create([
        'type' => 'plenary', 'label' => 'Plénière', 'ordre' => 1,
        'moderator_email' => 'mod-sans-inscription@uk.tg', 'moderator_name' => 'Pro Fesseur',
    ]);

    expect(Participant::where('user_id', $mod->id)->exists())->toBeFalse();

    $this->actingAs($mod, 'account')->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Ce que le colloque vous a confié')
        ->assertSee(route('account.moderation'), false)
        ->assertDontSee('Votre espace se remplira bientôt');
});
