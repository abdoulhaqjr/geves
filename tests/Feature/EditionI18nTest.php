<?php

use App\Models\Event;
use Illuminate\Support\Facades\App;

// openEdition() / adminStaff() : helpers partagés (tests/Pest.php).

it('renvoie le contenu de base (français) sans traduction', function () {
    $event = openEdition();
    App::setLocale('en');

    expect($event->tr('title'))->toBe($event->title); // pas de traduction → base
});

it('renvoie la traduction de la locale courante si elle existe', function () {
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'i18n' => ['en' => ['title' => 'Kara University Conference', 'theme' => 'Sustainability']],
    ])]);

    App::setLocale('en');
    expect($event->tr('title'))->toBe('Kara University Conference');
    expect($event->tr('theme'))->toBe('Sustainability');

    // Locale par défaut : toujours la base française, même si une trad existe.
    App::setLocale('fr');
    expect($event->tr('title'))->toBe($event->title);
});

it('enregistre les traductions depuis le back-office', function () {
    $event = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.evenements.translations', $event), [
            'locale' => 'en',
            'tr' => ['title' => 'Kara University Conference', 'theme' => '', 'event_label' => '3rd edition', 'location' => ''],
        ])
        ->assertRedirect();

    $fresh = $event->fresh();
    expect($fresh->translations('en'))->toBe(['title' => 'Kara University Conference', 'event_label' => '3rd edition']);
    expect($fresh->tr('title', 'en'))->toBe('Kara University Conference');
});

it('refuse d’enregistrer la locale par défaut ou une locale inconnue', function () {
    $event = openEdition();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')
        ->post(route('admin.evenements.translations', $event), ['locale' => 'fr', 'tr' => ['title' => 'X']])
        ->assertNotFound();

    $this->actingAs($staff, 'web')
        ->post(route('admin.evenements.translations', $event), ['locale' => 'zz', 'tr' => ['title' => 'X']])
        ->assertNotFound();
});

it('affiche le titre traduit sur la page publique selon la langue', function () {
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'i18n' => ['en' => ['title' => 'Kara University Conference']],
    ])]);

    // Langue EN mémorisée en session.
    $this->get(route('locale.switch', 'en'));
    $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertSee('Kara University Conference');
});

it('expose les champs traduisibles', function () {
    expect(Event::TRANSLATABLE)->toContain('title')->toContain('theme');
});
