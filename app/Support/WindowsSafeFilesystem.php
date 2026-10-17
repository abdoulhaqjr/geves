<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/** Avoid concurrent Blade cache replacements failing with Windows sharing errors. */
class WindowsSafeFilesystem extends Filesystem
{
    public function replace($path, $content, $mode = null)
    {
        $viewDirectory = rtrim(str_replace('\\', '/', storage_path('framework/views')), '/').'/';
        if (! str_starts_with(str_replace('\\', '/', $path), $viewDirectory)) {
            parent::replace($path, $content, $mode);

            return;
        }

        clearstatcache(true, $path);
        $path = realpath($path) ?: $path;
        $lock = @fopen($path.'.lock', 'c');

        if ($lock === false) {
            throw new RuntimeException("Impossible de verrouiller le fichier à remplacer : {$path}");
        }

        $tempPath = null;

        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException("Impossible de verrouiller le fichier à remplacer : {$path}");
            }

            $contentHash = hash('xxh128', $content);
            if ($this->hasSameContent($path, $contentHash)) {
                return;
            }

            $tempPath = tempnam(dirname($path), basename($path));
            if ($tempPath === false) {
                throw new RuntimeException("Impossible de créer un fichier temporaire pour {$path}");
            }

            @chmod($tempPath, $mode ?? (0777 - umask()));
            if (file_put_contents($tempPath, $content) === false) {
                throw new RuntimeException("Impossible d'écrire le fichier temporaire pour {$path}");
            }

            for ($attempt = 0; $attempt < 20; $attempt++) {
                if (@rename($tempPath, $path)) {
                    return;
                }

                clearstatcache(true, $path);
                if ($this->hasSameContent($path, $contentHash)) {
                    return;
                }

                usleep(min(25_000 * ($attempt + 1), 200_000));
            }

            throw new RuntimeException("Impossible de remplacer {$path} après plusieurs tentatives.");
        } finally {
            if ($tempPath !== null && is_file($tempPath)) {
                @unlink($tempPath);
            }

            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * ⚠️ **Impure, et c'est tout l'intérêt** : elle lit le DISQUE, que les autres
     * processus réécrivent entre deux appels - c'est précisément ce que la boucle
     * de reprise attend (d'où le `clearstatcache` qui la précède) - et elle
     * repousse l'horodatage du fichier au passage. Sans cette annotation,
     * l'analyse statique retient le `false` du premier appel et tient le second
     * pour toujours faux : la reprise paraîtrait morte alors qu'elle est la seule
     * sortie quand un autre processus a déjà écrit la vue compilée.
     *
     * @phpstan-impure
     */
    private function hasSameContent(string $path, string $contentHash): bool
    {
        if (! is_file($path) || @hash_file('xxh128', $path) !== $contentHash) {
            return false;
        }

        // Blade considers equal timestamps expired; avoid recompiling the same view.
        @touch($path, time() + 1);

        return true;
    }
}
