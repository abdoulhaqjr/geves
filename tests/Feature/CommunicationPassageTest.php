<?php

use App\Models\Attestation;
use App\Models\Edition;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\SessionAttendance;
use App\Models\Slot;
use App\Support\AttestationPolicy;
use App\Support\EditionSettings;

// openEdition() -> tests/Pest.php ; acceptedComm() + payParticipant() ->
// CommunicationAttestationTest ; officier() -> SessionAttendanceTest

/**
 * **Le passage d'une communication, constaté par qui tient la séance.**
 *
 * C'est le geste qui ouvre les attestations de ses auteurs : en délivrance en
 * ligne, chacun reçoit la sienne dans la foulée, au moment où la communication
 * vient d'avoir lieu - pas à la fin du colloque, quand plus personne ne se
 * souvient de qui est monté à la tribune.
 *
 * @return array{0: Edition, 1: EventSession, 2: Slot, 3: Participant, 4: Participant}
 */
function seancePassage(string $moderateur = 'modpass@example.tg'): array
{
    $edition = openEdition();
    $session = $edition->sessions()->create([
        'label' => 'Séance - Atelier 1', 'type' => 'atelier', 'room' => 'Salle 1',
        'starts_at' => now()->subHour(),
        'moderator_email' => $moderateur,
    ]);

    $auteur = $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $co = $edition->participants()->create([
        'first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'ben@example.tg', 'role' => 'coauthor',
    ]);
    payParticipant($edition, $co);

    $submission = acceptedComm($edition, $auteur, 'PP-001', [$co]);

    $slot = Slot::create([
        'edition_id' => $edition->id, 'session_id' => $session->id,
        'submission_id' => $submission->id, 'participant_id' => $auteur->id,
        'starts_at' => $session->starts_at, 'ends_at' => $session->starts_at->copy()->addMinutes(20),
    ]);

    return [$edition, $session, $slot, $auteur, $co];
}

it('émet les attestations des auteurs quand le modérateur valide le passage', function () {
    [, $session, $slot, $auteur, $co] = seancePassage();
    $mod = officier('modpass@example.tg');

    $this->actingAs($mod, 'account')
        ->post(route('session.passage', [$session, $slot]))
        ->assertRedirect();

    expect(Attestation::where('participant_id', $auteur->id)->where('type', 'communication')->exists())->toBeTrue();
    expect(Attestation::where('participant_id', $co->id)->where('type', 'communication')->exists())->toBeTrue();

    // ⚠️ Le constat est un fait tenu en base, imputable : il ouvre des documents
    // signés, on doit pouvoir dire qui l'a posé et quand.
    $slot->refresh();
    expect($slot->wasPresented())->toBeTrue();
    expect($slot->presented_by)->toBe($mod->id);
});

/**
 * ⚠️ En délivrance papier, le passage se constate quand même - il nourrit le PV
 * et dit qui est monté à la tribune - mais il n'émet rien : c'est le secrétariat
 * qui émet le lot, le relit et l'imprime. Émettre au fil des passages lui ferait
 * réimprimer à chaque communication.
 */
it('constate le passage sans rien émettre en délivrance papier', function () {
    [$edition, $session, $slot] = seancePassage();
    EditionSettings::set($edition, 'attestations.delivery', AttestationPolicy::DELIVERY_PAPER);

    $this->actingAs(officier('modpass@example.tg'), 'account')
        ->post(route('session.passage', [$session, $slot]))
        ->assertRedirect();

    expect($slot->fresh()->wasPresented())->toBeTrue();
    expect(Attestation::where('type', 'communication')->exists())->toBeFalse();
});

/**
 * ⚠️ L'option « seulement les présents » s'appuie sur le pointage de la séance :
 * un co-auteur resté chez lui ne reçoit pas d'attestation pour une communication
 * qu'il n'a pas présentée.
 */
it('réserve l’attestation aux présents quand l’édition le demande', function () {
    [$edition, $session, $slot, $auteur, $co] = seancePassage();
    EditionSettings::set($edition, 'attestations.communication_scope', AttestationPolicy::SCOPE_PRESENT);

    SessionAttendance::create([
        'session_id' => $session->id, 'participant_id' => $auteur->id,
        'status' => 'present', 'source' => SessionAttendance::SOURCE_MANUAL,
    ]);

    $this->actingAs(officier('modpass@example.tg'), 'account')
        ->post(route('session.passage', [$session, $slot]))
        ->assertRedirect();

    expect(Attestation::where('participant_id', $auteur->id)->where('type', 'communication')->exists())->toBeTrue();
    expect(Attestation::where('participant_id', $co->id)->where('type', 'communication')->exists())->toBeFalse();
});

/**
 * ⚠️ La salle et la page de modération sont deux portes sur le même geste : deux
 * validations à une seconde d'intervalle ne doivent pas réémettre, ni déplacer
 * l'heure du constat.
 */
it('ne réémet rien si le passage est validé deux fois', function () {
    [, $session, $slot, $auteur] = seancePassage();
    $mod = officier('modpass@example.tg');

    $this->actingAs($mod, 'account')->post(route('session.passage', [$session, $slot]));
    $quand = $slot->fresh()->presented_at;

    $this->actingAs($mod, 'account')->post(route('session.passage', [$session, $slot]));

    expect(Attestation::where('participant_id', $auteur->id)->where('type', 'communication')->count())->toBe(1);
    expect($slot->fresh()->presented_at->eq($quand))->toBeTrue();
});

/**
 * ⚠️ Le créneau doit appartenir à CETTE séance. Sans cette vérification, le
 * modérateur d'une salle validerait le passage d'une communication qui se tient
 * dans une autre - et en enverrait les attestations.
 */
it('refuse un créneau qui n’appartient pas à la séance, et un tiers', function () {
    [$edition, $session, $slot] = seancePassage();

    $autre = $edition->sessions()->create([
        'label' => 'Séance - Atelier 2', 'type' => 'atelier',
        'starts_at' => now()->subHour(), 'moderator_email' => 'modpass@example.tg',
    ]);

    $this->actingAs(officier('modpass@example.tg'), 'account')
        ->post(route('session.passage', [$autre, $slot]))
        ->assertNotFound();

    // Un compte qui n'anime ni ne rapporte la séance.
    $this->actingAs(officier('tiers@example.tg'), 'account')
        ->post(route('session.passage', [$session, $slot]))
        ->assertForbidden();

    expect($slot->fresh()->wasPresented())->toBeFalse();
});

/** ⚠️ Un créneau dont l'heure est passée n'est pas une communication présentée. */
it('n’ouvre la validation qu’une fois la séance commencée', function () {
    [, $session, $slot] = seancePassage();
    $session->update(['starts_at' => now()->addDay()]);

    $this->actingAs(officier('modpass@example.tg'), 'account')
        ->post(route('session.passage', [$session, $slot]))
        ->assertForbidden();

    expect($slot->fresh()->wasPresented())->toBeFalse();
});

/**
 * ⚠️ Le mode de délivrance commande l'ENVOI, pas l'existence du document. En
 * papier, l'exemplaire qui fait foi est celui qu'on remet en main propre :
 * annoncer un document qu'on ne peut pas encore retirer ne ferait qu'amener la
 * personne au secrétariat. Elle le retrouve quand même dans son espace.
 */
it('coupe l’annonce en mode papier, et garde le SMS fermé par défaut', function () {
    $edition = openEdition();

    expect(AttestationPolicy::delivery($edition))->toBe(AttestationPolicy::DELIVERY_ONLINE);
    expect(AttestationPolicy::notifies($edition))->toBeTrue();
    // Chaque SMS est facturé : une édition de deux cents communications en
    // enverrait autant sans que personne ne l'ait décidé.
    expect(AttestationPolicy::sendsSms($edition))->toBeFalse();

    EditionSettings::set($edition, 'attestations.sms', true);
    expect(AttestationPolicy::sendsSms($edition))->toBeTrue();

    EditionSettings::set($edition, 'attestations.delivery', AttestationPolicy::DELIVERY_PAPER);
    expect(AttestationPolicy::notifies($edition))->toBeFalse();
    expect(AttestationPolicy::sendsSms($edition))->toBeFalse();
    expect(AttestationPolicy::issuesOnPassage($edition))->toBeFalse();
});

/** Un réglage abîmé ne doit pas fermer la délivrance : on retombe sur « en ligne ». */
it('retombe sur la délivrance en ligne devant une valeur inconnue', function () {
    $edition = openEdition();
    EditionSettings::set($edition, 'attestations.delivery', 'pigeon-voyageur');
    EditionSettings::set($edition, 'attestations.communication_scope', 'n’importe quoi');

    expect(AttestationPolicy::delivery($edition))->toBe(AttestationPolicy::DELIVERY_ONLINE);
    expect(AttestationPolicy::requiresPresence($edition))->toBeFalse();
});
