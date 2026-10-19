<?php

namespace App\Services;

use Symfony\Component\Process\Process;

/** Lance un travailleur éphémère pour que l'export fonctionne sans démon local. */
class BadgeExportWorkerLauncher
{
    public function launch(): void
    {
        $php = DIRECTORY_SEPARATOR === '\\'
            ? (is_file(dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'php.exe') ? dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'php.exe' : PHP_BINARY)
            : PHP_BINARY;
        $args = ['artisan', 'queue:work', 'badge_exports', '--queue=badge-exports', '--stop-when-empty', '--tries=1', '--timeout=3500', '--memory=1024'];

        if (DIRECTORY_SEPARATOR === '\\') {
            $quote = static fn (string $value): string => "'".str_replace("'", "''", $value)."'";
            $list = '@('.implode(', ', array_map($quote, $args)).')';
            (new Process([
                'powershell.exe', '-NoProfile', '-NonInteractive', '-Command',
                '$worker = Start-Process -FilePath '.$quote($php).' -ArgumentList '.$list
                .' -WorkingDirectory '.$quote(base_path()).' -WindowStyle Hidden -PassThru; $worker.Id',
            ]))->mustRun();

            return;
        }

        $command = 'nohup '.escapeshellarg($php).' '.implode(' ', array_map('escapeshellarg', $args)).' >/dev/null 2>&1 &';
        (new Process(['/bin/sh', '-c', $command], base_path()))->mustRun();
    }
}
