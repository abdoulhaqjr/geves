<?php

use App\Models\MeetingMessage;
use App\Models\User;

// openEdition(), adminStaff() -> tests/Pest.php

/**
 * Onglet « Discussions » du module Programme : l'admin/les gestionnaires relisent
 * le fil de discussion d'une séance APRÈS la visio et après l'événement (lecture
 * directe de l'archive, indépendante de l'accès à la salle).
 */
function seanceAvecFil($event, string $body = 'Bonjour la salle', string $status = 'live')
{
    $session = $event->sessions()->create([
        'type' => 'atelier', 'label' => 'Séance - Atelier 7', 'room' => 'Salle 7',
        'starts_at' => now()->subHours(3), 'ends_at' => now()->subHours(2),
        'moderator_email' => 'mod7@example.tg', 'meeting_status' => $status,
    ]);

    $msg = new MeetingMessage(['uid' => 'msg0001', 'body' => $body]);
    $msg->author_name = 'Awa Kodjo';
    $msg->meetable()->associate($session);
    $msg->save();

    return $session;
}

it('liste les séances avec le nombre de messages de leur fil', function () {
    $event = openEdition();
    $session = seanceAvecFil($event);

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.program.index', ['event_id' => $event->id, 'tab' => 'discussions']))
        ->assertOk()
        ->assertSee('Séance - Atelier 7')
        ->assertSee('Voir le fil');
});

it('affiche le fil d’une séance même après la fin de la visio', function () {
    $event = openEdition();
    // Visio déjà terminée : le fil doit rester consultable.
    $session = seanceAvecFil($event, 'Une question pertinente', 'ended');

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.program.discussions.show', $session))
        ->assertOk()
        ->assertSee('Une question pertinente')
        ->assertSee('Awa Kodjo');
});

it('télécharge le fil en .txt', function () {
    $event = openEdition();
    $session = seanceAvecFil($event, 'Message à exporter');

    $res = $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.program.discussions.export', $session))
        ->assertOk();

    expect($res->headers->get('content-type'))->toContain('text/plain');
    expect($res->getContent())->toContain('Message à exporter');
});

it('refuse l’accès au fil sans la capacité sur l’événement', function () {
    $event = openEdition();
    $session = seanceAvecFil($event);

    // Un membre du staff sans affectation sur cet événement.
    $etranger = User::create([
        'first_name' => 'Sans', 'last_name' => 'Accès',
        'email' => 'sansacces@example.tg', 'password' => 'pass12345',
    ]);

    $this->actingAs($etranger, 'web')
        ->get(route('admin.program.discussions.show', $session))
        ->assertForbidden();
});
