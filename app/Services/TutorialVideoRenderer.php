<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/** Rend une composition Hyperframes autonome depuis le brief éditorial validé. */
class TutorialVideoRenderer
{
    /** @param array<string, mixed> $brief @return array{video: string, poster: string, summary: string} */
    public function render(array $brief, string $directory): array
    {
        File::ensureDirectoryExists($directory);
        $input = $directory.DIRECTORY_SEPARATOR.'render-input.json';
        $output = $directory.DIRECTORY_SEPARATOR.'render-result.json';

        File::put($input, json_encode($brief, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $process = Process::timeout((int) config('tutorial-video.render_timeout', 3300))
            ->path(base_path())
            ->run([
                config('tutorial-video.node_binary', 'node'),
                resource_path('scripts/tutorial-video-renderer.mjs'),
                $input,
                $directory,
                $output,
                base_path(),
                config('tutorial-video.hyperframes_version', '0.8.142'),
            ]);

        if (! $process->successful()) {
            throw new \RuntimeException(str($process->errorOutput().$process->output())->squish()->limit(1000, '…')->toString());
        }

        $result = json_decode((string) File::get($output), true, flags: JSON_THROW_ON_ERROR);

        return [
            'video' => (string) ($result['video'] ?? ''),
            'poster' => (string) ($result['poster'] ?? ''),
            'summary' => (string) ($result['summary'] ?? ''),
        ];
    }
}
