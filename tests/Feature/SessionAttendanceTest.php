<?php

use App\Models\Edition;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\SessionAttendance;
use App\Models\SessionReport;
use App\Models\Slot;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Str;

// openEdition() -> tests/Pest.php

/**
 * Présence d'une séance, tenue pendant la séance par son modérateur ou son
 * rapporteur. Le scan de badge a été retiré : c'est désormais la seule source de
 * présence réelle, et elle conditionne l'attestation de participation.
 *
 * ⚠️ On ne pointe que les AUTEURS des communications de la séance : un modérateur
 * constate ce qu'il a sous les yeux, pas l'effectif de l'édition.
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
 * @return array{0: Edition, 1: EventSession, 2: Participant, 3: Participant}
 */
function seanceAPointer(string $moderateur = '', string $rapporteur = ''): array
{
    $edition = openEdition();
    $session = $edition->sessions()->create([
        'label' => 'Séance - Atelier 3', 'type' => 'atelier', 'room' => 'Salle 3',
        'starts_at' => now()->subHour(),
        'moderator_email' => $moderateur ?: null,
        'rapporteur_email' => $rapporteur ?: null,
    ]);

    $awa = Participant::create([
        'edition_id' => $edition->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => 'awa@example.tg', 'role' => 'author', 'institution' => 'Université de Kara',
    ]);
    $kofi = Participant::create([
        'edition_id' => $edition->id, 'first_name' => 'Kofi', 'last_name' => 'Mensah',
        'email' => 'kofi@example.tg', 'role' => 'attendee',
    ]);

    $submission = Submission::create([
        'edition_id' => $edition->id, 'participant_id' => $awa->id,
        'ref_code' => 'UK-TEST-001', 'access_token' => Str::random(40),
        'title' => 'Les universités africaines face au climat',
        'authors' => [
            ['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'corresponding' => true],
            ['name' => 'Kofi Mensah', 'email' => 'kofi@example.tg', 'corresponding' => false],
        ],
        'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted', 'revision_of' => 0,
    ]);

    Slot::create([
        'edition_id' => $edition->id, 'session_id' => $session->id,
        'submission_id' => $submission->id, 'participant_id' => $awa->id,
        'starts_at' => $session->starts_at, 'ends_at' => $session->starts_at->copy()->addMinutes(30),
    ]);

    return [$edition, $session, $awa, $kofi];
}

it('protège le pointage derrière le guard account', function () {
    [, $session] = seanceAPointer('mod@example.tg');

    $this->get(route('account.attendance.edit', $session))->assertRedirect(route('account.login'));
});

it('n’ouvre le pointage ni à un tiers ni à un simple inscrit', function () {
    [$edition, $session] = seanceAPointer('mod@example.tg');
    $intrus = officier('intrus@example.tg');
    Participant::create([
        'edition_id' => $edition->id, 'first_name' => 'In', 'last_name' => 'Trus',
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
 * les centaines d'inscrits de l'édition demandait au modérateur de se prononcer
 * sur des gens qui n'ont rien à faire dans sa salle.
 */
it('ne liste que les auteurs des communications de la séance', function () {
    [$edition, $session, , $kofi] = seanceAPointer('mod@example.tg');
    $mod = officier('mod@example.tg');

    // Un inscrit de l'édition, étranger à cette séance.
    Participant::create([
        'edition_id' => $edition->id, 'first_name' => 'Sans', 'last_name' => 'Rapport',
        'email' => 'ailleurs@example.tg', 'role' => 'attendee',
    ]);

    $this->actingAs($mod, 'account')
        ->get(route('account.attendance.edit', $session))
        ->assertOk()
        ->assertSee('Awa Kodjo')
        ->assertSee('Kofi Mensah')
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
        'present' => 1, 'absent' => 1, 'excused' => 0, 'unmarked' => 0, 'expected' => 2,
    ]);
});

it('laisse aussi le rapporteur pointer : c’est souvent lui qui tient la liste', function () {
    [, $session, $awa] = seanceAPointer(rapporteur: 'rap@example.tg');
    $rap = officier('rap@example.tg');

    $this->actingAs($rap, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => [$awa->id => 'excused']])
        ->assertRedirect();

    expect(SessionAttendance::where('participant_id', $awa->id)->value('status'))->toBe('excused');
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
it('refuse un inscrit de l’édition étranger à la séance', function () {
    [$edition, $session] = seanceAPointer('mod@example.tg');
    $mod = officier('mod@example.tg');
    $etranger = Participant::create([
        'edition_id' => $edition->id, 'first_name' => 'Sans', 'last_name' => 'Rapport',
        'email' => 'ailleurs@example.tg', 'role' => 'attendee',
    ]);

    $this->actingAs($mod, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => [$etranger->id => 'present']]);

    expect(SessionAttendance::where('participant_id', $etranger->id)->exists())->toBeFalse();
});

/**
 * Un co-auteur qui n'est pas inscrit sur l'édition reste VISIBLE - sinon la liste
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
    $edition = openEdition();
    $session = $edition->sessions()->create([
        'label' => 'Séance plénière', 'type' => 'plenary',
        'starts_at' => now()->subHour(), 'moderator_email' => 'modp@example.tg',
    ]);
    Participant::create([
        'edition_id' => $edition->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => 'awa@example.tg', 'role' => 'author',
    ]);

    $this->actingAs(officier('modp@example.tg'), 'account')
        ->get(route('account.attendance.edit', $session))
        ->assertOk()
        ->assertSee('Aucune communication programmée')
        ->assertDontSee('Awa Kodjo');
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
    [$edition, $session, $awa] = seanceAPointer('modv@example.tg');
    $mod = officier('modv@example.tg');
    SessionReport::create([
        'session_id' => $session->id, 'edition_id' => $edition->id,
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

it('fige la présence d’une édition archivée', function () {
    [$edition, $session, $awa] = seanceAPointer('moda@example.tg');
    $mod = officier('moda@example.tg');
    $edition->update(['status' => 'archived']);

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
