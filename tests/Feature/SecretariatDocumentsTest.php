<?php

use App\Models\Event;
use App\Models\Participant;

// openEdition(), adminStaff() -> tests/Pest.php

/** Événement avec une séance datée (salle + rôles) et un participant. */
function secretariatEvent(): Event
{
    $event = openEdition();
    $event->sessions()->create([
        'label' => 'Atelier 1 - Sciences',
        'type' => 'atelier',
        'room' => 'Salle A',
        'session_day' => now()->toDateString(),
        'starts_at' => now()->setTime(9, 0),
        'ends_at' => now()->setTime(11, 0),
        'moderator_name' => 'Awa Kodjo', 'moderator_email' => 'awa@uk.tg',
        'rapporteur_name' => 'Kofi Mensah', 'rapporteur_email' => 'kofi@uk.tg',
    ]);
    $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@uk.tg',
        'role' => 'author', 'title' => 'Doctorant', 'institution' => 'Université de Kara',
    ]);

    return $event;
}

/**
 * ⚠️ **L'entrée du module mène aux DOCUMENTS**, pas aux rapports de séance.
 * C'est l'onglet qu'on vient ouvrir le plus souvent, et le seul qui ne dépende
 * d'aucun travail en cours : hors période de colloque, arriver sur les rapports
 * montrait un tableau vide.
 */
it('ouvre le secrétariat sur l’onglet Documents', function () {
    $staff = adminStaff();
    secretariatEvent();

    $this->actingAs($staff, 'web')->get(route('admin.secretariat'))
        ->assertRedirect(route('admin.secretariat.documents'));

    // Et c'est bien la page des documents qui s'affiche au bout.
    $this->actingAs($staff, 'web')->followingRedirects()->get(route('admin.secretariat'))
        ->assertOk()
        ->assertSee('Liste de présence')
        ->assertSee('Feuille d’émargement', false);
});

it('affiche les trois onglets du secrétariat', function () {
    $staff = adminStaff();
    secretariatEvent();

    $this->actingAs($staff, 'web')->get(route('admin.secretariat.documents'))
        ->assertOk()->assertSee('Rapports de séance')->assertSee('Documents');

    $this->actingAs($staff, 'web')->get(route('admin.secretariat.assistant'))
        ->assertOk()->assertSee('Générer un document');
});

it('liste les documents imprimables de l’onglet Documents', function () {
    $staff = adminStaff();
    $event = secretariatEvent();

    $this->actingAs($staff, 'web')->get(route('admin.secretariat.documents', ['doc_event' => $event->id]))
        ->assertOk()
        ->assertSee('Liste de présence')
        ->assertSee('Feuille d’émargement')
        ->assertSee('Affiche directionnelle')
        ->assertSee('Ordre du jour')
        ->assertSee('Chevalets nominatifs');
});

it('imprime la liste de présence avec les participants', function () {
    $staff = adminStaff();
    $event = secretariatEvent();

    $this->actingAs($staff, 'web')
        ->get(route('admin.secretariat.documents.print', ['event' => $event, 'type' => 'presence']))
        ->assertOk()->assertSee('Liste de présence')->assertSee('Kodjo Awa');
});

it('imprime la feuille d’émargement avec des lignes vierges', function () {
    $staff = adminStaff();
    $event = secretariatEvent();

    $this->actingAs($staff, 'web')
        ->get(route('admin.secretariat.documents.print', ['event' => $event, 'type' => 'emargement', 'extra' => 3]))
        ->assertOk()->assertSee('Feuille d’émargement')->assertSee('Signature');
});

it('imprime l’ordre du jour et les chevalets depuis le programme réel', function () {
    $staff = adminStaff();
    $event = secretariatEvent();

    $this->actingAs($staff, 'web')
        ->get(route('admin.secretariat.documents.print', ['event' => $event, 'type' => 'ordre_jour', 'day' => now()->toDateString()]))
        ->assertOk()->assertSee('Atelier 1 - Sciences')->assertSee('Salle A');

    $this->actingAs($staff, 'web')
        ->get(route('admin.secretariat.documents.print', ['event' => $event, 'type' => 'chevalets', 'who' => 'both']))
        ->assertOk()->assertSee('Awa Kodjo')->assertSee('Kofi Mensah');
});

it('imprime une liste de présence vierge (sans les participants)', function () {
    $staff = adminStaff();
    $event = secretariatEvent();

    $this->actingAs($staff, 'web')
        ->get(route('admin.secretariat.documents.print', ['event' => $event, 'type' => 'presence', 'blank' => 1, 'rows' => 5]))
        ->assertOk()
        ->assertSee('Liste de présence')
        ->assertSee('Émargement')
        ->assertDontSee('Kodjo Awa');
});

it('enregistre l’en-tête des documents et le reporte sur l’impression', function () {
    $staff = adminStaff();
    $event = secretariatEvent();

    $this->actingAs($staff, 'web')
        ->post(route('admin.secretariat.documents.header', ['event' => $event]), [
            'doc_org' => 'Université de Kara - DRI',
            'doc_subtitle' => 'Comité d’organisation',
            'doc_align' => 'center',
            'doc_logo' => '1',
        ])
        ->assertRedirect(route('admin.secretariat.documents', ['doc_event' => $event->id]));

    $this->actingAs($staff, 'web')
        ->get(route('admin.secretariat.documents.print', ['event' => $event, 'type' => 'presence']))
        ->assertOk()
        ->assertSee('Université de Kara - DRI')
        ->assertSee('Comité d’organisation');
});

it('refuse un type de document inconnu', function () {
    $staff = adminStaff();
    $event = secretariatEvent();

    $this->actingAs($staff, 'web')
        ->get(route('admin.secretariat.documents.print', ['event' => $event, 'type' => 'inconnu']))
        ->assertNotFound();
});
