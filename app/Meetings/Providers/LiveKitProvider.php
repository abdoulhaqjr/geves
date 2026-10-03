<?php

namespace App\Meetings\Providers;

use App\Enums\MeetingRole;
use App\Meetings\Contracts\Meetable;
use App\Meetings\Contracts\MeetingProvider;
use App\Meetings\LiveKitRoom;
use Firebase\JWT\JWT;

/**
 * LiveKit - SFU WebRTC (Cloud ou self-host). L'accès à une salle se fait avec un
 * **token d'accès** (JWT signé avec le secret d'API) encodant l'identité, la
 * salle et les droits (publier / s'abonner / admin) - ce qui matérialise
 * modérateur / présentateur / participant côté serveur.
 *
 * L'affichage passe par l'app LiveKit Meet (paramètres `liveKitUrl` + `token`).
 * Non embarquable par défaut (l'app publique bloque l'iframe) → nouvel onglet ;
 * une app self-host peut être intégrée ensuite. Réutilise firebase/php-jwt.
 */
class LiveKitProvider implements MeetingProvider
{
    public function key(): string
    {
        return 'livekit';
    }

    public function label(): string
    {
        return 'LiveKit';
    }

    /** Requiert un serveur + des identifiants d'API (pas de mode public). */
    public function isAvailable(): bool
    {
        return filled(config('meetings.livekit.url'))
            && filled(config('meetings.livekit.api_key'))
            && filled(config('meetings.livekit.api_secret'));
    }

    /** Intégré via notre lecteur maison (SDK livekit-client), aux couleurs UK. */
    public function embeddable(): bool
    {
        return true;
    }

    public function joinUrl(Meetable $session, MeetingRole $role, ?string $displayName = null): string
    {
        $meet = rtrim((string) config('meetings.livekit.meet_url', 'https://meet.livekit.io/custom'), '/');
        $token = $this->token($session, $role, $displayName);

        return $meet.'?'.http_build_query([
            'liveKitUrl' => (string) config('meetings.livekit.url'),
            'token' => $token,
        ]);
    }

    /**
     * Token d'accès LiveKit (JWT HS256) avec les droits dérivés du rôle.
     *
     * `$participantId` est encodé dans l'**identité** de la connexion, ce qui
     * permet ensuite de reconnaître qui est dans la salle ({@see LiveKitRoom}).
     */
    public function token(Meetable $session, MeetingRole $role, ?string $displayName = null, ?int $participantId = null): string
    {
        $now = time();
        $key = (string) config('meetings.livekit.api_key');
        $secret = (string) config('meetings.livekit.api_secret');
        $ttl = (int) config('meetings.livekit.token_ttl', 7200);

        $name = $displayName ?: 'Invité';
        // Identité unique par connexion (LiveKit refuse les doublons d'identité),
        // porteuse de l'inscription quand on la connaît.
        $identity = LiveKitRoom::identityFor($participantId, $name);

        // Présentateur et modérateur peuvent diffuser ; le public s'abonne (écoute/
        // regarde). Seul le modérateur administre la salle.
        $canPublish = in_array($role, [MeetingRole::Moderator, MeetingRole::Presenter], true);

        $payload = [
            'iss' => $key,
            'sub' => $identity,
            'name' => $name,
            'nbf' => $now,
            'iat' => $now,
            'exp' => $now + $ttl,
            'video' => [
                'room' => $session->meetingRoom(),
                'roomJoin' => true,
                'canPublish' => $canPublish,
                'canSubscribe' => true,
                'canPublishData' => true,
                'roomAdmin' => $role->isModerator(),
            ],
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }
}
