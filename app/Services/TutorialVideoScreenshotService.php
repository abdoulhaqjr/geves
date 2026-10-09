<?php

namespace App\Services;

use App\Models\TutorialGuide;
use App\Support\TutorialCaptureCatalog;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\URL;

/** Capture les véritables pages GEVES dans un navigateur isolé avant le rendu vidéo. */
class TutorialVideoScreenshotService
{
    /** @return list<array{title: string, path: string}> */
    public function capture(TutorialGuide $guide, string $directory): array
    {
        $captureDirectory = $directory.DIRECTORY_SEPARATOR.'captures';
        File::ensureDirectoryExists($captureDirectory);

        $screens = collect(TutorialCaptureCatalog::targetsFor($guide))
            ->values()
            ->map(fn (array $target, int $index): array => [
                'title' => $target['title'],
                'url' => URL::temporarySignedRoute('tutorial.capture', now()->addMinutes(10), [
                    'guide' => $guide,
                    'step' => $index + 1,
                ]),
                'path' => $captureDirectory.DIRECTORY_SEPARATOR.'etape-'.($index + 1).'.png',
            ])->all();

        foreach ($screens as $index => $screen) {
            $rawCapture = $directory.DIRECTORY_SEPARATOR.'hyperframes-capture-'.($index + 1);
            $process = Process::timeout((int) config('tutorial-video.capture_process_timeout', 240))
                ->path(base_path())
                ->run([
                    config('tutorial-video.npx_binary'),
                    '--yes', 'hyperframes@'.config('tutorial-video.hyperframes_version', '0.8.142'), 'capture',
                    $screen['url'], '-o', $rawCapture,
                    '--skip-assets', '--skip-vision', '--max-screenshots', '1',
                    '--timeout', (string) config('tutorial-video.capture_timeout', 60000),
                    '--capture-budget', (string) config('tutorial-video.capture_budget', 60000), '--json',
                ]);
            $source = $rawCapture.DIRECTORY_SEPARATOR.'screenshots'.DIRECTORY_SEPARATOR.'scroll-000.png';

            if (! $process->successful() || ! File::isFile($source)) {
                throw new \RuntimeException(str($process->errorOutput().$process->output())->squish()->limit(1000, '…')->toString() ?: 'La capture Hyperframes de l’écran réel a échoué.');
            }

            File::copy($source, $screen['path']);
        }

        return array_map(fn (array $screen): array => [
            'title' => (string) $screen['title'],
            'path' => (string) $screen['path'],
        ], $screens);
    }
}
