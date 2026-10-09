<?php

use App\Models\TutorialGuide;
use App\Models\TutorialVideoRequest;
use App\Services\TutorialVideoRenderer;
use App\Services\TutorialVideoWorkerLauncher;
use App\Support\TutorialGuideCatalog;

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
    TutorialGuideCatalog::ensureDefaults();
    $staff = adminStaff();
    $videoRequest = TutorialVideoRequest::create([
        'tutorial_guide_id' => TutorialGuide::query()->firstOrFail()->id,
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

it('utilise un binaire PHP CLI pour le worker vidéo Windows', function () {
    $method = new ReflectionMethod(TutorialVideoWorkerLauncher::class, 'phpCli');
    $method->setAccessible(true);

    expect($method->invoke(app(TutorialVideoWorkerLauncher::class)))
        ->toEndWith('php.exe');
});

it('résout FFmpeg pour le renderer vidéo Windows', function () {
    $method = new ReflectionMethod(TutorialVideoRenderer::class, 'ffmpegBinary');
    $method->setAccessible(true);

    expect($method->invoke(app(TutorialVideoRenderer::class)))
        ->toEndWith('ffmpeg.exe');
});

it('résout FFprobe compagnon de FFmpeg pour Hyperframes', function () {
    $ffmpeg = new ReflectionMethod(TutorialVideoRenderer::class, 'ffmpegBinary');
    $ffmpeg->setAccessible(true);
    $ffprobe = new ReflectionMethod(TutorialVideoRenderer::class, 'ffprobeBinary');
    $ffprobe->setAccessible(true);
    $renderer = app(TutorialVideoRenderer::class);

    expect($ffprobe->invoke($renderer, $ffmpeg->invoke($renderer)))
        ->toEndWith('ffprobe.exe');
});
