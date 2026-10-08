<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/** Démarre un unique travailleur dédié, caché sous Windows, pour les vidéos. */
class TutorialVideoWorkerLauncher
{
    public function launch(bool $force = false): void
    {
        if ($force) {
            Cache::forget('tutorial-video-worker');
        }

        if (! Cache::add('tutorial-video-worker', true, now()->addHour())) {
            return;
        }

        $arguments = ['artisan', 'queue:work', 'database', '--queue=tutorial-videos', '--stop-when-empty', '--tries=1', '--timeout=3600'];

        try {
            if (DIRECTORY_SEPARATOR === '\\') {
                $process = new Process([
                    'powershell.exe', '-NoProfile', '-NonInteractive', '-Command',
                    'Start-Process -FilePath '.$this->ps(PHP_BINARY)
                    .' -ArgumentList '.$this->psArray($arguments)
                    .' -WorkingDirectory '.$this->ps(base_path())
                    .' -WindowStyle Hidden',
                ]);
                $process->mustRun();

                return;
            }

            (new Process(array_merge([PHP_BINARY], $arguments), base_path()))->start();
        } catch (\Throwable $exception) {
            Cache::forget('tutorial-video-worker');
            Log::error('Impossible de démarrer le travailleur vidéo.', ['message' => $exception->getMessage()]);
        }
    }

    private function ps(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    /** @param array<int, string> $values */
    private function psArray(array $values): string
    {
        return '@('.implode(', ', array_map($this->ps(...), $values)).')';
    }
}
