<?php

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\User;
use Illuminate\Http\Testing\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

function staffWithPermission(): User
{
    Permission::firstOrCreate(['name' => 'manage_colloques', 'guard_name' => 'web']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    $user->givePermissionTo('manage_colloques');

    return $user;
}

it('redirige les invités du back-office vers la connexion admin', function () {
    $this->get('/admin')->assertRedirect(route('account.login'));
});

it('limite un staff sans affectation aux zones par événement (shell accessible)', function () {
    $user = User::factory()->create();

    // Le tableau de bord (shell) reste accessible à tout staff connecté…
    $this->actingAs($user, 'web')->get('/admin')->assertOk();
    // …mais aucune zone par événement sans affectation (modèle « pur »).
    $this->actingAs($user, 'web')->get(route('admin.evenements.index'))->assertForbidden();
    $this->actingAs($user, 'web')->get(route('admin.payments.index'))->assertForbidden();
});

it('autorise le staff habilité à voir la liste des événements', function () {
    $user = staffWithPermission();

    $this->actingAs($user, 'web')->get(route('admin.evenements.index'))->assertOk();
});

it('liste les événements de la plus récente à la plus ancienne (par année)', function () {
    $user = staffWithPermission();
    $series = EventSeries::create(['name' => 'C', 'slug' => 'c']);
    $series->events()->create(['title' => 'E 2024', 'status' => 'open', 'slug' => 'e-2024', 'year' => 2024]);
    $series->events()->create(['title' => 'E 2026', 'status' => 'open', 'slug' => 'e-2026', 'year' => 2026]);
    $series->events()->create(['title' => 'E 2025', 'status' => 'open', 'slug' => 'e-2025', 'year' => 2025]);

    $this->actingAs($user, 'web')->get(route('admin.evenements.index'))
        ->assertOk()
        ->assertSeeInOrder(['E 2026', 'E 2025', 'E 2024']);
});

it('exporte une événement donnée en JSON', function () {
    $user = staffWithPermission();
    $series = EventSeries::create(['name' => 'UK', 'slug' => 'uk']);
    $event = $series->events()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026', 'year' => 2026, 'settings' => ['tracks' => ['A']]]);

    $res = $this->actingAs($user, 'web')->get(route('admin.evenements.export', $event));
    $res->assertOk();
    $res->assertHeader('content-type', 'application/json');
    $json = json_decode($res->streamedContent(), true);
    expect($json['events'])->toHaveCount(1);
    expect($json['events'][0]['title'])->toBe('UK 2026');
    expect($json['events'][0]['settings']['tracks'])->toBe(['A']);
});

it('importe une configuration dans l’événement courante (identité conservée)', function () {
    $user = staffWithPermission();
    $series = EventSeries::create(['name' => 'UK', 'slug' => 'uk']);
    $event = $series->events()->create(['title' => 'Ancien titre', 'status' => 'draft', 'slug' => 'uk-2026', 'year' => 2026]);

    $payload = json_encode(['events' => [
        ['title' => 'Config importée', 'slug' => 'autre-slug', 'year' => 2027, 'status' => 'open', 'settings' => ['tracks' => ['X', 'Y']]],
    ]]);
    $file = File::createWithContent('edition.json', $payload);

    $this->actingAs($user, 'web')->post(route('admin.evenements.import', $event), ['file' => $file])
        ->assertSessionHasNoErrors();

    $event->refresh();
    expect($event->title)->toBe('Config importée');
    expect($event->settings['tracks'])->toBe(['X', 'Y']);
    expect($event->status)->toBe('open');
    // Identité conservée : le slug de l'événement n'est PAS écrasé par le fichier.
    expect($event->slug)->toBe('uk-2026');
});

it('crée une événement avec un nouveau colloque via le formulaire', function () {
    $user = staffWithPermission();

    $response = $this->actingAs($user, 'web')->post(route('admin.evenements.store'), [
        'new_colloque' => 'EventSeries test',
        'title' => 'Événement inaugurale 2026',
        'status' => 'open',
        'submission_open' => '1',
        'max_communications' => 3,
        'tracks' => "Axe A\nAxe B",
        'fees' => [['label' => 'Doctorant', 'amount' => '15000']],
    ]);
    $response->assertSessionHasNoErrors();

    $event = Event::firstWhere('title', 'Événement inaugurale 2026');
    expect($event)->not->toBeNull();
    // Après enregistrement, on reste sur la fiche de l'événement (pas de retour à la liste).
    $response->assertRedirect(route('admin.evenements.edit', $event));
    expect($event->slug)->toBe('evenement-inaugurale-2026');
    expect($event->settings['tracks'])->toBe(['Axe A', 'Axe B']);
    expect((float) $event->settings['fees'][0]['amount'])->toBe(15000.0);
    expect(EventSeries::where('name', 'EventSeries test')->exists())->toBeTrue();
});

it('affiche un toast de confirmation après enregistrement (reste sur la fiche)', function () {
    $user = staffWithPermission();
    $series = EventSeries::create(['name' => 'C', 'slug' => 'c']);
    $event = $series->events()->create(['title' => 'Ed', 'status' => 'draft', 'slug' => 'ed']);

    $this->actingAs($user, 'web')->followingRedirects()
        ->put(route('admin.evenements.update', $event), [
            'title' => 'Ed', 'status' => 'draft', 'event_series_id' => $event->event_series_id, 'max_communications' => 3,
            'active_tab' => 'programme',
        ])
        ->assertOk()
        ->assertSee('ukc-toasts', false)   // conteneur de toasts présent
        ->assertSee("tab: 'programme'", false); // onglet actif restauré
});

it('modifie une événement existante', function () {
    $user = staffWithPermission();
    $series = EventSeries::create(['name' => 'C', 'slug' => 'c']);
    $event = $series->events()->create(['title' => 'Ancien titre', 'status' => 'draft', 'slug' => 'ancien']);

    $this->actingAs($user, 'web')->put(route('admin.evenements.update', $event), [
        'title' => 'Nouveau titre',
        'status' => 'draft',
        'event_series_id' => $event->event_series_id,
        'max_communications' => 3,
    ])->assertSessionHasNoErrors();

    expect($event->fresh()->title)->toBe('Nouveau titre');
});

it('enregistre tous les champs du mockup (programme, langues, apparence, options…)', function () {
    $user = staffWithPermission();
    $series = EventSeries::create(['name' => 'C', 'slug' => 'c']);
    $event = $series->events()->create([
        'title' => 'Événement', 'status' => 'open', 'slug' => 'ed',
        'settings' => ['practical' => ['venue' => 'Campus Sud']],
    ]);

    $this->actingAs($user, 'web')->put(route('admin.evenements.update', $event), [
        'title' => 'Événement', 'status' => 'open', 'event_series_id' => $event->event_series_id, 'max_communications' => 3,
        'format' => 'Présentiel & hybride',
        'short_name' => 'CSIUK 2026',
        'languages' => ['Français', 'Anglais'],
        'main_language' => 'Français',
        'axes' => [['label' => 'Sciences'], ['label' => 'Santé'], ['label' => '']],
        'keyDates' => [['date' => '2026-07-15', 'label' => 'Limite de soumission']],
        'why' => [['title' => 'Visibilité', 'text' => 'Devant un large public.']],
        'infoCards' => [['title' => 'Lieu', 'text' => 'Kara, Togo']],
        'ref_prefix_mode' => 'global',
        'ref_prefix' => 'UKC-S-',
        'attestation_max' => 5,
        'coauthors_max' => 8,
        'gen_participation' => '1',
        'stats' => [['label' => 'Soumissions', 'value' => '142']],
        'stats_enabled' => '1',
        'color_primary' => '#223ea4',
        'opt_livret' => '1',
    ])->assertSessionHasNoErrors();

    $s = $event->fresh()->settings;
    expect($s['format'])->toBe('Présentiel & hybride');
    expect($s['short_name'])->toBe('CSIUK 2026');
    expect($s['languages'])->toBe(['Français', 'Anglais']);
    expect($s['tracks'])->toBe(['Sciences', 'Santé']); // axes vides ignorés
    expect($s['key_dates'][0]['label'])->toBe('Limite de soumission');
    // ⚠️ Pas de `programme` en réglage : le déroulé public est construit à partir
    // des sessions et des créneaux (module Programme), pas d'un tableau saisi.
    expect($s)->not->toHaveKey('programme');
    expect($s['why'][0]['title'])->toBe('Visibilité');
    expect($s['info_cards'][0]['title'])->toBe('Lieu');
    expect($s['ref_prefix'])->toBe('UKC-S-');
    expect($s['attestation_max'])->toBe(5);
    expect($s['gen_participation'])->toBeTrue();
    expect($s['gen_communication'])->toBeFalse(); // non coché
    expect($s['appearance']['stats'][0]['value'])->toBe('142');
    expect($s['appearance']['stats_enabled'])->toBeTrue();
    expect($s['appearance']['color_primary'])->toBe('#223ea4');
    expect($s['options']['livret'])->toBeTrue();
    expect($s['options']['badges'])->toBeFalse(); // non coché
    // Le practical existant est préservé (non écrasé).
    expect($s['practical']['venue'])->toBe('Campus Sud');
});

it('préserve submission_form et enregistre hero/contact au save', function () {
    $user = staffWithPermission();
    $series = EventSeries::create(['name' => 'C', 'slug' => 'c']);
    $event = $series->events()->create([
        'title' => 'Événement', 'status' => 'open', 'slug' => 'ed',
        'settings' => ['submission_form' => ['fields' => ['title' => ['label' => 'Intitulé']], 'custom' => []]],
    ]);

    $this->actingAs($user, 'web')->put(route('admin.evenements.update', $event), [
        'title' => $event->title,
        'status' => 'open',
        'event_series_id' => $event->event_series_id,
        'max_communications' => 3,
        'contact_email' => 'colloque@univkara.tg',
        'hero_image' => 'https://example.tg/banniere.jpg',
    ])->assertSessionHasNoErrors();

    $settings = $event->fresh()->settings;
    // La config du formulaire (gérée ailleurs) survit au save de l'événement.
    expect($settings['submission_form']['fields']['title']['label'])->toBe('Intitulé');
    expect($settings['contact']['email'])->toBe('colloque@univkara.tg');
    expect($settings['hero_image'])->toBe('https://example.tg/banniere.jpg');
});

it('affiche la page publique d’une événement publiée', function () {
    $series = EventSeries::create(['name' => 'EventSeries UK', 'slug' => 'uk']);
    $series->events()->create([
        'title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026',
        'settings' => ['tracks' => ['Sciences'], 'fees' => []],
    ]);

    $this->get(route('public.colloque', 'uk-2026'))
        ->assertOk()
        ->assertSee('UK 2026')
        ->assertSee('Ateliers disciplinaires');
});

it('renvoie 404 pour une événement en brouillon', function () {
    $series = EventSeries::create(['name' => 'EventSeries UK', 'slug' => 'uk']);
    $series->events()->create(['title' => 'Brouillon', 'status' => 'draft', 'slug' => 'brouillon']);

    $this->get(route('public.colloque', 'brouillon'))->assertNotFound();
});

/**
 * **Réorganisation des onglets de l'événement.**
 *
 * ⚠️ « Intégrations » réunit paiement et visioconférence - deux formulaires de
 * même nature (brancher l'événement sur un service extérieur), chacun gardant sa
 * sauvegarde. Les traductions rejoignent l'apparence : les deux faces de ce que
 * le visiteur voit du site public.
 *
 * Ce cas gèle la disparition des trois anciens onglets. Il compare les `tab=`
 * des liens, et non les intitulés : « Paiement » et « Visioconférence » restent
 * des mots de la page - ce sont les titres des deux cartes de l'onglet.
 */
it('réunit paiement et visio dans Intégrations, et les traductions dans Apparence', function () {
    $user = staffWithPermission();
    $series = EventSeries::create(['name' => 'C', 'slug' => 'c-onglets']);
    $event = $series->events()->create(['title' => 'Ed', 'status' => 'draft', 'slug' => 'ed-onglets']);

    $reponse = $this->actingAs($user, 'web')
        ->get(route('admin.evenements.edit', $event))
        ->assertOk();

    $reponse->assertSee('tab=integrations', false);
    $reponse->assertSee('Intégrations');

    // Les trois onglets n'existent plus.
    $reponse->assertDontSee('tab=payment', false);
    $reponse->assertDontSee('tab=meetings', false);
    $reponse->assertDontSee('tab=translations', false);

    // Mais leur contenu est toujours là, sur les onglets qui les ont repris.
    $reponse->assertSee('Passerelle de paiement de cette événement');
    $reponse->assertSee('Visioconférence de cette événement');
    $reponse->assertSee('Traductions du contenu');
});

/**
 * ⚠️ Enregistrer une passerelle ou un serveur de visio doit ramener sur
 * l'onglet qui les porte : revenir sur « Général » après un enregistrement
 * laisse croire que la saisie n'a pas été prise.
 */
it('ramène sur Intégrations après avoir enregistré paiement ou visio', function () {
    $user = staffWithPermission();
    $series = EventSeries::create(['name' => 'C', 'slug' => 'c-retour']);
    $event = $series->events()->create(['title' => 'Ed', 'status' => 'draft', 'slug' => 'ed-retour']);

    $this->actingAs($user, 'web')
        ->post(route('admin.evenements.payment', $event), ['gateway' => ''])
        ->assertSessionHas('edition_tab', 'integrations');

    $this->actingAs($user, 'web')
        ->post(route('admin.evenements.meetings', $event), ['provider' => ''])
        ->assertSessionHas('edition_tab', 'integrations');
});

/**
 * ⚠️ Un lien partagé, un favori ou une session encore ouverte portent l'ancien
 * nom d'onglet. Sans traduction, la page s'ouvrirait sur un onglet qui n'existe
 * plus : aucune pilule allumée, aucun panneau affiché - une page vide, sans
 * explication.
 */
it('ouvre les anciens liens d’onglets sur l’onglet qui les a repris', function () {
    $user = staffWithPermission();
    $series = EventSeries::create(['name' => 'C', 'slug' => 'c-vieux-liens']);
    $event = $series->events()->create(['title' => 'Ed', 'status' => 'draft', 'slug' => 'ed-vieux-liens']);

    foreach (['payment' => 'integrations', 'meetings' => 'integrations', 'translations' => 'appearance'] as $ancien => $repris) {
        $this->actingAs($user, 'web')
            ->get(route('admin.evenements.edit', ['event' => $event, 'tab' => $ancien]))
            ->assertOk()
            ->assertSee("tab: '".$repris."'", false);
    }

    // Un onglet toujours valide n'est pas touché.
    $this->actingAs($user, 'web')
        ->get(route('admin.evenements.edit', ['event' => $event, 'tab' => 'committees']))
        ->assertOk()
        ->assertSee("tab: 'committees'", false);
});
