<?php

namespace App\Documents;

use App\Models\Event;
use App\Support\TaskDocSpace;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;
use Symfony\Component\Process\Process;

/** Couverture DOCX conçue dans DocSpace, propre à une édition. */
final class LivretCover
{
    public static function fileId(Event $event): string
    {
        return self::active($event) ? self::storedFileId($event) : '';
    }

    public static function storedFileId(Event $event): string
    {
        return (string) ($event->settings['doc']['livret.cover_docspace_file_id'] ?? '');
    }

    public static function active(Event $event): bool
    {
        // Les couvertures créées avant l'ajout de cette bascule restent actives.
        return filter_var($event->settings['doc']['livret.cover_docspace_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
    }

    public static function token(Event $event): string
    {
        $encrypted = (string) ($event->settings['doc']['livret.cover_docspace_token'] ?? '');

        return $encrypted === '' ? '' : Crypt::decryptString($encrypted);
    }

    public static function encryptedToken(string $token): string
    {
        return Crypt::encryptString($token);
    }

    /** Garde la première page DocSpace et les pages 2+ du PDF du livret. */
    public static function pdf(Event $event, string $bookPdf, TaskDocSpace $docspace): string
    {
        $coverFileId = self::fileId($event);
        if ($coverFileId === '') {
            return $bookPdf;
        }

        $convertedId = $docspace->saveAsPdf($coverFileId, $docspace->roomId($event));
        try {
            $coverPdf = $docspace->downloadFile($convertedId);
        } finally {
            $docspace->deleteFile($convertedId);
        }

        $paths = [];
        try {
            foreach (['cover', 'book', 'merged'] as $part) {
                $path = tempnam(sys_get_temp_dir(), 'livret-'.$part.'-');
                if ($path === false) {
                    throw new RuntimeException('Impossible de préparer la fusion PDF du livret.');
                }
                $paths[$part] = $path;
            }
            if (file_put_contents($paths['cover'], $coverPdf) !== strlen($coverPdf)
                || file_put_contents($paths['book'], $bookPdf) !== strlen($bookPdf)) {
                throw new RuntimeException('Impossible de préparer les fichiers PDF du livret.');
            }

            // Le PDF DocSpace contient des références compressées que le
            // parseur gratuit de FPDI ne sait pas lire. pdf-lib les accepte et
            // recopie les pages sans les transformer en images.
            $process = new Process([
                (string) config('services.livret_pdf_node_binary', 'node'),
                base_path('scripts/merge-livret-cover.mjs'),
                $paths['cover'],
                $paths['book'],
                $paths['merged'],
                'Livre des résumés - '.$event->title,
                (string) config('app.name', 'GEVES').' - Université de Kara',
            ]);
            $process->setTimeout(180);
            $process->run();
            if (! $process->isSuccessful()) {
                throw new RuntimeException('Impossible de fusionner la couverture DocSpace avec le livre PDF. '.trim($process->getErrorOutput()));
            }

            $binary = file_get_contents($paths['merged']);
            if ($binary === false || ! str_starts_with($binary, '%PDF-')) {
                throw new RuntimeException('Le livre PDF fusionné est invalide.');
            }

            return $binary;
        } finally {
            foreach ($paths as $path) {
                @unlink($path);
            }
        }
    }
}
