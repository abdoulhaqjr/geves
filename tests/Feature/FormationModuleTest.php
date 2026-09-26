<?php

use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\FormationTrainer;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition()/verifiedAccount() -> tests/Pest.php

it('affiche la page Formations pour un membre du staff', function () {
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.formations').'?edition_id='.$edition->id)
        ->assertOk()
        ->assertSee('Formations');
});

it('n’affiche les formations que si le module est activé dans les paramètres de l’édition', function () {
    $edition = openEdition();
    Formation::create(['edition_id' => $edition->id, 'title' => 'Atelier module', 'fee' => 0, 'published' => true]);

    // Désactivé par défaut : la liste n'apparaît pas.
    $this->actingAs(adminStaff(), 'web')->get(route('admin.formations').'?edition_id='.$edition->id)
        ->assertOk()->assertDontSee('Atelier module')->assertSee('désactivé');

    // Activé via le réglage d'édition (case « Module Formations activé » du formulaire).
    $edition->update(['settings' => array_merge($edition->settings ?? [], ['formation_enabled' => true])]);
    expect($edition->fresh()->formationEnabled())->toBeTrue();
    $this->actingAs(adminStaff(), 'web')->get(route('admin.formations').'?edition_id='.$edition->id)
        ->assertOk()->assertSee('Atelier module');
});

it('crée, met à jour et supprime une formation', function () {
    $edition = openEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->post(route('admin.formations.store', $edition), [
        'title' => 'Rédaction scientifique',
        'fee' => 15000,
        'published' => '1',
        'public' => '1',
    ]);

    $formation = Formation::where('edition_id', $edition->id)->first();
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
    $edition = openEdition();
    $account = verifiedAccount(); // e-mail awa@example.tg
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Atelier stats', 'fee' => 0]);

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
    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], ['formation_enabled' => true])]);
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Atelier', 'fee' => 0, 'published' => true]);
    $user = verifiedAccount(); // Awa Kodjo
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'trainee', 'user_id' => $user->id]);
    FormationEnrollment::create(['formation_id' => $formation->id, 'edition_id' => $edition->id, 'user_id' => $user->id, 'participant_id' => $p->id, 'status' => 'registered', 'amount' => 0]);

    $admin = adminStaff();

    // La liste compacte annonce le nombre d'inscrits et renvoie vers la formation.
    $this->actingAs($admin, 'web')
        ->get(route('admin.formations').'?edition_id='.$edition->id)
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
