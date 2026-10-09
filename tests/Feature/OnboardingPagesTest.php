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

it('affiche un seul formulaire vidéo et poste vers son URL déclarée', function () {
    $response = $this->actingAs(adminStaff(), 'web')->get(route('admin.onboarding'));

    $response
        ->assertOk()
        ->assertSee("form.getAttribute('action')", false);

    expect(substr_count($response->getContent(), 'id="video-music"'))->toBe(1);
});

it('expose la progression d’une production vidéo au calque de suivi', function () {
    \App\Support\TutorialGuideCatalog::ensureDefaults();
    $staff = adminStaff();
    $videoRequest = \App\Models\TutorialVideoRequest::create([
        'tutorial_guide_id' => \App\Models\TutorialGuide::query()->firstOrFail()->id,
        'requested_by_user_id' => $staff->id,
        'action' => 'generate',
        'status' => 'running',
        'providers' => [],
        'meta' => ['progress' => ['stage' => 'rendering', 'percent' => 70, 'label' => 'Montage Hyperframes en cours.']],
    ]);

    $this->actingAs($staff, 'web')
        ->getJson(route('admin.onboarding.videos.status', $videoRequest))
        ->assertOk()
        ->assertJsonPath('request.id', $videoRequest->id)
        ->assertJsonPath('request.stage', 'rendering')
        ->assertJsonPath('request.percent', 70)
        ->assertJsonPath('request.label', 'Montage Hyperframes en cours.');
});
