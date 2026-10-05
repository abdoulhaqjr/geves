<?php

// adminStaff(), verifiedAccount() -> tests/Pest.php

/**
 * Densité des back-offices : **zoom 90% par défaut**, côté gestion comme côté
 * participant. Ces écrans sont des tableaux de bord - une ligne de plus sans
 * défilement y vaut mieux qu'un caractère plus gros.
 *
 * ⚠️ La classe se pose sur `<html>` et NON sur le shell : le `zoom` d'un
 * sous-arbre met à l'échelle les longueurs qu'il contient sans toucher au
 * viewport, donc un `min-height: 100vh` ne couvrirait plus que 90% de l'écran.
 * Sur la racine, c'est le bloc conteneur initial qui est mis à l'échelle -
 * exactement ce que fait le zoom du navigateur, qui reste cumulable.
 */
it('pose la densité sur la racine du back-office de gestion', function () {
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('<html lang="fr" class="ukc-dense">', false);
});

it('pose la densité sur la racine de l’espace participant', function () {
    $this->actingAs(verifiedAccount(), 'account')
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('<html lang="fr" class="ukc-dense">', false);
});

/** Le public, lui, reste à l'échelle du navigateur du visiteur. */
it('laisse la page publique à son échelle', function () {
    $event = openEdition();

    $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertSee('<html lang="fr">', false)
        ->assertDontSee('ukc-dense');
});
