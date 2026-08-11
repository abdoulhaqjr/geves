<?php

namespace App\Support;

/**
 * Normalisation des liens de diffusion en URL embarquable (iframe), en PHP pur.
 * Reconnaît YouTube et Vimeo (watch/court/live → /embed) ; toute autre URL est
 * renvoyée telle quelle (embed personnalisé fourni par l'organisateur).
 */
class StreamEmbed
{
    /** Fournisseur détecté : 'youtube' | 'vimeo' | 'custom' (ou null si vide). */
    public static function provider(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (self::youtubeId($url) !== null) {
            return 'youtube';
        }
        if (self::vimeoId($url) !== null) {
            return 'vimeo';
        }

        return 'custom';
    }

    /** URL embarquable (iframe src), ou null si l'entrée est vide. */
    public static function toEmbedUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (($id = self::youtubeId($url)) !== null) {
            return 'https://www.youtube.com/embed/'.$id;
        }
        if (($id = self::vimeoId($url)) !== null) {
            return 'https://player.vimeo.com/video/'.$id;
        }

        // Embed personnalisé : on suppose l'URL déjà embarquable.
        return $url;
    }

    /** Extrait l'identifiant d'une vidéo YouTube (watch, youtu.be, live, embed). */
    public static function youtubeId(string $url): ?string
    {
        $patterns = [
            '~youtu\.be/([A-Za-z0-9_-]{6,})~i',
            '~youtube\.com/(?:watch\?(?:.*&)?v=|embed/|live/|shorts/)([A-Za-z0-9_-]{6,})~i',
        ];
        foreach ($patterns as $re) {
            if (preg_match($re, $url, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    /** Extrait l'identifiant numérique d'une vidéo Vimeo. */
    public static function vimeoId(string $url): ?string
    {
        if (preg_match('~vimeo\.com/(?:video/)?(\d{6,})~i', $url, $m)) {
            return $m[1];
        }

        return null;
    }
}
