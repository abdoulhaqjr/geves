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

        // Le processus lit directement les demandes persistées. Il ne dépend donc
        // pas d'un démon de queue externe sur le poste de l'administrateur.
        $arguments = ['artisan', 'tutorial-videos:render-pending', '--limit=20'];
        $php = $this->phpCli();

        try {
            if (DIRECTORY_SEPARATOR === '\\') {
                $process = new Process([
                    'powershell.exe', '-NoProfile', '-NonInteractive', '-Command',
                    '$worker = Start-Process -FilePath '.$this->ps($php)
                    .' -ArgumentList '.$this->psArray($arguments)
                    .' -WorkingDirectory '.$this->ps(base_path())
                    .' -WindowStyle Hidden -PassThru; $worker.Id',
                ]);
                $process->mustRun();
                $pid = (int) trim($process->getOutput());
                if ($pid <= 0) {
                    throw new \RuntimeException('Le processus de rendu vidéo n’a pas retourné d’identifiant.');
                }

                // Depuis PHP-FPM, PHP_BINARY peut désigner php-cgi.exe. Le PID de
                // php-cgi existe brièvement mais le rendu ne démarre jamais. Vérifier
                // la ligne de commande du véritable worker avant de le mémoriser.
                usleep(350000);
                if (! $this->isWorkerProcess($pid)) {
                    throw new \RuntimeException('Le processus de rendu vidéo a quitté avant son démarrage.');
                }

                Cache::put(self::CACHE_KEY, ['pid' => $pid], now()->addHour());

                return;
            }

            $process = new Process(array_merge([$php], $arguments), base_path());
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

        return $this->isWorkerProcess($worker['pid']);
    }

    private function isWorkerProcess(int $pid): bool
    {
        $check = new Process([
            'powershell.exe', '-NoProfile', '-NonInteractive', '-Command',
            '(Get-CimInstance Win32_Process -Filter "ProcessId = '.$pid.'").CommandLine',
        ]);
        $check->run();

        return $check->isSuccessful() && str_contains($check->getOutput(), 'tutorial-videos:render-pending');
    }

    /** Résout php.exe depuis php-cgi.exe lorsque la requête vient de PHP-FPM. */
    private function phpCli(): string
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            return PHP_BINARY;
        }

        $cli = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'php.exe';

        return is_file($cli) ? $cli : PHP_BINARY;
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
