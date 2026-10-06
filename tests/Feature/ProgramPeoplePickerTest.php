<?php

use App\Models\User;

// openEdition(), adminStaff() -> tests/Pest.php

/**
 * Désignation du modérateur / rapporteur d'une séance = rattachement à un COMPTE
 * par e-mail (auto-complétion). Le bug : le formulaire ne postait que le nom, donc
 * la modification d'une séance EFFACAIT l'e-mail - et coupait le lien au compte.
 */
it('suggère des comptes par nom ou e-mail', function () {
    $event = openEdition();
    User::create([
        'first_name' => 'Mariam', 'last_name' => 'Modératrice',
        'email' => 'mariam@example.tg', 'password' => 'pass12345',
    ]);

    $res = $this->actingAs(adminStaff(), 'web')
        ->getJson(route('admin.program.people', ['q' => 'Mariam']))
        ->assertOk();

    expect($res->json('people'))->toHaveCount(1);
    expect($res->json('people.0.email'))->toBe('mariam@example.tg');
    expect($res->json('people.0.name'))->toContain('Mariam');

    // Recherche par e-mail aussi.
    $this->actingAs(adminStaff(), 'web')
        ->getJson(route('admin.program.people', ['q' => 'mariam@example']))
        ->assertOk()
        ->assertJsonPath('people.0.email', 'mariam@example.tg');
});

it('ne suggère rien sous deux caractères', function () {
    $event = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->getJson(route('admin.program.people', ['q' => 'a']))
        ->assertOk()
        ->assertJsonPath('people', []);
});

it('conserve le lien au compte quand on modifie une séance', function () {
    $event = openEdition();
    $mod = User::create([
        'first_name' => 'Mariam', 'last_name' => 'Modératrice',
        'email' => 'mariam@example.tg', 'password' => 'pass12345',
    ]);
    $session = $event->sessions()->create([
        'type' => 'atelier', 'label' => 'Session A',
        'moderator_name' => 'Mariam Modératrice', 'moderator_email' => 'mariam@example.tg',
    ]);

    // Modification qui RENVOIE l'e-mail (ce que fait désormais le formulaire).
    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.program.sessions.update', $session), [
            'event_id' => $event->id,
            'sType' => 'atelier', 'sAtelierId' => '', 'sLabel' => 'Session A (corrigée)',
            'sDay' => '', 'sStart' => '', 'sEnd' => '', 'sRoom' => '',
            'sModerator' => 'Mariam Modératrice', 'sModeratorEmail' => 'mariam@example.tg',
            'sRapporteur' => '', 'sRapporteurEmail' => '',
        ])
        ->assertRedirect();

    $session->refresh();
    expect($session->label)->toBe('Session A (corrigée)');
    expect($session->moderator_email)->toBe('mariam@example.tg');
    // Le lien est effectif : la séance est bien « modérée par » ce compte.
    expect($session->isModeratedBy($mod))->toBeTrue();
});

it('refuse l’auto-complétion à un staff sans accès', function () {
    openEdition();
    $etranger = User::create([
        'first_name' => 'Sans', 'last_name' => 'Accès',
        'email' => 'sansacces@example.tg', 'password' => 'pass12345',
    ]);

    $this->actingAs($etranger, 'web')
        ->getJson(route('admin.program.people', ['q' => 'Mariam']))
        ->assertForbidden();
});
