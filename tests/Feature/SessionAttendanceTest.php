<?php

use App\Meetings\LiveKitRoom;
use App\Meetings\MeetableRegistry;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\SessionAttendance;
use App\Models\SessionReport;
use App\Models\Slot;
use App\Models\Submission;
use App\Models\User;
use App\Support\SessionPresence;
use Illuminate\Support\Str;

// openEdition() -> tests/Pest.php

/**
 * Présence d'une séance, tenue pendant la séance par son modérateur ou son
 * rapporteur. Le scan de badge a été retiré : c'est désormais la seule source de
 * présence réelle, et elle conditionne l'attestation de participation.
 *
 * ⚠️ On ne pointe que les AUTEURS des communications de la séance : un modérateur
 * constate ce qu'il a sous les yeux, pas l'effectif de l'événement.
 */

/** Compte participant vérifié portant un e-mail donné. */
function officier(string $email): User
{
    $user = User::create([
        'first_name' => 'Test', 'last_name' => 'Officier',
        'email' => $email, 'password' => 'pass12345',
    ]);
    $user->markEmailAsVerified();

    return $user;
}

/**
 * Une séance commencée, avec UNE communication programmée : Awa (auteur
 * correspondant) et Kofi (co-auteur inscrit).
 *
 * @return array{0: Event, 1: EventSession, 2: Participant, 3: Participant}
 */
function seanceAPointer(string $moderateur = '', string $rapporteur = ''): array
{
    $event = openEdition();
    $session = $event->sessions()->create([
        'label' => 'Séance - Atelier 3', 'type' => 'atelier', 'room' => 'Salle 3',
        'starts_at' => now()->subHour(),
        'moderator_email' => $moderateur ?: null,
        'rapporteur_email' => $rapporteur ?: null,
    ]);

    $awa = Participant::create([
        'event_id' => $event->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => 'awa@example.tg', 'role' => 'author', 'institution' => 'Université de Kara',
    ]);
    $kofi = Participant::create([
        'event_id' => $event->id, 'first_name' => 'Kofi', 'last_name' => 'Mensah',
        'email' => 'kofi@example.tg', 'role' => 'attendee',
    ]);

    $submission = Submission::create([
        'event_id' => $event->id, 'participant_id' => $awa->id,
        'ref_code' => 'UK-TEST-001', 'access_token' => Str::random(40),
        'title' => 'Les universités africaines face au climat',
        'authors' => [
            ['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'corresponding' => true],
            ['name' => 'Kofi Mensah', 'email' => 'kofi@example.tg', 'corresponding' => false],
        ],
        'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted', 'revision_of' => 0,
    ]);

    Slot::create([
        'event_id' => $event->id, 'session_id' => $session->id,
        'submission_id' => $submission->id, 'participant_id' => $awa->id,
        'starts_at' => $session->starts_at, 'ends_at' => $session->starts_at->copy()->addMinutes(30),
    ]);

    return [$event, $session, $awa, $kofi];
}

it('protège le pointage derrière le guard account', function () {
    [, $session] = seanceAPointer('mod@example.tg');

    $this->get(route('account.attendance.edit', $session))->assertRedirect(route('account.login'));
});

it('n’ouvre le pointage ni à un tiers ni à un simple inscrit', function () {
    [$event, $session] = seanceAPointer('mod@example.tg');
    $intrus = officier('intrus@example.tg');
    Participant::create([
        'event_id' => $event->id, 'first_name' => 'In', 'last_name' => 'Trus',
        'email' => 'intrus@example.tg', 'role' => 'attendee', 'user_id' => $intrus->id,
    ]);

    $this->actingAs($intrus, 'account')
        ->get(route('account.attendance.edit', $session))
        ->assertForbidden();

    $this->actingAs($intrus, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => []])
        ->assertForbidden();
});

/**
 * ⚠️ Le cadrage demandé : la liste est celle des auteurs de la séance. Afficher
 * les centaines d'inscrits de l'événement demandait au modérateur de se prononcer
 * sur des gens qui n'ont rien à faire dans sa salle.
 */
it('ne liste que les auteurs des communications de la séance', function () {
    [$event, $session, , $kofi] = seanceAPointer('mod@example.tg');
    $mod = officier('mod@example.tg');

    // Un inscrit de l'événement, étranger à cette séance.
    Participant::create([
        'event_id' => $event->id, 'first_name' => 'Sans', 'last_name' => 'Rapport',
        'email' => 'ailleurs@example.tg', 'role' => 'attendee',
    ]);

    $this->actingAs($mod, 'account')
        ->get(route('account.attendance.edit', $session))
        ->assertOk()
        ->assertSee('Awa KODJO')
        ->assertSee('Kofi MENSAH')
        ->assertSee('Les universités africaines face au climat')
        ->assertSee('Auteur correspondant')
        ->assertDontSee('Sans Rapport');

    expect($session->presenceCounts())->toMatchArray(['expected' => 2, 'unmarked' => 2]);
    expect($kofi->exists)->toBeTrue();
});

it('laisse le modérateur pointer la présence de sa séance', function () {
    [, $session, $awa, $kofi] = seanceAPointer('mod@example.tg');
    $mod = officier('mod@example.tg');

    $this->actingAs($mod, 'account')
        ->post(route('account.attendance.update', $session), [
            'presence' => [$awa->id => 'present', $kofi->id => 'absent'],
        ])
        ->assertRedirect(route('account.attendance.edit', $session));

    expect(SessionAttendance::where('session_id', $session->id)->count())->toBe(2);
    expect(SessionAttendance::where('participant_id', $awa->id)->value('status'))->toBe('present');
    expect(SessionAttendance::where('participant_id', $awa->id)->value('marked_by'))->toBe($mod->id);
    expect($session->presenceCounts())->toMatchArray([
        'present' => 1, 'absent' => 1, 'unmarked' => 0, 'expected' => 2,
    ]);
});

it('laisse aussi le rapporteur pointer : c’est souvent lui qui tient la liste', function () {
    [, $session, $awa] = seanceAPointer(rapporteur: 'rap@example.tg');
    $rap = officier('rap@example.tg');

    $this->actingAs($rap, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => [$awa->id => 'absent']])
        ->assertRedirect();

    expect(SessionAttendance::where('participant_id', $awa->id)->value('status'))->toBe('absent');
});

/**
 * ⚠️ « Excusé » a été retiré : il ne pesait rien en aval (seul `present` compte
 * dans le PV et dans l'attestation) et ajoutait un choix à trancher pendant
 * qu'on appelle des gens à la voix. Ce cas gèle le refus des DEUX côtés - la
 * page et la salle valident sur la même liste, et y remettre une valeur
 * l'ouvrirait partout.
 */
it('ne connaît plus que présent et absent, page et salle', function () {
    [, $session, $awa] = seanceAPointer('modbin@example.tg');
    $session->update(['meeting_status' => 'live']);
    $mod = officier('modbin@example.tg');

    expect(array_keys(SessionAttendance::STATUSES))->toBe(['present', 'absent']);

    $this->actingAs($mod, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => [$awa->id => 'excused']])
        ->assertSessionHasErrors('presence.'.$awa->id);

    $this->actingAs($mod, 'account')
        ->postJson(route('session.presence.mark', $session), ['participant_id' => $awa->id, 'status' => 'excused'])
        ->assertStatus(422);

    expect(SessionAttendance::where('participant_id', $awa->id)->exists())->toBeFalse();
});

/**
 * ⚠️ On vient de pointer des gens pendant qu'on anime : repasser par la liste
 * des séances pour retourner dans la salle est un détour que personne ne fait.
 * Le `target` nommé vaut autant que le lien - sans lui, le retour ouvrirait une
 * SECONDE salle à côté de celle qui tourne déjà (deux connexions, de l'écho).
 */
it('ramène dans la visio depuis la page de présence, dans l’onglet de la salle', function () {
    [, $session] = seanceAPointer('modretour@example.tg');
    $mod = officier('modretour@example.tg');

    // Sans visio, rien à proposer : le bouton n'existe pas.
    $this->actingAs($mod, 'account')
        ->get(route('account.attendance.edit', $session))
        ->assertOk()
        ->assertDontSee('retourVisio');

    $session->update(['meeting_status' => 'live']);

    $this->actingAs($mod, 'account')
        ->get(route('account.attendance.edit', $session))
        ->assertOk()
        ->assertSee('Retour dans la visio')
        ->assertSee(route('session.meeting', $session))
        ->assertSee('target="'.MeetableRegistry::tabFor($session).'"', false);
});

/**
 * ⚠️ Le cœur du pointage : il se fait par petits lots pendant la séance. Une
 * sauvegarde qui remettrait à zéro ce qui n'est pas à l'écran effacerait le lot
 * précédent.
 */
it('n’efface pas les verdicts absents du formulaire', function () {
    [, $session, $awa, $kofi] = seanceAPointer('mod@example.tg');
    $mod = officier('mod@example.tg');

    $this->actingAs($mod, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => [$awa->id => 'present']]);
    $this->actingAs($mod, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => [$kofi->id => 'present']]);

    expect(SessionAttendance::where('session_id', $session->id)->where('status', 'present')->count())->toBe(2);
});

it('revient à « non pointé » sur un verdict vide', function () {
    [, $session, $awa] = seanceAPointer('mod@example.tg');
    $mod = officier('mod@example.tg');

    $this->actingAs($mod, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => [$awa->id => 'present']]);
    expect(SessionAttendance::where('participant_id', $awa->id)->exists())->toBeTrue();

    // Une erreur de pointage doit pouvoir se défaire : pas de ligne « vide » qui
    // compterait quand même comme un pointage.
    $this->actingAs($mod, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => [$awa->id => '']]);
    expect(SessionAttendance::where('participant_id', $awa->id)->exists())->toBeFalse();
});

/** ⚠️ Un identifiant posté n'est pas une autorisation : la garde est la SÉANCE. */
it('refuse un inscrit de l’événement étranger à la séance', function () {
    [$event, $session] = seanceAPointer('mod@example.tg');
    $mod = officier('mod@example.tg');
    $etranger = Participant::create([
        'event_id' => $event->id, 'first_name' => 'Sans', 'last_name' => 'Rapport',
        'email' => 'ailleurs@example.tg', 'role' => 'attendee',
    ]);

    $this->actingAs($mod, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => [$etranger->id => 'present']]);

    expect(SessionAttendance::where('participant_id', $etranger->id)->exists())->toBeFalse();
});

/**
 * Un co-auteur qui n'est pas inscrit sur l'événement reste VISIBLE - sinon la liste
 * des auteurs paraîtrait tronquée - mais il n'y a aucune inscription à laquelle
 * rattacher une présence.
 */
it('montre un co-auteur non inscrit sans permettre de le pointer', function () {
    [, $session] = seanceAPointer('modc@example.tg');
    $mod = officier('modc@example.tg');

    $submission = Submission::where('ref_code', 'UK-TEST-001')->firstOrFail();
    $auteurs = $submission->authors;
    $auteurs[] = ['name' => 'Fatou Diallo', 'email' => 'fatou@ailleurs.tg', 'corresponding' => false];
    $submission->update(['authors' => $auteurs]);

    $this->actingAs($mod, 'account')
        ->get(route('account.attendance.edit', $session))
        ->assertOk()
        ->assertSee('Fatou Diallo')
        ->assertSee('Non inscrit');

    // Elle ne compte pas dans l'effectif attendu : on ne peut pas la pointer.
    expect($session->presenceCounts()['expected'])->toBe(2);
});

it('le dit quand la séance n’a aucune communication programmée', function () {
    $event = openEdition();
    $session = $event->sessions()->create([
        'label' => 'Séance plénière', 'type' => 'plenary',
        'starts_at' => now()->subHour(), 'moderator_email' => 'modp@example.tg',
    ]);
    Participant::create([
        'event_id' => $event->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => 'awa@example.tg', 'role' => 'author',
    ]);

    $this->actingAs(officier('modp@example.tg'), 'account')
        ->get(route('account.attendance.edit', $session))
        ->assertOk()
        ->assertSee('Aucune communication programmée')
        ->assertDontSee('Awa KODJO');
});

it('n’ouvre pas le pointage avant le début de la séance', function () {
    [, $session] = seanceAPointer('modf@example.tg');
    $session->update(['starts_at' => now()->addDay()]);
    $mod = officier('modf@example.tg');

    $this->actingAs($mod, 'account')
        ->get(route('account.attendance.edit', $session))
        ->assertOk()
        ->assertSee('Pointage pas encore ouvert');

    $this->actingAs($mod, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => []])
        ->assertForbidden();
});

it('fige la présence quand le secrétariat a validé le rapport', function () {
    [$event, $session, $awa] = seanceAPointer('modv@example.tg');
    $mod = officier('modv@example.tg');
    SessionReport::create([
        'session_id' => $session->id, 'event_id' => $event->id,
        'summary' => 'Rapport validé.', 'status' => 'validated',
        'submitted_at' => now()->subHour(), 'validated_at' => now(),
    ]);

    // Consultable - le PV cite ces chiffres, on doit pouvoir les relire.
    $this->actingAs($mod, 'account')
        ->get(route('account.attendance.edit', $session))
        ->assertOk()
        ->assertSee('présence est figée');

    $this->actingAs($mod, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => [$awa->id => 'present']])
        ->assertForbidden();
});

it('fige la présence d’un événement archivé', function () {
    [$event, $session, $awa] = seanceAPointer('moda@example.tg');
    $mod = officier('moda@example.tg');
    $event->update(['status' => 'archived']);

    $this->actingAs($mod, 'account')
        ->get(route('account.attendance.edit', $session))->assertOk();

    $this->actingAs($mod, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => [$awa->id => 'present']])
        ->assertForbidden();
});

/**
 * ⚠️ La présence en séance est la SEULE source de présence réelle depuis le
 * retrait du scan de badge : sans ce raccordement, l'option « présence requise »
 * des attestations ne laissait passer personne.
 */
it('vaut présence réelle pour l’attestation de participation', function () {
    [, $session, $awa, $kofi] = seanceAPointer('modp2@example.tg');

    expect($awa->wasPresent())->toBeFalse();

    SessionAttendance::create([
        'session_id' => $session->id, 'participant_id' => $awa->id, 'status' => 'present',
    ]);
    SessionAttendance::create([
        'session_id' => $session->id, 'participant_id' => $kofi->id, 'status' => 'absent',
    ]);

    expect($awa->fresh()->wasPresent())->toBeTrue();
    // Un absent constaté n'est pas un présent : le verdict compte, pas la ligne.
    expect($kofi->fresh()->wasPresent())->toBeFalse();
});

/**
 * ⚠️ Entrer dans la visio VAUT présence : le modérateur n'a pas à pointer à la
 * main quelqu'un qui est déjà dans la salle.
 */
it('porte présent automatiquement qui entre dans la visio', function () {
    [$event, $session, $awa] = seanceAPointer('modvisio@example.tg');
    // Pas de fournisseur imposé : le registre résout celui qui est configuré.
    $session->update(['meeting_status' => 'live']);

    // Le compte d'Awa, auteur correspondant de la communication de la séance.
    $compte = officier('awa@example.tg');
    $awa->update(['user_id' => $compte->id]);

    $this->actingAs($compte, 'account')->get(route('session.meeting', $session))->assertOk();

    $ligne = SessionAttendance::where('session_id', $session->id)
        ->where('participant_id', $awa->id)->first();

    expect($ligne)->not->toBeNull();
    expect($ligne->status)->toBe('present');
    expect($ligne->isAutomatic())->toBeTrue();
    expect($event->fresh()->exists)->toBeTrue();
});

/** Le pointage automatique se voit : le modérateur endosse ces chiffres. */
it('signale sur la page ce que la visio a pointé', function () {
    [, $session, $awa] = seanceAPointer('modvu@example.tg');
    $session->update(['meeting_status' => 'live']);
    SessionAttendance::create([
        'session_id' => $session->id, 'participant_id' => $awa->id,
        'status' => 'present', 'source' => SessionAttendance::SOURCE_MEETING,
    ]);

    $this->actingAs(officier('modvu@example.tg'), 'account')
        ->get(route('account.attendance.edit', $session))
        ->assertOk()
        ->assertSee('Entré en visio');
});

/**
 * ⚠️ Un verdict humain n'est jamais écrasé par un clic sur « Rejoindre » :
 * quelqu'un a regardé la salle, et le modérateur ne doit pas voir son constat
 * changer dans son dos.
 */
it('n’écrase pas un verdict déjà posé par le modérateur', function () {
    [, $session, $awa] = seanceAPointer('modw@example.tg');
    $session->update(['meeting_status' => 'live']);
    $compte = officier('awa@example.tg');
    $awa->update(['user_id' => $compte->id]);

    SessionAttendance::create([
        'session_id' => $session->id, 'participant_id' => $awa->id,
        'status' => 'absent', 'source' => SessionAttendance::SOURCE_MANUAL,
    ]);

    $this->actingAs($compte, 'account')->get(route('session.meeting', $session));

    expect(SessionAttendance::where('participant_id', $awa->id)->value('status'))->toBe('absent');
});

/** La visio ne pointe que des attendus : un visiteur de passage n'a pas de ligne. */
it('ne porte pas présent un inscrit étranger à la séance', function () {
    [$event, $session] = seanceAPointer('modx@example.tg');
    $session->update(['meeting_status' => 'live']);

    $compte = officier('curieux@example.tg');
    $curieux = Participant::create([
        'event_id' => $event->id, 'first_name' => 'Cu', 'last_name' => 'Rieux',
        'email' => 'curieux@example.tg', 'role' => 'attendee', 'user_id' => $compte->id,
    ]);

    $this->actingAs($compte, 'account')->get(route('session.meeting', $session))->assertOk();

    expect(SessionAttendance::where('participant_id', $curieux->id)->exists())->toBeFalse();
});

/** Une séance figée ne bouge plus, même par la visio. */
it('ne pointe pas par la visio quand le rapport est validé', function () {
    [$event, $session, $awa] = seanceAPointer('mody@example.tg');
    $session->update(['meeting_status' => 'live']);
    $compte = officier('awa@example.tg');
    $awa->update(['user_id' => $compte->id]);
    SessionReport::create([
        'session_id' => $session->id, 'event_id' => $event->id,
        'summary' => 'Rapport validé.', 'status' => 'validated',
        'submitted_at' => now()->subHour(), 'validated_at' => now(),
    ]);

    $this->actingAs($compte, 'account')->get(route('session.meeting', $session));

    expect(SessionAttendance::where('participant_id', $awa->id)->exists())->toBeFalse();
});

/**
 * ⚠️ Pointer DEPUIS la salle : l'aller-retour vers la page de présence obligeait
 * à quitter la séance au moment précis où l'on regarde les gens.
 */
it('sert la liste de présence à la salle de visio', function () {
    [, $session, $awa, $kofi] = seanceAPointer('modapi@example.tg');
    $session->update(['meeting_status' => 'live']);

    $reponse = $this->actingAs(officier('modapi@example.tg'), 'account')
        ->getJson(route('session.presence.list', $session))
        ->assertOk()
        ->assertJsonPath('canWrite', true)
        ->assertJsonPath('counts.expected', 2);

    $noms = collect($reponse->json('rows'))->pluck('name')->all();
    expect($noms)->toContain('Awa KODJO')->toContain('Kofi MENSAH');
    expect($kofi->exists)->toBeTrue();
    expect($awa->exists)->toBeTrue();
});

it('pointe depuis la salle, et retire sur un verdict vide', function () {
    [, $session, $awa] = seanceAPointer('modapi2@example.tg');
    $session->update(['meeting_status' => 'live']);
    $mod = officier('modapi2@example.tg');

    $this->actingAs($mod, 'account')
        ->postJson(route('session.presence.mark', $session), ['participant_id' => $awa->id, 'status' => 'present'])
        ->assertOk()
        ->assertJsonPath('counts.present', 1);

    expect(SessionAttendance::where('participant_id', $awa->id)->value('source'))
        ->toBe(SessionAttendance::SOURCE_MANUAL);

    $this->actingAs($mod, 'account')
        ->postJson(route('session.presence.mark', $session), ['participant_id' => $awa->id, 'status' => null])
        ->assertOk();

    expect(SessionAttendance::where('participant_id', $awa->id)->exists())->toBeFalse();
});

/**
 * ⚠️ Le modérateur ENDOSSE le chiffre dans le PV : un pointage que la visio a
 * déduit doit pouvoir passer de sa main sans changer de verdict. C'est ce que
 * fait son clic sur « Présent » d'une ligne automatique - et la salle le lui
 * montre en demi-teinte tant qu'il ne l'a pas fait.
 */
it('confirme un pointage de la visio sans changer le verdict', function () {
    [, $session, $awa] = seanceAPointer('modconf@example.tg');
    $session->update(['meeting_status' => 'live']);
    $mod = officier('modconf@example.tg');

    SessionAttendance::create([
        'session_id' => $session->id, 'participant_id' => $awa->id,
        'status' => 'present', 'source' => SessionAttendance::SOURCE_MEETING,
    ]);

    // La salle annonce la ligne comme automatique : c'est ce qui déclenche la
    // demi-teinte et le « à confirmer » du panneau.
    $this->actingAs($mod, 'account')
        ->getJson(route('session.presence.list', $session))
        ->assertOk()
        ->assertJsonPath('rows.0.auto', true)
        ->assertJsonPath('rows.0.status', 'present');

    $this->actingAs($mod, 'account')
        ->postJson(route('session.presence.mark', $session), ['participant_id' => $awa->id, 'status' => 'present'])
        ->assertOk()
        ->assertJsonPath('counts.present', 1);

    $ligne = SessionAttendance::where('participant_id', $awa->id)->first();
    expect($ligne->status)->toBe('present');
    expect($ligne->source)->toBe(SessionAttendance::SOURCE_MANUAL);
    expect($ligne->marked_by)->toBe($mod->id);
});

it('n’ouvre le panneau de la salle ni à un tiers ni sur un étranger à la séance', function () {
    [$event, $session, $awa] = seanceAPointer('modapi3@example.tg');
    $session->update(['meeting_status' => 'live']);
    $etranger = Participant::create([
        'event_id' => $event->id, 'first_name' => 'Sans', 'last_name' => 'Rapport',
        'email' => 'ailleurs@example.tg', 'role' => 'attendee',
    ]);

    // Un compte qui n'anime ni ne rapporte la séance.
    $this->actingAs(officier('tiers@example.tg'), 'account')
        ->getJson(route('session.presence.list', $session))->assertForbidden();

    // Et le modérateur ne peut pas se prononcer sur quelqu'un hors de sa séance.
    $this->actingAs(officier('modapi3@example.tg'), 'account')
        ->postJson(route('session.presence.mark', $session), ['participant_id' => $etranger->id, 'status' => 'present'])
        ->assertStatus(422);

    expect(SessionAttendance::where('participant_id', $awa->id)->exists())->toBeFalse();
});

/**
 * ⚠️ La version forte du pointage automatique : une connexion EN COURS à la
 * salle, et non un simple passage par la porte. Un verdict déjà posé tient.
 */
it('porte présents les connectés de la salle, sans écraser un verdict', function () {
    [, $session, $awa, $kofi] = seanceAPointer('modlk@example.tg');
    $session->update(['meeting_status' => 'live']);

    SessionAttendance::create([
        'session_id' => $session->id, 'participant_id' => $kofi->id,
        'status' => 'absent', 'source' => SessionAttendance::SOURCE_MANUAL,
    ]);

    $poses = SessionPresence::markConnected($session, [$awa->id, $kofi->id]);

    expect($poses)->toBe(1);
    expect(SessionAttendance::where('participant_id', $awa->id)->value('status'))->toBe('present');
    expect(SessionAttendance::where('participant_id', $awa->id)->value('source'))
        ->toBe(SessionAttendance::SOURCE_MEETING);
    // Le constat du modérateur n'a pas bougé.
    expect(SessionAttendance::where('participant_id', $kofi->id)->value('status'))->toBe('absent');
});

/**
 * ⚠️ Le rapprochement « connecté dans la salle ↔ inscription » passe par
 * l'IDENTITÉ LiveKit, jamais par le nom affiché : celui-ci se change depuis la
 * salle, et la présence ne peut pas dépendre de ce que quelqu'un a tapé.
 */
it('encode l’inscription dans l’identité LiveKit, et sait la relire', function () {
    $identite = LiveKitRoom::identityFor(42, 'Awa Kodjo');

    expect(LiveKitRoom::participantIdFromIdentity($identite))->toBe(42);

    // Deux connexions de la même personne ne portent pas la même identité :
    // LiveKit refuse les doublons, et on ouvre la salle sur deux appareils.
    expect(LiveKitRoom::identityFor(42, 'Awa Kodjo'))->not->toBe($identite);

    // Un invité sans inscription ne se fait pas passer pour quelqu'un.
    expect(LiveKitRoom::participantIdFromIdentity(LiveKitRoom::identityFor(null, 'Visiteur')))->toBeNull();
});

it('propose le pointage depuis les espaces modérateur et rapporteur', function () {
    [, $session] = seanceAPointer('modl@example.tg', 'rapl@example.tg');

    $this->actingAs(officier('modl@example.tg'), 'account')
        ->get(route('account.moderation'))
        ->assertOk()
        ->assertSee(route('account.attendance.edit', $session), false);

    $this->actingAs(officier('rapl@example.tg'), 'account')
        ->get(route('account.reports'))
        ->assertOk()
        ->assertSee(route('account.attendance.edit', $session), false);
});
