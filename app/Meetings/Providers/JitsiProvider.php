<?php

namespace App\Meetings\Providers;

use App\Enums\MeetingRole;
use App\Meetings\Contracts\MeetingProvider;
use App\Models\EventSession;
use Firebase\JWT\JWT;

/**
 * Jitsi Meet - visio interactive open-source, embarquable. Deux modes :
 *
 * - Public (`meet.jit.si`, sans secret JWT) : l'URL porte le nom d'affichage ;
 *   le rôle n'est pas imposé côté serveur (premier arrivé = modérateur Jitsi).
 * - Self-host avec JWT (`JITSI_APP_ID`/`JITSI_APP_SECRET`) : un jeton signé
 *   matérialise le rôle (modérateur vs invité) et le nom, imposés par le serveur.
 *
 * Réutilise firebase/php-jwt (déjà présent pour CashPay).
 */
class JitsiProvider implements MeetingProvider
{
    public function key(): string
    {
        return 'jitsi';
    }

    public function label(): string
    {
        return 'Jitsi Meet';
    }

    /** Toujours utilisable : le mode public ne requiert aucune configuration. */
    public function isAvailable(): bool
    {
        return true;
    }

    /** Jitsi s'embarque en iframe. */
    public function embeddable(): bool
    {
        return true;
    }

    /** Le mode JWT (rôles imposés) est actif si un secret est configuré. */
    public function usesJwt(): bool
    {
        return filled(config('meetings.jitsi.app_secret')) && filled(config('meetings.jitsi.app_id'));
    }

    /**
     * Paramètres pour l'IFrame API officielle (JitsiMeetExternalAPI) : domaine,
     * salle, jeton (self-host) et nom d'affichage. Méthode d'intégration
     * recommandée par le handbook Jitsi (au lieu d'une iframe brute).
     *
     * @return array{domain: string, roomName: string, jwt: ?string, userName: string}
     */
    public function embedConfig(EventSession $session, MeetingRole $role, ?string $displayName = null): array
    {
        return [
            'domain' => trim((string) config('meetings.jitsi.domain', 'meet.jit.si'), '/'),
            'roomName' => $session->meetingRoom(),
            'jwt' => $this->usesJwt() ? $this->jwt($session, $role, $displayName) : null,
            'userName' => $displayName ?: 'Invité',
        ];
    }

    public function joinUrl(EventSession $session, MeetingRole $role, ?string $displayName = null): string
    {
        $domain = trim((string) config('meetings.jitsi.domain', 'meet.jit.si'), '/');
        $room = rawurlencode($session->meetingRoom());
        $base = "https://{$domain}/{$room}";

        if ($this->usesJwt()) {
            return $base.'?jwt='.$this->jwt($session, $role, $displayName);
        }

        // Mode public : le nom d'affichage passe par le fragment de config Jitsi.
        if (filled($displayName)) {
            return $base.'#userInfo.displayName='.rawurlencode('"'.$displayName.'"');
        }

        return $base;
    }

    /** Jeton JWT Jitsi (self-host) matérialisant rôle + identité. */
    private function jwt(EventSession $session, MeetingRole $role, ?string $displayName): string
    {
        $now = time();
        $secret = (string) config('meetings.jitsi.app_secret');
        $appId = (string) config('meetings.jitsi.app_id');
        $ttl = (int) config('meetings.jitsi.jwt_ttl', 7200);

        $payload = [
            'aud' => 'jitsi',
            'iss' => $appId,
            'sub' => trim((string) config('meetings.jitsi.domain', 'meet.jit.si'), '/'),
            'room' => $session->meetingRoom(),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'context' => [
                'user' => [
                    'name' => $displayName ?: 'Invité',
                    'moderator' => $role->isModerator(),
                ],
            ],
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }
}
