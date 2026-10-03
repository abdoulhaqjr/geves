<?php

namespace App\Meetings;

use App\Meetings\Contracts\Meetable;
use App\Support\SessionPresence;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Client LiveKit RoomService : **qui est réellement connecté** à la salle d'une
 * séance, maintenant.
 *
 * ⚠️ C'est la seule source d'occupation en temps réel dont dispose la plateforme.
 * Jitsi public n'expose aucune API de liste de participants : sans LiveKit
 * configuré, on ne sait que qui a **cliqué sur « Rejoindre »** depuis GEVES
 * ({@see SessionPresence}). Ne pas confondre les deux - l'un dit
 * « est dans la salle », l'autre « est passé par la porte ».
 *
 * L'identité LiveKit est forgée au moment du jeton ({@see Providers\LiveKitProvider::token()})
 * et porte l'identifiant de l'inscription, ce qui permet de rapprocher un
 * connecté d'un participant sans se fier à son nom d'affichage, que l'intéressé
 * peut changer.
 */
class LiveKitRoom
{
    /** Préfixe d'identité qui porte l'id de l'inscription : `ukp-{id}-…`. */
    public const IDENTITY_PREFIX = 'ukp';

    public function isConfigured(): bool
    {
        return filled(config('meetings.livekit.url'))
            && filled(config('meetings.livekit.api_key'))
            && filled(config('meetings.livekit.api_secret'));
    }

    /**
     * Identifiants d'inscription (`participants.id`) actuellement connectés.
     *
     * ⚠️ Ne lève jamais : la salle peut être injoignable, et une panne de LiveKit
     * ne doit pas emporter la page de présence ni la salle elle-même. Dans ce cas
     * on ne sait rien, et on le dit en renvoyant null - ce qui n'est pas la même
     * chose qu'une salle vide.
     *
     * @return list<int>|null null = information indisponible
     */
    public function connectedParticipantIds(Meetable $session): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $salle = $session->meetingRoom();

        try {
            $response = Http::withToken($this->token($salle))
                ->acceptJson()->asJson()->timeout(5)
                ->post($this->apiBase().'/twirp/livekit.RoomService/ListParticipants', ['room' => $salle]);
        } catch (Throwable) {
            return null;
        }

        // ⚠️ 404 « requested room does not exist » n'est PAS une panne : LiveKit
        // ne crée la salle qu'à la première connexion. Personne n'est entré, donc
        // personne n'est dedans - c'est une réponse, pas une absence de réponse.
        if ($response->status() === 404) {
            return [];
        }

        if ($response->failed()) {
            return null;
        }

        $ids = [];
        foreach ((array) ($response->json('participants') ?? []) as $connecte) {
            $id = self::participantIdFromIdentity((string) ($connecte['identity'] ?? ''));
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Identité LiveKit d'une inscription : `ukp-{id}-{aléa}`.
     *
     * L'aléa est indispensable - LiveKit refuse deux connexions de même identité,
     * et une même personne peut ouvrir la salle sur deux appareils.
     */
    public static function identityFor(?int $participantId, string $displayName): string
    {
        $base = $participantId !== null
            ? self::IDENTITY_PREFIX.'-'.$participantId
            : Str::slug($displayName ?: 'invite');

        return $base.'-'.Str::lower(Str::random(6));
    }

    /** Identifiant d'inscription porté par une identité, ou null (invité). */
    public static function participantIdFromIdentity(string $identity): ?int
    {
        if (! preg_match('/^'.self::IDENTITY_PREFIX.'-(\d+)-/', $identity, $m)) {
            return null;
        }

        return (int) $m[1];
    }

    /**
     * Jeton d'administration de salle (JWT HS256), courte durée.
     *
     * ⚠️ `room` doit nommer la salle EXACTE : LiveKit refuse le joker `*` sur
     * `roomAdmin` (401 « permissions denied »). Le jeton ne vaut donc que pour la
     * salle qu'on interroge - ce qui est aussi la bonne portée.
     */
    private function token(string $salle): string
    {
        $now = time();

        return JWT::encode([
            'iss' => (string) config('meetings.livekit.api_key'),
            'sub' => 'presence',
            'nbf' => $now,
            'iat' => $now,
            'exp' => $now + 600,
            'video' => ['roomAdmin' => true, 'room' => $salle],
        ], (string) config('meetings.livekit.api_secret'), 'HS256');
    }

    /** Base HTTP de l'API LiveKit, dérivée de l'URL WebSocket (wss→https). */
    private function apiBase(): string
    {
        return rtrim(Str::replaceStart('ws', 'http', trim((string) config('meetings.livekit.url'))), '/');
    }
}
