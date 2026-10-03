<?php

namespace App\Meetings;

use App\Models\EventSession;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Client LiveKit Egress (enregistrement RoomComposite → fichier MP4). Démarre et
 * arrête l'enregistrement d'une salle via l'API Egress (Twirp/JSON) du serveur
 * LiveKit, avec un jeton portant le droit `roomRecord`. Le fichier est déposé
 * dans un stockage objet compatible S3 configuré dans les réglages admin.
 *
 * L'identifiant d'egress renvoyé par « start » est mémorisé (cache) pour pouvoir
 * arrêter l'enregistrement plus tard.
 *
 * @phpstan-type EgressResult array{egressId?: string, message?: string}
 */
class LiveKitEgress
{
    /** L'enregistrement est-il exploitable ? (stockage S3 complet). */
    public function isConfigured(): bool
    {
        foreach (['s3_bucket', 's3_region', 's3_access_key', 's3_secret'] as $k) {
            if (trim((string) config("meetings.livekit.egress.$k")) === '') {
                return false;
            }
        }

        return true;
    }

    /** @return EgressResult */
    public function start(EventSession $session): array
    {
        $s3 = $this->s3Config();

        $body = [
            'room_name' => $session->meetingRoom(),
            'layout' => 'speaker',
            'file_outputs' => [[
                'file_type' => 'MP4',
                'filepath' => $this->filepath($session),
                's3' => $s3,
            ]],
        ];

        $info = $this->call('StartRoomCompositeEgress', $body);

        $egressId = (string) ($info['egress_id'] ?? $info['egressId'] ?? '');
        if ($egressId === '') {
            throw new \RuntimeException('Réponse Egress invalide (identifiant manquant).');
        }

        Cache::put($this->cacheKey($session), $egressId, now()->addHours(6));

        return ['egressId' => $egressId];
    }

    /** @return EgressResult */
    public function stop(EventSession $session): array
    {
        $egressId = (string) Cache::get($this->cacheKey($session), '');
        if ($egressId === '') {
            throw new \RuntimeException('Aucun enregistrement en cours pour cette session.');
        }

        $this->call('StopEgress', ['egress_id' => $egressId]);
        Cache::forget($this->cacheKey($session));

        return ['egressId' => $egressId];
    }

    /**
     * Appelle un endpoint Twirp de l'API Egress avec un jeton `roomRecord`.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function call(string $method, array $body): array
    {
        // ⚠️ Un délai, sinon un serveur d'enregistrement qui ne répond plus
        // immobilise le worker PHP sans limite - et c'est le modérateur qui
        // attend, en pleine séance. Plus large que l'interrogation de salle
        // (5 s) : démarrer un enregistrement demande au service de se préparer.
        $response = Http::withToken($this->token())
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->post($this->apiBase().'/twirp/livekit.Egress/'.$method, $body);

        if ($response->failed()) {
            throw new \RuntimeException('LiveKit Egress a répondu '.$response->status().' : '.Str::limit($response->body(), 200));
        }

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        return $data;
    }

    /** Jeton d'accès (JWT HS256) portant le droit d'enregistrer (`roomRecord`). */
    private function token(): string
    {
        $key = (string) config('meetings.livekit.api_key');
        $secret = (string) config('meetings.livekit.api_secret');
        if ($key === '' || $secret === '') {
            throw new \RuntimeException('Identifiants LiveKit manquants.');
        }

        $now = time();

        return JWT::encode([
            'iss' => $key,
            'sub' => 'egress',
            'nbf' => $now,
            'iat' => $now,
            'exp' => $now + 600,
            'video' => ['roomRecord' => true],
        ], $secret, 'HS256');
    }

    /**
     * Destination de stockage S3 (validée). Lève une exception si incomplète.
     *
     * @return array<string, string>
     */
    private function s3Config(): array
    {
        $get = fn (string $k): string => trim((string) config("meetings.livekit.egress.$k"));

        $s3 = [
            'access_key' => $get('s3_access_key'),
            'secret' => $get('s3_secret'),
            'region' => $get('s3_region'),
            'bucket' => $get('s3_bucket'),
        ];

        foreach ($s3 as $value) {
            if ($value === '') {
                throw new \RuntimeException('Stockage d’enregistrement non configuré (voir Réglages › Visio).');
            }
        }

        if (($endpoint = $get('s3_endpoint')) !== '') {
            $s3['endpoint'] = $endpoint;
        }

        return $s3;
    }

    /** Base HTTP de l'API LiveKit, dérivée de l'URL WebSocket (wss→https). */
    private function apiBase(): string
    {
        $url = trim((string) config('meetings.livekit.url'));
        if ($url === '') {
            throw new \RuntimeException('URL du serveur LiveKit manquante.');
        }

        return rtrim(Str::replaceStart('ws', 'http', $url), '/');
    }

    private function filepath(EventSession $session): string
    {
        return 'gesco/'.$session->meetingRoom().'/'.now()->format('Ymd-His').'.mp4';
    }

    private function cacheKey(EventSession $session): string
    {
        return 'livekit-egress:'.$session->getKey();
    }
}
