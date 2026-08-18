<?php

namespace App\Support;

use App\Models\Edition;
use App\Models\Media;

/**
 * Construit la galerie publique d'une édition (photos + vidéos ordonnées) au
 * format attendu par le partiel `partials.gallery` / le lightbox (public.js).
 * Partagé entre l'accueil (galerie du dernier colloque) et l'archive-détail.
 */
class Gallery
{
    /**
     * @return array<int, array{type: string, src: string, caption: string, thumb: ?string}>
     */
    public static function for(?Edition $edition): array
    {
        if (! $edition) {
            return [];
        }

        return $edition->media()
            ->whereIn('type', ['photo', 'video'])
            ->orderBy('ordre')
            ->get()
            ->map(function (Media $m): array {
                $isVideo = $m->type === 'video';

                return [
                    'type' => $isVideo ? 'video' : 'image',
                    'src' => (string) $m->url,
                    'caption' => (string) ($m->title ?? ''),
                    'thumb' => $isVideo ? self::videoThumb((string) $m->url) : (string) $m->url,
                ];
            })
            ->all();
    }

    /** Vignette d'une vidéo (YouTube) ou null (le lecteur gère le reste). */
    private static function videoThumb(string $url): ?string
    {
        if (preg_match('~(?:youtu\.be/|watch\?v=|/embed/)([\w-]{11})~', $url, $m)) {
            return 'https://img.youtube.com/vi/'.$m[1].'/hqdefault.jpg';
        }

        return null;
    }
}
