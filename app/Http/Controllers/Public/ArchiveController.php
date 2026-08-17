<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Media;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Archives publiques des colloques : liste des éditions passées et détail d'une
 * édition (thème, présentation, axes, galerie, documents). Vues statiques
 * rendues par contrôleur + Blade (cacheables) ; la galerie/lightbox est du JS
 * client (public.js), alimenté par les médias réels de l'édition.
 */
class ArchiveController extends Controller
{
    /** Liste des éditions passées (clôturées, archivées ou dont la date est dépassée). */
    public function index(): View
    {
        $today = Carbon::today();

        $editions = Edition::query()
            ->with('colloque')
            ->whereIn('status', ['open', 'closed', 'archived'])
            ->get()
            ->filter(fn (Edition $e) => $e->status === 'archived'
                || ($e->date_end && $e->date_end->lt($today)))
            ->sortByDesc('year')
            ->values();

        return view('public.archives', ['editions' => $editions]);
    }

    /** Détail d'une édition passée. */
    public function show(string $slug): View
    {
        $edition = Edition::with('colloque')->where('slug', $slug)->first();

        abort_unless($edition && $edition->isPublished(), 404);

        $settings = $edition->settings ?? [];

        $documents = [];
        if ($edition->booklet_url) {
            $documents[] = ['label' => 'Livre de résumés', 'url' => $edition->booklet_url];
        }
        foreach ($edition->media()->whereIn('type', ['document', 'livret', 'link'])->orderBy('ordre')->get() as $m) {
            $documents[] = ['label' => $m->title ?: $m->typeLabel(), 'url' => $m->url];
        }

        return view('public.archive', [
            'edition' => $edition,
            'description' => $settings['intro'] ?? $edition->subtitle,
            'highlights' => $settings['highlights'] ?? [],
            'axes' => $settings['tracks'] ?? [],
            'documents' => $documents,
            'gallery' => $this->gallery($edition),
            'stats' => [
                'communications' => $edition->submissions()->where('status', 'accepted')->count(),
                'participants' => $edition->participants()->count(),
                'sessions' => $edition->sessions()->count(),
            ],
        ]);
    }

    /**
     * Médias de galerie (photos + vidéos) formatés pour le lightbox client.
     *
     * @return list<array{type: string, src: string, caption: string, thumb: ?string}>
     */
    private function gallery(Edition $edition): array
    {
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
                    'thumb' => $isVideo ? $this->videoThumb((string) $m->url) : (string) $m->url,
                ];
            })
            ->all();
    }

    /** Vignette d'une vidéo YouTube (sinon null → fond dégradé). */
    private function videoThumb(string $url): ?string
    {
        if (preg_match('~(?:youtu\.be/|watch\?v=|/embed/)([\w-]{11})~', $url, $m)) {
            return 'https://img.youtube.com/vi/'.$m[1].'/hqdefault.jpg';
        }

        return null;
    }

    /** Nature de la manifestation (heuristique pour le filtre de la liste). */
    public static function kind(Edition $edition): string
    {
        $haystack = Str::lower($edition->colloque->name.' '.$edition->title);

        return Str::contains($haystack, ['journée', 'journee', 'séminaire', 'seminaire']) ? 'journee' : 'colloque';
    }
}
