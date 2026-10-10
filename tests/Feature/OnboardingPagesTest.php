<?php

use App\Models\TutorialGuide;
use App\Models\TutorialVideoRequest;
use App\Services\TutorialVideoRenderer;
use App\Services\TutorialVideoWorkerLauncher;
use App\Support\TutorialGuideCatalog;
use Illuminate\Support\Facades\File;

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

/**
 * **Résolution de FFmpeg et FFprobe** (`TutorialVideoRenderer`).
 *
 * ⚠️ **Ces cas éprouvaient le POSTE, pas le code** : ils exigeaient
 * `toEndWith('ffmpeg.exe')`, donc un FFmpeg installé **par WinGet** (paquet
 * `Gyan.FFmpeg`) sur la machine qui lance les tests. Sans lui - en intégration
 * continue, sous Linux, ou avec un FFmpeg posé par Chocolatey, Scoop ou à la
 * main - le résolveur rend `ffmpeg` et laisse le PATH trancher, ce qui est le
 * comportement VOULU ; le cas échouait quand même. Ils éprouvent désormais la
 * **résolution elle-même**, sur une arborescence fabriquée, donc partout.
 */
function resoudreFfmpeg(): string
{
    $m = new ReflectionMethod(TutorialVideoRenderer::class, 'ffmpegBinary');
    $m->setAccessible(true);

    return (string) $m->invoke(app(TutorialVideoRenderer::class));
}

function resoudreFfprobe(string $ffmpeg): string
{
    $m = new ReflectionMethod(TutorialVideoRenderer::class, 'ffprobeBinary');
    $m->setAccessible(true);

    return (string) $m->invoke(app(TutorialVideoRenderer::class), $ffmpeg);
}

/** Racine jetable, nettoyée après chaque cas (cf. `afterEach` en bas de fichier). */
function racineBinairesVideo(): string
{
    $racine = storage_path('framework'.DIRECTORY_SEPARATOR.'testing'.DIRECTORY_SEPARATOR.'bin-video');
    File::ensureDirectoryExists($racine);

    return $racine;
}

/** Nom d'un exécutable selon la plateforme, comme le fait le résolveur. */
function nomBinaire(string $base): string
{
    return DIRECTORY_SEPARATOR === '\\' ? $base.'.exe' : $base;
}

it('préfère le chemin FFmpeg configuré', function () {
    $binaire = racineBinairesVideo().DIRECTORY_SEPARATOR.nomBinaire('ffmpeg');
    File::put($binaire, '');
    config()->set('tutorial-video.ffmpeg_binary', $binaire);

    expect(resoudreFfmpeg())->toBe($binaire);
});

it('ignore un chemin FFmpeg configuré qui n’existe pas', function () {
    // ⚠️ Garde le `is_file()` : un chemin resté dans un `.env` après une
    // désinstallation ferait échouer le rendu avec « commande introuvable »
    // plutôt que de retomber sur le PATH, où FFmpeg est peut-être.
    config()->set('tutorial-video.ffmpeg_binary', racineBinairesVideo().DIRECTORY_SEPARATOR.'nexiste-pas.exe');

    expect(resoudreFfmpeg())->toBe('ffmpeg');
});

it('trouve FFmpeg installé par WinGet, et prend la version la plus récente', function () {
    $racine = racineBinairesVideo();
    $paquets = $racine.DIRECTORY_SEPARATOR.'Microsoft'.DIRECTORY_SEPARATOR.'WinGet'.DIRECTORY_SEPARATOR.'Packages';

    foreach (['Gyan.FFmpeg_7.0', 'Gyan.FFmpeg_10.0'] as $paquet) {
        $bin = $paquets.DIRECTORY_SEPARATOR.$paquet.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'bin';
        File::ensureDirectoryExists($bin);
        File::put($bin.DIRECTORY_SEPARATOR.'ffmpeg.exe', '');
    }

    $avant = getenv('LOCALAPPDATA');
    putenv('LOCALAPPDATA='.$racine);

    try {
        $resolu = resoudreFfmpeg();
    } finally {
        putenv($avant === false ? 'LOCALAPPDATA' : 'LOCALAPPDATA='.$avant);
    }

    // ⚠️ Le tri est NATUREL, et ces deux versions le prouvent : trié comme des
    // chaînes, « 7.0 » passerait APRÈS « 10.0 » et le résolveur servirait la
    // vieille version.
    expect($resolu)->toEndWith('ffmpeg.exe')
        ->and($resolu)->toContain('Gyan.FFmpeg_10.0');
})->onlyOnWindows();

it('laisse le PATH trancher quand FFmpeg n’est installé nulle part', function () {
    // C'est le cas d'un poste sans FFmpeg, et d'un FFmpeg posé autrement que par
    // WinGet : `ffmpeg` tout court est la bonne réponse, pas un échec.
    $avant = getenv('LOCALAPPDATA');
    putenv('LOCALAPPDATA='.racineBinairesVideo());

    try {
        expect(resoudreFfmpeg())->toBe('ffmpeg');
    } finally {
        putenv($avant === false ? 'LOCALAPPDATA' : 'LOCALAPPDATA='.$avant);
    }
});

it('prend FFprobe à côté de FFmpeg', function () {
    $bin = racineBinairesVideo().DIRECTORY_SEPARATOR.'bin';
    File::ensureDirectoryExists($bin);
    $ffmpeg = $bin.DIRECTORY_SEPARATOR.nomBinaire('ffmpeg');
    $ffprobe = $bin.DIRECTORY_SEPARATOR.nomBinaire('ffprobe');
    File::put($ffmpeg, '');
    File::put($ffprobe, '');

    // Hyperframes exige FFprobe : il accompagne FFmpeg dans la même archive.
    expect(resoudreFfprobe($ffmpeg))->toBe($ffprobe);
});

it('laisse le PATH trancher quand FFprobe n’accompagne pas FFmpeg', function () {
    $bin = racineBinairesVideo().DIRECTORY_SEPARATOR.'bin';
    File::ensureDirectoryExists($bin);
    $ffmpeg = $bin.DIRECTORY_SEPARATOR.nomBinaire('ffmpeg');
    File::put($ffmpeg, '');

    expect(resoudreFfprobe($ffmpeg))->toBe('ffprobe');
});

it('préfère le chemin FFprobe configuré, même si un voisin existe', function () {
    $bin = racineBinairesVideo().DIRECTORY_SEPARATOR.'bin';
    File::ensureDirectoryExists($bin);
    $ffmpeg = $bin.DIRECTORY_SEPARATOR.nomBinaire('ffmpeg');
    File::put($ffmpeg, '');
    File::put($bin.DIRECTORY_SEPARATOR.nomBinaire('ffprobe'), '');

    $choisi = racineBinairesVideo().DIRECTORY_SEPARATOR.nomBinaire('ffprobe-choisi');
    File::put($choisi, '');
    config()->set('tutorial-video.ffprobe_binary', $choisi);

    expect(resoudreFfprobe($ffmpeg))->toBe($choisi);
});

afterEach(function () {
    File::deleteDirectory(storage_path('framework'.DIRECTORY_SEPARATOR.'testing'.DIRECTORY_SEPARATOR.'bin-video'));
});

it('prépare les rendus tutoriels au format Full HD', function () {
    expect(config('tutorial-video.render_width'))->toBe(1920)
        ->and(config('tutorial-video.render_height'))->toBe(1080);
});
