<?php

// verifiedAccount() est défini dans tests/Pest.php.

it('enregistre un ORCID valide (normalisé) sur le profil', function () {
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->put(route('account.profile.update'), [
            'first_name' => 'Awa', 'last_name' => 'Kodjo',
            'affiliation' => 'Université de Kara',
            'orcid' => 'https://orcid.org/0000-0002-1825-0097',
        ])
        ->assertSessionHasNoErrors();

    expect($account->fresh()->orcid)->toBe('0000-0002-1825-0097');

    // La page profil affiche l'ORCID comme lien vérifiable.
    $this->actingAs($account->fresh(), 'account')
        ->get(route('account.profile'))
        ->assertOk()
        ->assertSee('0000-0002-1825-0097')
        ->assertSee('orcid.org/0000-0002-1825-0097', false);
});

it('rejette un ORCID invalide', function () {
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->put(route('account.profile.update'), [
            'first_name' => 'Awa', 'last_name' => 'Kodjo',
            'orcid' => '0000-0002-1825-0098', // checksum faux
        ])
        ->assertSessionHasErrors('orcid');

    expect($account->fresh()->orcid)->toBeNull();
});

it('accepte un profil sans ORCID', function () {
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->put(route('account.profile.update'), [
            'first_name' => 'Awa', 'last_name' => 'Kodjo', 'orcid' => '',
        ])
        ->assertSessionHasNoErrors();

    expect($account->fresh()->orcid)->toBeNull();
});
