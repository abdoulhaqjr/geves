<?php

/**
 * **Filtres de liste en AJAX** (cf. `data-filter` dans `resources/js/app.js`).
 *
 * ⚠️ Un champ de recherche obligeait à taper PUIS valider, et rechargeait toute
 * la page : on perdait le focus et la position de défilement à chaque essai. Ces
 * cas gardent l'invariant de balisage, parce qu'une barre de filtres se recopie
 * d'une liste à l'autre - et qu'un `onchange="this.form.submit()"` oublié dans
 * la copie rechargerait la page sans que rien ne le signale.
 */

/** Les vues qui portent une barre de filtres serveur. */
function vuesFiltrantes(): array
{
    return [
        'admin/users/index',
        'admin/evenements/index',
        'admin/participants/index',
        'admin/payments/index',
        'admin/submissions/index',
        'admin/program/index',
        'admin/documents/_issued',
        'admin/documents/_badges-ready',
    ];
}

function sourceVue(string $vue): string
{
    return (string) file_get_contents(resource_path('views/'.$vue.'.blade.php'));
}

it('ne recharge plus la page depuis une barre de filtres', function () {
    $fautifs = [];

    foreach (vuesFiltrantes() as $vue) {
        $src = sourceVue($vue);

        // ⚠️ `this.form.submit()` NE déclenche aucun évènement « submit » : il
        // contourne l'interception et recharge la page. Un `onkeydown` sur Entrée
        // fait pire - il oblige à valider ce qui s'applique déjà à la frappe.
        if (str_contains($src, 'this.form.submit()')) {
            $fautifs[] = $vue.' : this.form.submit()';
        }
        if (str_contains($src, "event.key==='Enter'")) {
            $fautifs[] = $vue.' : validation par Entrée';
        }
    }

    expect($fautifs)->toBe([]);
});

it('déclare un groupe sur chaque barre de filtres, et des régions qui lui répondent', function () {
    foreach (vuesFiltrantes() as $vue) {
        $src = sourceVue($vue);

        preg_match_all('/<form[^>]*\bdata-filter="([^"]+)"/', $src, $groupes);
        preg_match_all('/data-filter-part="([^"]+)"/', $src, $regions);

        // Toute barre de filtres de ces vues est en AJAX…
        expect(count($groupes[1]))->toBeGreaterThan(0, 'aucun data-filter dans '.$vue);

        // ⚠️ …et un `data-filter` sans valeur échangerait les régions de TOUS les
        // formulaires de la page : les onglets de Programme en portent deux.
        expect($src)->not->toContain('data-filter>', 'data-filter sans groupe dans '.$vue);

        foreach ($groupes[1] as $groupe) {
            $siennes = array_filter($regions[1], fn (string $r) => str_starts_with($r, $groupe.'-'));

            // ⚠️ Sans région au bon préfixe, le filtre part en silence : la
            // requête est émise, la réponse ignorée, et l'écran ne bouge pas.
            expect($siennes)->not->toBeEmpty("aucune région « {$groupe}-* » dans {$vue}");
        }
    }
});

it('câble la liste des participants : barre en AJAX et tableau échangeable', function () {
    $staff = adminStaff();
    $event = openEdition();
    $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);

    $this->actingAs($staff, 'web')
        ->get(route('admin.participants.index', ['event_id' => $event->id]))
        ->assertOk()
        ->assertSee('data-filter="participants"', false)
        ->assertSee('data-filter-part="participants-table"', false)
        // Le lien « Réinitialiser » dépend des filtres : il a sa propre région,
        // sans quoi il n'apparaîtrait qu'au prochain rechargement complet.
        ->assertSee('data-filter-part="participants-reset"', false);
});

it('filtre la liste sans rien changer d’autre que les régions annoncées', function () {
    $staff = adminStaff();
    $event = openEdition();
    $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $event->participants()->create([
        'first_name' => 'Kossi', 'last_name' => 'Mensah', 'email' => 'kossi@example.tg', 'role' => 'author',
    ]);

    // La requête que pose le navigateur en AJAX est une requête GET ordinaire :
    // c'est la MÊME page, dont on ne reprend que les régions. Rien de spécial
    // côté serveur - et c'est voulu, un rendu partiel dédié divergerait.
    $this->actingAs($staff, 'web')
        ->get(route('admin.participants.index', ['event_id' => $event->id, 'search' => 'Kossi']))
        ->assertOk()
        ->assertSee('Kossi')
        ->assertDontSee('awa@example.tg')
        ->assertSee('data-filter-part="participants-reset"', false);
});
