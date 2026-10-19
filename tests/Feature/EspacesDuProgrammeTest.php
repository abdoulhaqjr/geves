<?php

use App\Models\Atelier;
use App\Models\Event;
use App\Models\Slot;
use App\Models\User;

// adminStaff() / openEdition() -> Pest.php ; makeSession() -> ProgramPlenaryExclusivityTest

/**
 * ⚠️⚠️ **LE NOM EST LE SEUL LIEN** entre un espace et le programme : séances et
 * créneaux portent le NOM de la salle, pas un identifiant. D'où les deux règles
 * que ces cas tiennent - toute salle employée est déclarée d'office, et renommer
 * un espace renomme la salle partout.
 */
function espacesDe(Event $event): array
{
    return $event->fresh()->settings['program']['spaces'] ?? [];
}

function pageProgramme(User $staff, Event $event): void
{
    test()->actingAs($staff, 'web')
        ->get(route('admin.program.index', ['event_id' => $event->id, 'tab' => 'espaces']))
        ->assertOk();
}

it('déclare d’office toute salle que le programme emploie', function () {
    $staff = adminStaff();
    $event = openEdition();
    makeSession($event->id, 'atelier', '2026-10-12', '14:00', '17:00')->update(['room' => 'Salle 1']);
    Slot::create(['event_id' => $event->id, 'room' => 'Auditorium', 'submission_id' => 0]);

    pageProgramme($staff, $event);

    expect(collect(espacesDe($event))->pluck('name')->all())->toBe(['Auditorium', 'Salle 1']);
    // Déclarée depuis le programme : la fiche le dit, et il reste à la renseigner.
    expect(espacesDe($event)[0]['auto'])->toBeTrue();
    expect(espacesDe($event)[0]['capacity'])->toBeNull();
});

it('ne redéclare rien, et ne touche pas à ce qui est renseigné', function () {
    $staff = adminStaff();
    $event = openEdition();
    makeSession($event->id, 'atelier', '2026-10-12', '14:00', '17:00')->update(['room' => 'Salle 1']);

    $this->actingAs($staff, 'web')->post(route('admin.program.spaces.store', ['event_id' => $event->id]), [
        'spaceName' => 'Salle 1', 'spaceCapacity' => '120', 'spaceBuilding' => 'Bâtiment A',
    ])->assertSessionHasNoErrors();

    pageProgramme($staff, $event);
    pageProgramme($staff, $event);

    expect(espacesDe($event))->toHaveCount(1);
    expect(espacesDe($event)[0]['capacity'])->toBe(120);
    expect(espacesDe($event)[0]['building'])->toBe('Bâtiment A');
});

it('déclare aussi la salle associée à un atelier', function () {
    $staff = adminStaff();
    $event = openEdition();
    $atelier = Atelier::create(['event_id' => $event->id, 'title' => 'Atelier 1', 'tracks' => [], 'ordre' => 0]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.program.ateliers.room', $atelier), ['atelierRoom' => 'Hall posters'])
        ->assertSessionHasNoErrors();

    pageProgramme($staff, $event);

    expect(collect(espacesDe($event))->pluck('name')->all())->toBe(['Hall posters']);
});

it('renomme la salle sur les séances, les créneaux et les ateliers', function () {
    $staff = adminStaff();
    $event = openEdition();
    $atelier = Atelier::create(['event_id' => $event->id, 'title' => 'Atelier 1', 'tracks' => [], 'ordre' => 0]);
    $session = makeSession($event->id, 'atelier', '2026-10-12', '14:00', '17:00');
    $session->update(['room' => 'Salle 1', 'atelier_id' => $atelier->id]);
    $slot = Slot::create(['event_id' => $event->id, 'room' => 'Salle 1', 'submission_id' => 0]);
    $ailleurs = Slot::create(['event_id' => $event->id, 'room' => 'Salle 2', 'submission_id' => 0]);
    $this->actingAs($staff, 'web')->post(route('admin.program.ateliers.room', $atelier), ['atelierRoom' => 'Salle 1']);

    pageProgramme($staff, $event);
    $index = collect(espacesDe($event))->search(fn (array $e) => $e['name'] === 'Salle 1');

    $this->actingAs($staff, 'web')
        ->put(route('admin.program.spaces.update', ['index' => $index, 'event_id' => $event->id]), [
            'spaceName' => 'Amphi A', 'spaceCapacity' => '300',
        ])->assertSessionHasNoErrors();

    expect($session->fresh()->room)->toBe('Amphi A');
    expect($slot->fresh()->room)->toBe('Amphi A');
    // ⚠️ Seule la salle renommée bouge : les autres gardent la leur.
    expect($ailleurs->fresh()->room)->toBe('Salle 2');
    expect($event->fresh()->settings['program']['atelier_rooms'][(string) $atelier->id])->toBe('Amphi A');

    // ⚠️ Et l'ancien nom ne revient PAS par la déclaration d'office : plus rien
    // ne le porte. Sans la réécriture ci-dessus, on aurait renommé une fiche et
    // vu réapparaître « Salle 1 » juste à côté.
    pageProgramme($staff, $event);
    expect(collect(espacesDe($event))->pluck('name')->all())->toBe(['Amphi A', 'Salle 2']);
    expect(collect(espacesDe($event))->firstWhere('name', 'Amphi A')['capacity'])->toBe(300);
});

it('renommer sur un espace déjà déclaré le rejoint', function () {
    // C'est ainsi qu'on répare deux graphies de la même salle.
    $staff = adminStaff();
    $event = openEdition();
    makeSession($event->id, 'atelier', '2026-10-12', '14:00', '17:00')->update(['room' => 'Salle1']);
    $this->actingAs($staff, 'web')->post(route('admin.program.spaces.store', ['event_id' => $event->id]), [
        'spaceName' => 'Salle 1', 'spaceCapacity' => '120',
    ]);

    pageProgramme($staff, $event);
    $index = collect(espacesDe($event))->search(fn (array $e) => $e['name'] === 'Salle1');

    $this->actingAs($staff, 'web')
        ->put(route('admin.program.spaces.update', ['index' => $index, 'event_id' => $event->id]), [
            'spaceName' => 'Salle 1',
        ])->assertSessionHasNoErrors();

    // Une seule fiche, celle d'arrivée, qui garde ses renseignements.
    expect(espacesDe($event))->toHaveCount(1);
    expect(espacesDe($event)[0])->toMatchArray(['name' => 'Salle 1', 'capacity' => 120]);
    expect($event->sessions()->first()->room)->toBe('Salle 1');
});

it('modifie le PREMIER espace au lieu d’en ajouter un', function () {
    // ⚠️ L'index du premier espace vaut 0, et 0 est faux : le formulaire éprouve
    // `form.i !== ''`, sans quoi modifier la première salle en créait une autre.
    $staff = adminStaff();
    $event = openEdition();
    $this->actingAs($staff, 'web')->post(route('admin.program.spaces.store', ['event_id' => $event->id]), [
        'spaceName' => 'Salle 1',
    ]);

    $this->actingAs($staff, 'web')
        ->put(route('admin.program.spaces.update', ['index' => 0, 'event_id' => $event->id]), [
            'spaceName' => 'Salle 1', 'spaceCapacity' => '90',
        ])->assertSessionHasNoErrors();

    expect(espacesDe($event))->toHaveCount(1);
    expect(espacesDe($event)[0]['capacity'])->toBe(90);
});

it('refuse de supprimer un espace où le programme se tient', function () {
    $staff = adminStaff();
    $event = openEdition();
    makeSession($event->id, 'atelier', '2026-10-12', '14:00', '17:00')->update(['room' => 'Salle 1']);

    // ⚠️ L'écran n'offre pas le bouton - le retour AJAX annoncerait « Espace
    // supprimé. » sur une suppression refusée. Mais la règle est au serveur.
    $this->actingAs($staff, 'web')
        ->get(route('admin.program.index', ['event_id' => $event->id, 'tab' => 'espaces']))
        ->assertOk()
        ->assertSee('renommez-le, ou libérez-le d’abord', false)
        ->assertDontSee(route('admin.program.spaces.destroy', 0).'?event_id='.$event->id, false);

    $this->from(route('admin.program.index'))
        ->actingAs($staff, 'web')
        ->delete(route('admin.program.spaces.destroy', ['index' => 0, 'event_id' => $event->id]))
        ->assertSessionHas('ukc_error');

    // ⚠️ Le refuser plutôt que le laisser revenir au rendu suivant : on verrait
    // réapparaître ce qu'on vient d'effacer, sans comprendre pourquoi.
    expect(espacesDe($event))->toHaveCount(1);
});

it('supprime un espace que rien n’emploie', function () {
    $staff = adminStaff();
    $event = openEdition();
    $this->actingAs($staff, 'web')->post(route('admin.program.spaces.store', ['event_id' => $event->id]), [
        'spaceName' => 'Salle de réserve',
    ]);

    $this->actingAs($staff, 'web')
        ->delete(route('admin.program.spaces.destroy', ['index' => 0, 'event_id' => $event->id]))
        ->assertSessionHasNoErrors();

    expect(espacesDe($event))->toBeEmpty();
});

it('refuse deux espaces du même nom', function () {
    $staff = adminStaff();
    $event = openEdition();
    $poster = fn () => $this->from(route('admin.program.index'))
        ->actingAs($staff, 'web')
        ->post(route('admin.program.spaces.store', ['event_id' => $event->id]), ['spaceName' => 'Salle 1']);

    $poster()->assertSessionHasNoErrors();
    $poster()->assertSessionHas('ukc_error');

    expect(espacesDe($event))->toHaveCount(1);
});
