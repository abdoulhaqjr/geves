<?php

/**
 * Rendu des deux pages de prise en main (back-office + espace participant).
 * ⚠️ Garde-fou de compilation Blade : la refonte a touché la structure des
 * vues ; ces cas échouent si une directive casse ou si la nouvelle grille
 * (`onb-grid` / `onb-card`) disparaît.
 */
it('affiche la prise en main du back-office avec la nouvelle grille de guides', function () {
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.onboarding'))
        ->assertOk()
        ->assertSee('onb-grid', false)
        ->assertSee('onb-card', false)
        ->assertSee('Comprendre la plateforme')
        ->assertDontSee('onboarding-guide__number', false); // l'ancien gadget « Guide 01 » a disparu
});

it('affiche la prise en main du participant avec ses guides', function () {
    $this->actingAs(verifiedAccount(), 'account')
        ->get(route('account.onboarding'))
        ->assertOk()
        ->assertSee('onb-grid', false)
        ->assertSee('Votre parcours de prise en main');
});
