<?php

use App\Models\Edition;
use Illuminate\Support\Facades\App;

// openEdition() / adminStaff() : helpers partagés (tests/Pest.php).

it('renvoie le contenu de base (français) sans traduction', function () {
    $edition = openEdition();
    App::setLocale('en');

    expect($edition->tr('title'))->toBe($edition->title); // pas de traduction → base
});

it('renvoie la traduction de la locale courante si elle existe', function () {
    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'i18n' => ['en' => ['title' => 'Kara University Conference', 'theme' => 'Sustainability']],
    ])]);

    App::setLocale('en');
    expect($edition->tr('title'))->toBe('Kara University Conference');
    expect($edition->tr('theme'))->toBe('Sustainability');

    // Locale par défaut : toujours la base française, même si une trad existe.
    App::setLocale('fr');
    expect($edition->tr('title'))->toBe($edition->title);
});

it('enregistre les traductions depuis le back-office', function () {
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.editions.translations', $edition), [
            'locale' => 'en',
            'tr' => ['title' => 'Kara University Conference', 'theme' => '', 'edition_label' => '3rd edition', 'location' => ''],
        ])
        ->assertRedirect();

    $fresh = $edition->fresh();
    expect($fresh->translations('en'))->toBe(['title' => 'Kara University Conference', 'edition_label' => '3rd edition']);
    expect($fresh->tr('title', 'en'))->toBe('Kara University Conference');
});

it('refuse d’enregistrer la locale par défaut ou une locale inconnue', function () {
    $edition = openEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')
        ->post(route('admin.editions.translations', $edition), ['locale' => 'fr', 'tr' => ['title' => 'X']])
        ->assertNotFound();

    $this->actingAs($staff, 'web')
        ->post(route('admin.editions.translations', $edition), ['locale' => 'zz', 'tr' => ['title' => 'X']])
        ->assertNotFound();
});

it('affiche le titre traduit sur la page publique selon la langue', function () {
    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'i18n' => ['en' => ['title' => 'Kara University Conference']],
    ])]);

    // Langue EN mémorisée en session.
    $this->get(route('locale.switch', 'en'));
    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertSee('Kara University Conference');
});

it('expose les champs traduisibles', function () {
    expect(Edition::TRANSLATABLE)->toContain('title')->toContain('theme');
});
