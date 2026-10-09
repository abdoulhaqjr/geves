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

        // Le format appartient à l'infrastructure, jamais à la demande envoyée
        // par le navigateur. Les captures sont nativement en Full HD.
        $brief['render'] = [
            'width' => (int) config('tutorial-video.render_width', 1920),
            'height' => (int) config('tutorial-video.render_height', 1080),
        ];

        File::put($input, json_encode($brief, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $ffmpeg = $this->ffmpegBinary();
        $ffprobe = $this->ffprobeBinary($ffmpeg);
        $process = Process::timeout((int) config('tutorial-video.render_timeout', 3300))
            ->path(base_path())
            ->env([
                'FFMPEG_BINARY' => $ffmpeg,
                'HYPERFRAMES_FFMPEG_PATH' => $ffmpeg,
                'HYPERFRAMES_FFPROBE_PATH' => $ffprobe,
            ])
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

    /** Résout FFmpeg même lorsque le worker Windows n’hérite pas du PATH utilisateur. */
    private function ffmpegBinary(): string
    {
        $configured = config('tutorial-video.ffmpeg_binary');
        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $localAppData = getenv('LOCALAPPDATA');
            if (is_string($localAppData) && $localAppData !== '') {
                $candidates = glob($localAppData.DIRECTORY_SEPARATOR.'Microsoft'.DIRECTORY_SEPARATOR.'WinGet'.DIRECTORY_SEPARATOR.'Packages'.DIRECTORY_SEPARATOR.'Gyan.FFmpeg_*'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'ffmpeg.exe') ?: [];
                if ($candidates !== []) {
                    rsort($candidates, SORT_NATURAL);

                    return $candidates[0];
                }
            }
        }

        return 'ffmpeg';
    }

    /** FFprobe accompagne FFmpeg ; Hyperframes le requiert pour vérifier les médias. */
    private function ffprobeBinary(string $ffmpeg): string
    {
        $configured = config('tutorial-video.ffprobe_binary');
        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $sibling = dirname($ffmpeg).DIRECTORY_SEPARATOR.(DIRECTORY_SEPARATOR === '\\' ? 'ffprobe.exe' : 'ffprobe');
        if (is_file($sibling)) {
            return $sibling;
        }

        return 'ffprobe';
    }
}
