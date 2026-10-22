<?php

namespace App\Support;

use App\Models\Event;
use App\Models\ProjectTask;
use App\Models\ProjectTaskDocument;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/** Accès serveur à DocSpace. La clé API ne quitte jamais PHP. */
class TaskDocSpace
{
    /** Plafond d'un fichier de travail, à l'import comme au téléchargement. */
    public const MAX_BYTES = 20 * 1024 * 1024;

    private const EDIT_ACCESS = 10;

    public function configured(?Event $event = null): bool
    {
        $url = (string) config('services.docspace.url');

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && filled(config('services.docspace.api_key'))
            && ctype_digit($this->roomId($event));
    }

    public function roomId(?Event $event = null): string
    {
        return (string) ($event?->settings['docspace']['room_id'] ?? config('services.docspace.room_id'));
    }

    public function fileLinksEnabled(): bool
    {
        return filter_var(config('services.docspace.file_links_enabled'), FILTER_VALIDATE_BOOLEAN);
    }

    public function url(): string
    {
        return rtrim((string) config('services.docspace.url'), '/');
    }

    public function scriptUrl(): string
    {
        return $this->url().'/static/scripts/sdk/2.2.0/api.js';
    }

    /** Identifiant du dossier de la tâche. Le partage d'édition porte sur chaque fichier. */
    public function taskFolder(ProjectTask $task): string
    {
        $folderId = (string) ($task->docspace_folder_id ?? '');
        if ($folderId === '') {
            $parentId = $this->roomId($task->event);
            $folder = $this->json('post', '/api/2.0/files/folder/'.$parentId, [
                'title' => 'GEVES - tâche '.$task->id.' - '.mb_substr($task->title, 0, 100),
            ]);
            $folderId = (string) ($folder['id'] ?? '');
            if (! ctype_digit($folderId)) {
                throw new RuntimeException('DocSpace n’a pas confirmé la création du dossier de la tâche.');
            }
            $task->forceFill(['docspace_folder_id' => $folderId])->save();
        }

        return $folderId;
    }

    /** Crée un lien de fichier utilisable par le mode Editor du SDK. */
    public function createFileEditLink(string $fileId): string
    {
        $this->requireFileLinksEnabled();
        if (! ctype_digit($fileId)) {
            throw new RuntimeException('Le fichier DocSpace est introuvable.');
        }
        $path = '/api/2.0/files/file/'.$fileId;
        $share = $this->json('post', $path.'/link', [
            'access' => self::EDIT_ACCESS,
            'internal' => false,
            'denyDownload' => false,
            'title' => 'Édition GEVES - fichier '.$fileId,
        ]);
        if ((int) ($share['access'] ?? -1) !== self::EDIT_ACCESS) {
            throw new RuntimeException('Le lien principal de ce fichier n’autorise pas l’édition dans DocSpace.');
        }
        $token = $this->linkToken($share);
        $this->verifyFileLink($fileId, $token);

        return $token;
    }

    /** Répare les documents créés avec l'ancien lien de dossier. */
    public function fileEditToken(ProjectTaskDocument $document): string
    {
        $token = (string) ($document->docspace_request_token ?? '');
        if ($token !== '') {
            return $token;
        }
        $token = $this->createFileEditLink((string) $document->docspace_file_id);
        $document->forceFill(['docspace_request_token' => $token])->save();

        return $token;
    }

    /** Le nouveau lien doit fonctionner avant que l'ancien soit révoqué. */
    public function rotateFileLink(ProjectTaskDocument $document): void
    {
        $this->requireFileLinksEnabled();
        $fileId = (string) $document->docspace_file_id;
        $oldToken = $this->fileEditToken($document);
        $path = '/api/2.0/files/file/'.$fileId;
        $links = $this->json('get', $path.'/links');
        if (! is_array($links)) {
            throw new RuntimeException('Impossible de lire les liens du fichier dans DocSpace.');
        }
        $oldId = null;
        foreach ($links as $entry) {
            $lu = $this->linkEntry(is_array($entry) ? $entry : []);
            if ($lu['token'] === $oldToken) {
                $oldId = $lu['id'];
                break;
            }
        }
        if (! $oldId) {
            throw new RuntimeException('Le lien actuel est introuvable dans DocSpace. Il n’a pas été révoqué.');
        }
        $newId = (string) Str::uuid();
        $share = $this->json('put', $path.'/links', [
            'linkId' => $newId,
            'access' => self::EDIT_ACCESS,
            'internal' => false,
            'denyDownload' => false,
            'primary' => false,
            'title' => 'Édition GEVES - fichier '.$fileId,
        ]);
        if ((int) ($share['access'] ?? -1) !== self::EDIT_ACCESS) {
            throw new RuntimeException('DocSpace n’a pas créé le nouveau lien d’édition. L’ancien reste actif.');
        }
        $newToken = $this->linkToken($share);
        $this->verifyFileLink($fileId, $newToken);
        $document->forceFill(['docspace_request_token' => $newToken])->save();
        $this->json('put', $path.'/links', ['linkId' => $oldId, 'access' => 0]);
    }

    /** Supprime l'ancien lien de dossier une fois tous ses fichiers migrés. */
    public function revokeLegacyTaskLink(ProjectTask $task): void
    {
        $this->requireFileLinksEnabled();
        $token = (string) ($task->docspace_request_token ?? '');
        if ($token === '') {
            return;
        }
        $folderId = (string) ($task->docspace_folder_id ?? '');
        $links = $this->json('get', '/api/2.0/files/folder/'.$folderId.'/links');
        if (! is_array($links)) {
            throw new RuntimeException('Impossible de lire les anciens liens du dossier DocSpace.');
        }
        foreach ($links as $entry) {
            $lu = $this->linkEntry(is_array($entry) ? $entry : []);
            if ($lu['token'] === $token && $lu['id'] !== '') {
                $this->json('put', '/api/2.0/files/folder/'.$folderId.'/links', [
                    'linkId' => $lu['id'], 'access' => 0,
                ]);
                break;
            }
        }
        $task->forceFill(['docspace_request_token' => null])->save();
    }

    /**
     * Le fichier importé correspond-il vraiment à son format annoncé ?
     *
     * ⚠️ L'extension et le type MIME annoncés viennent du navigateur : seul le
     * contenu dit ce qu'il est. On ouvre donc l'archive et on y cherche la pièce
     * propre au format, après avoir borné son nombre d'entrées et la taille
     * qu'elle réclame une fois décompressée - une archive minuscule qui se
     * déplie en centaines de mégaoctets remplirait le disque du prestataire.
     */
    public function validFile(string $path, string $format): bool
    {
        if ($format === 'txt') {
            $content = file_get_contents($path);

            return $content !== false
                && mb_check_encoding($content, 'UTF-8')
                && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $content) === 0;
        }

        if (! in_array($format, ['docx', 'xlsx', 'pptx'], true)) {
            return false;
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return false;
        }

        try {
            if ($zip->numFiles > 5000) {
                return false;
            }
            $expanded = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $expanded += (int) ($zip->statIndex($index)['size'] ?? 0);
                if ($expanded > 200 * 1024 * 1024) {
                    return false;
                }
            }

            $entry = match ($format) {
                'xlsx' => 'xl/workbook.xml',
                'pptx' => 'ppt/presentation.xml',
                default => 'word/document.xml',
            };

            return $zip->locateName('[Content_Types].xml') !== false
                && $zip->locateName($entry) !== false;
        } finally {
            $zip->close();
        }
    }

    private function linkToken(array $share): string
    {
        $token = $this->linkEntry($share)['token'];
        if ($token === '') {
            throw new RuntimeException('DocSpace n’a pas fourni le jeton du lien d’édition.');
        }

        return $token;
    }

    /**
     * Identifiant et jeton d'un lien, quelle que soit la forme de la réponse.
     *
     * ⚠️⚠️ **LA FORME EXACTE N'EST PAS CONFIRMÉE EN SITUATION RÉELLE**, et une
     * seule lecture en dur nous ferait échouer sur « DocSpace n'a pas fourni le
     * jeton » sans dire pourquoi. Quatre formes sont donc acceptées : le jeton
     * sous `sharedLink`, sous `sharedTo`, à la racine, ou à défaut le paramètre
     * `share` de l'URL de partage - `sharedTo.shareLink` est tantôt un objet,
     * tantôt la CHAÎNE de l'adresse, et indexer une chaîne par un nom ne rend
     * rien. Toute forme inconnue échoue FERMÉE : on ne devine pas un jeton.
     *
     * @param  array<string, mixed>  $entry
     * @return array{id: string, token: string}
     */
    private function linkEntry(array $entry): array
    {
        $lien = $entry['sharedLink'] ?? $entry['sharedTo'] ?? [];
        $lien = is_array($lien) ? $lien : [];

        $token = (string) ($lien['requestToken'] ?? $entry['requestToken'] ?? '');
        if ($token === '') {
            $adresse = $lien['shareLink'] ?? $entry['shareLink'] ?? null;
            if (is_string($adresse) && $adresse !== '') {
                parse_str((string) parse_url($adresse, PHP_URL_QUERY), $parametres);
                $token = is_string($parametres['share'] ?? null) ? $parametres['share'] : '';
            }
        }

        return [
            'id' => (string) ($lien['id'] ?? $entry['id'] ?? ''),
            'token' => $token,
        ];
    }

    /** Un dossier de tâche part avec ses fichiers et tous leurs liens. */
    public function deleteFolder(string $folderId): void
    {
        $this->json('delete', '/api/2.0/files/folder/'.$folderId, [
            'deleteAfter' => true, 'immediately' => true,
        ]);
    }

    /** Suivre le titre de GEVES, pour qu'on retrouve le fichier chez le prestataire. */
    public function renameFile(string $fileId, string $title): void
    {
        $this->json('put', '/api/2.0/files/file/'.$fileId, ['title' => mb_substr($title, 0, 160)]);
    }

    private function requireFileLinksEnabled(): void
    {
        if (! $this->fileLinksEnabled()) {
            throw new RuntimeException('La création de liens d’édition DocSpace doit être activée par l’administrateur.');
        }
    }

    private function verifyFileLink(string $fileId, string $token): void
    {
        $url = $this->url().'/api/2.0/files/share/'.rawurlencode($token);
        try {
            $response = Http::acceptJson()->timeout(20)->withOptions(['allow_redirects' => false])
                ->get($url, ['fileId' => $fileId]);
        } catch (ConnectionException $error) {
            throw new RuntimeException('Impossible de vérifier le nouveau lien auprès de DocSpace.', 0, $error);
        }
        $result = $response->json('response');
        if (! $response->successful() || ! is_array($result)
            || (int) ($result['status'] ?? -1) !== 0
            || (string) ($result['id'] ?? '') !== $fileId) {
            throw new RuntimeException('Le nouveau lien DocSpace est invalide. L’ancien lien n’a pas été révoqué.');
        }
    }

    public function createFile(string $folderId, string $title, string $format): string
    {
        if ($format === 'txt') {
            $file = $this->json('post', '/api/2.0/files/'.$folderId.'/text', [
                'title' => mb_substr($title, 0, 160).'.txt',
                'content' => "\n",
                'createNewIfExist' => false,
            ]);

            return $this->fileId($file);
        }

        $file = $this->json('post', '/api/2.0/files/'.$folderId.'/file', [
            'title' => mb_substr($title, 0, 160).'.'.$format,
        ]);

        return $this->fileId($file);
    }

    public function uploadFile(string $folderId, string $path, string $title): string
    {
        $size = filesize($path);
        if ($size === false) {
            throw new RuntimeException('Impossible de lire le fichier à importer.');
        }
        if ($size > 10 * 1024 * 1024) {
            return $this->uploadLargeFile($folderId, $path, $title, $size);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Impossible de lire le fichier à importer.');
        }
        try {
            $response = $this->client()->attach('file', $handle, $title)
                ->post($this->url().'/api/2.0/files/'.$folderId.'/upload?createNewIfExist=true');
            $files = $this->unwrap($response);
        } finally {
            fclose($handle);
        }

        return $this->fileId(is_array($files) ? ($files[0] ?? []) : []);
    }

    public function downloadUrl(string $fileId): string
    {
        $url = $this->json('get', '/api/2.0/files/file/'.$fileId.'/presigneduri');
        if (! is_string($url) || ! str_starts_with($url, $this->url().'/')) {
            throw new RuntimeException('DocSpace a renvoyé une adresse de téléchargement inattendue.');
        }

        return $url;
    }

    public function downloadFile(string $fileId): string
    {
        // Sur certaines salles DocSpace, le lien présigné exige encore la clé
        // API lors du téléchargement. Le lien est validé sur notre hôte avant
        // d'y envoyer ce secret ; aucune redirection n'est suivie.
        $response = Http::withToken((string) config('services.docspace.api_key'))
            ->timeout(90)
            ->withOptions(['allow_redirects' => false])
            ->get($this->downloadUrl($fileId));
        if (! $response->successful() || strlen($response->body()) > self::MAX_BYTES) {
            throw new RuntimeException('Impossible de télécharger le fichier depuis DocSpace.');
        }

        return $response->body();
    }

    public function deleteFile(string $fileId): void
    {
        $this->json('delete', '/api/2.0/files/file/'.$fileId, ['deleteAfter' => true]);
    }

    private function uploadLargeFile(string $folderId, string $path, string $title, int $size): string
    {
        $response = $this->client()->post($this->url().'/api/2.0/files/'.$folderId.'/upload/create_session', [
            'CreateOn' => now()->toIso8601String(),
            'FileName' => $title,
            'FileSize' => $size,
            'folderId' => (int) $folderId,
        ]);
        if (! $response->successful()) {
            throw new RuntimeException('DocSpace a refusé l’import volumineux (HTTP '.$response->status().').');
        }
        $payload = (array) ($response->json() ?? []);
        $session = $payload['data'] ?? $payload['response'] ?? [];
        $location = (string) ($session['location'] ?? '');
        if (! str_starts_with($location, $this->url().'/')) {
            throw new RuntimeException('DocSpace a renvoyé une adresse de transfert inattendue.');
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Impossible de lire le fichier à importer.');
        }
        try {
            $result = [];
            while (! feof($handle)) {
                $chunk = fread($handle, 5 * 1024 * 1024);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $response = $this->client()->attach('file', $chunk, $title)->post($location);
                if (! $response->successful()) {
                    throw new RuntimeException('DocSpace a interrompu l’import du fichier volumineux.');
                }
                $result = (array) ($response->json() ?? []);
            }
        } finally {
            fclose($handle);
        }
        $file = $result['response'] ?? $result['data'] ?? $result['file'] ?? $result;
        if (is_array($file) && isset($file['file'])) {
            $file = $file['file'];
        }

        return $this->fileId(is_array($file) ? $file : []);
    }

    private function json(string $method, string $path, ?array $body = null): mixed
    {
        try {
            $response = $this->client()->send(strtoupper($method), $this->url().$path, $body === null ? [] : ['json' => $body]);
        } catch (ConnectionException $error) {
            throw new RuntimeException('DocSpace est momentanément inaccessible.', 0, $error);
        }

        return $this->unwrap($response);
    }

    private function unwrap(Response $response): mixed
    {
        if (! $response->successful()) {
            throw new RuntimeException('DocSpace a refusé l’opération (HTTP '.$response->status().').');
        }
        $json = $response->json();
        if (! is_array($json) || ! array_key_exists('response', $json)) {
            throw new RuntimeException('Réponse inattendue de DocSpace.');
        }

        return $json['response'];
    }

    private function fileId(array $file): string
    {
        $id = (string) ($file['id'] ?? '');
        if (! ctype_digit($id)) {
            throw new RuntimeException('DocSpace n’a pas fourni l’identifiant du document.');
        }

        return $id;
    }

    private function client(): PendingRequest
    {
        $url = $this->url();
        if (filter_var($url, FILTER_VALIDATE_URL) === false
            || parse_url($url, PHP_URL_SCHEME) !== 'https'
            || blank(config('services.docspace.api_key'))) {
            throw new RuntimeException('Configurez l’URL et la clé API DocSpace dans les réglages.');
        }

        return Http::withToken((string) config('services.docspace.api_key'))
            ->acceptJson()->timeout(90)->withOptions(['allow_redirects' => false]);
    }
}
