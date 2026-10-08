<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/** Démarre un unique travailleur dédié, caché sous Windows, pour les vidéos. */
class TutorialVideoWorkerLauncher
{
    private const CACHE_KEY = 'tutorial-video-worker';

    public function launch(bool $force = false): void
    {
        if ($force) {
            Cache::forget(self::CACHE_KEY);
        }

        if ($this->hasActiveWorker()) {
            return;
        }

        // Keep the short lock only while the detached process is being started.
        // A stale lock must never leave tutorial requests waiting for an hour.
        Cache::forget(self::CACHE_KEY);
        if (! Cache::add(self::CACHE_KEY, ['state' => 'starting'], now()->addMinutes(2))) {
            return;
        }

        $arguments = ['artisan', 'queue:work', 'database', '--queue=tutorial-videos', '--stop-when-empty', '--tries=1', '--timeout=3600'];

        try {
            if (DIRECTORY_SEPARATOR === '\\') {
                $process = new Process([
                    'powershell.exe', '-NoProfile', '-NonInteractive', '-Command',
                    '$worker = Start-Process -FilePath '.$this->ps(PHP_BINARY)
                    .' -ArgumentList '.$this->psArray($arguments)
                    .' -WorkingDirectory '.$this->ps(base_path())
                    .' -WindowStyle Hidden -PassThru; $worker.Id',
                ]);
                $process->mustRun();
                $pid = (int) trim($process->getOutput());
                if ($pid <= 0) {
                    throw new \RuntimeException('Le processus de rendu vidéo n’a pas retourné d’identifiant.');
                }

                Cache::put(self::CACHE_KEY, ['pid' => $pid], now()->addHour());

                return;
            }

            $process = new Process(array_merge([PHP_BINARY], $arguments), base_path());
            $process->start();
            Cache::put(self::CACHE_KEY, ['pid' => $process->getPid()], now()->addHour());
        } catch (\Throwable $exception) {
            Cache::forget(self::CACHE_KEY);
            Log::error('Impossible de démarrer le travailleur vidéo.', ['message' => $exception->getMessage()]);
        }
    }

    private function hasActiveWorker(): bool
    {
        $worker = Cache::get(self::CACHE_KEY);
        if (! is_array($worker) || ! isset($worker['pid']) || ! is_int($worker['pid'])) {
            return false;
        }

        if (DIRECTORY_SEPARATOR !== '\\') {
            return true;
        }

        $check = new Process(['tasklist.exe', '/FI', 'PID eq '.$worker['pid'], '/FO', 'CSV', '/NH']);
        $check->run();

        return $check->isSuccessful() && str_contains($check->getOutput(), '"'.$worker['pid'].'"');
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
