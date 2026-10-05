<?php

use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\FormationTrainer;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition()/verifiedAccount() -> tests/Pest.php

it('affiche la page Formations pour un membre du staff', function () {
    $event = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.formations').'?event_id='.$event->id)
        ->assertOk()
        ->assertSee('Formations');
});

it('n’affiche les formations que si le module est activé dans les paramètres de l’événement', function () {
    $event = openEdition();
    Formation::create(['event_id' => $event->id, 'title' => 'Atelier module', 'fee' => 0, 'published' => true]);

    // Désactivé par défaut : la liste n'apparaît pas.
    $this->actingAs(adminStaff(), 'web')->get(route('admin.formations').'?event_id='.$event->id)
        ->assertOk()->assertDontSee('Atelier module')->assertSee('désactivé');

    // Activé via le réglage d'événement (case « Activer le Module Formations » du formulaire).
    $event->update(['settings' => array_merge($event->settings ?? [], ['formation_enabled' => true])]);
    expect($event->fresh()->formationEnabled())->toBeTrue();
    $this->actingAs(adminStaff(), 'web')->get(route('admin.formations').'?event_id='.$event->id)
        ->assertOk()->assertSee('Atelier module');
});

it('crée, met à jour et supprime une formation', function () {
    $event = openEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->post(route('admin.formations.store', $event), [
        'title' => 'Rédaction scientifique',
        'fee' => 15000,
        'published' => '1',
        'public' => '1',
    ]);

    $formation = Formation::where('event_id', $event->id)->first();
    expect($formation)->not->toBeNull();
    expect($formation->title)->toBe('Rédaction scientifique');
    expect((float) $formation->fee)->toBe(15000.0);
    expect($formation->published)->toBeTrue();
    expect($formation->isFree())->toBeFalse();

    $this->actingAs($staff, 'web')->put(route('admin.formations.update', $formation), [
        'title' => 'Rédaction et publication',
        'fee' => 0,
    ]);
    $formation->refresh();
    expect($formation->title)->toBe('Rédaction et publication');
    expect($formation->isFree())->toBeTrue();      // frais 0 = gratuite
    expect($formation->published)->toBeFalse();     // case décochée = brouillon

    $this->actingAs($staff, 'web')->delete(route('admin.formations.destroy', $formation));
    expect(Formation::find($formation->id))->toBeNull();
});

it('ajoute un formateur et le rattache au compte unifié si l’e-mail correspond', function () {
    $event = openEdition();
    $account = verifiedAccount(); // e-mail awa@example.tg
    $formation = Formation::create(['event_id' => $event->id, 'title' => 'Atelier stats', 'fee' => 0]);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.formations.trainers.store', $formation), [
        'name' => 'Dr Awa Kodjo',
        'email' => 'awa@example.tg',
    ]);

    $trainer = FormationTrainer::where('formation_id', $formation->id)->first();
    expect($trainer)->not->toBeNull();
    // L'e-mail correspond à un compte : l'identité vient du compte, pas de la saisie.
    expect($trainer->name)->toBe('Awa Kodjo');
    expect($trainer->user_id)->toBe($account->id);
    expect($trainer->is_moderator)->toBeTrue();

    $this->actingAs(adminStaff(), 'web')->delete(route('admin.formations.trainers.destroy', [$formation, $trainer]));
    expect(FormationTrainer::find($trainer->id))->toBeNull();
});

it('interdit l’accès aux Formations sans authentification staff', function () {
    $this->get(route('admin.formations'))->assertRedirect();
});

it('affiche la liste nominative des inscrits pour l’admin', function () {
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], ['formation_enabled' => true])]);
    $formation = Formation::create(['event_id' => $event->id, 'title' => 'Atelier', 'fee' => 0, 'published' => true]);
    $user = verifiedAccount(); // Awa Kodjo
    $p = $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'trainee', 'user_id' => $user->id]);
    FormationEnrollment::create(['formation_id' => $formation->id, 'event_id' => $event->id, 'user_id' => $user->id, 'participant_id' => $p->id, 'status' => 'registered', 'amount' => 0]);

    $admin = adminStaff();

    // La liste compacte annonce le nombre d'inscrits et renvoie vers la formation.
    $this->actingAs($admin, 'web')
        ->get(route('admin.formations').'?event_id='.$event->id)
        ->assertOk()
        ->assertSee('Atelier')
        ->assertSee(route('admin.formations.show', $formation), false);

    // Le détail nominatif est sur la page de la formation (une carte ne pouvait
    // pas porter la table des inscrits).
    $this->actingAs($admin, 'web')
        ->get(route('admin.formations.show', $formation))
        ->assertOk()
        ->assertSee('Inscrits')
        ->assertSee('Awa Kodjo')
        ->assertSee($user->email);
});
