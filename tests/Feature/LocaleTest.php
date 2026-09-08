<?php

use App\Support\Locales;

// openEdition() / verifiedAccount() : helpers partagés (tests/Pest.php).

it('bascule la langue en session et redirige', function () {
    $this->from('/')->get(route('locale.switch', 'en'))->assertRedirect();
    expect(session('locale'))->toBe('en');
});

it('refuse une langue non prise en charge', function () {
    $this->get(route('locale.switch', 'zz'))->assertNotFound();
});

it('mémorise la langue comme préférence du compte connecté', function () {
    $account = verifiedAccount();

    $this->actingAs($account, 'account')->from('/')->get(route('locale.switch', 'en'));

    expect($account->fresh()->locale)->toBe('en');
});

it('rend l’interface participant en anglais selon la préférence du compte', function () {
    $account = verifiedAccount();
    $account->update(['locale' => 'en']);

    $this->actingAs($account, 'account')
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('My profile')   // « Mon profil » traduit
        ->assertSee('Submissions')  // « Soumissions » traduit
        ->assertDontSee('Mon profil');
});

it('applique la langue choisie en session à la page publique', function () {
    $edition = openEdition();

    // Choix de langue mémorisé en session, puis page publique du colloque.
    $this->get(route('locale.switch', 'en'));
    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertSee('Home'); // nav « Accueil » traduit
});

it('expose les locales prises en charge', function () {
    expect(Locales::codes())->toContain('fr')->toContain('en');
    expect(Locales::supported('en'))->toBeTrue();
    expect(Locales::supported('zz'))->toBeFalse();
    expect(Locales::default())->toBe('fr');
});
