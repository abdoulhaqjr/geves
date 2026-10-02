<?php

use App\Models\Participant;
use App\Models\SessionAttendance;
use App\Models\SessionReport;
use App\Models\User;

// openEdition() -> tests/Pest.php

/**
 * Présence d'une séance, tenue pendant la séance par son modérateur ou son
 * rapporteur. Le scan de badge a été retiré : c'est désormais la seule source de
 * présence réelle, et elle conditionne l'attestation de participation.
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

/** Une séance commencée + deux inscrits à pointer. */
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

it('laisse le modérateur pointer la présence de sa séance', function () {
    [, $session, $awa, $kofi] = seanceAPointer('mod@example.tg');
    $mod = officier('mod@example.tg');

    $this->actingAs($mod, 'account')
        ->get(route('account.attendance.edit', $session))
        ->assertOk()
        ->assertSee('Awa Kodjo')
        ->assertSee('Kofi Mensah')
        ->assertSee('Université de Kara');

    $this->actingAs($mod, 'account')
        ->post(route('account.attendance.update', $session), [
            'presence' => [$awa->id => 'present', $kofi->id => 'absent'],
        ])
        ->assertRedirect(route('account.attendance.edit', ['session' => $session->id, 'q' => null]));

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

it('refuse un participant d’une autre édition', function () {
    [, $session] = seanceAPointer('mod@example.tg');
    $mod = officier('mod@example.tg');
    $autre = openEdition();
    $etranger = Participant::create([
        'edition_id' => $autre->id, 'first_name' => 'Hors', 'last_name' => 'Sujet',
        'email' => 'hors@example.tg', 'role' => 'attendee',
    ]);

    $this->actingAs($mod, 'account')
        ->post(route('account.attendance.update', $session), ['presence' => [$etranger->id => 'present']]);

    expect(SessionAttendance::where('participant_id', $etranger->id)->exists())->toBeFalse();
});

it('n’ouvre pas le pointage avant le début de la séance', function () {
    $edition = openEdition();
    $session = $edition->sessions()->create([
        'label' => 'Séance future', 'type' => 'atelier',
        'starts_at' => now()->addDay(), 'moderator_email' => 'modf@example.tg',
    ]);
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

it('filtre la liste sur une recherche', function () {
    [, $session] = seanceAPointer('modr@example.tg');
    $mod = officier('modr@example.tg');

    $this->actingAs($mod, 'account')
        ->get(route('account.attendance.edit', ['session' => $session->id, 'q' => 'Mensah']))
        ->assertOk()
        ->assertSee('Kofi Mensah')
        ->assertDontSee('Awa Kodjo');
});

/**
 * ⚠️ La présence en séance est la SEULE source de présence réelle depuis le
 * retrait du scan de badge : sans ce raccordement, l'option « présence requise »
 * des attestations ne laissait passer personne.
 */
it('vaut présence réelle pour l’attestation de participation', function () {
    [, $session, $awa, $kofi] = seanceAPointer('modp@example.tg');

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
