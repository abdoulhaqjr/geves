<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Support\Gallery;
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
            'gallery' => Gallery::for($edition),
            'stats' => [
                'communications' => $edition->submissions()->where('status', 'accepted')->count(),
                'participants' => $edition->participants()->count(),
                'sessions' => $edition->sessions()->count(),
            ],
        ]);
    }

    /** Nature de la manifestation (heuristique pour le filtre de la liste). */
    public static function kind(Edition $edition): string
    {
        $haystack = Str::lower($edition->colloque->name.' '.$edition->title);

        return Str::contains($haystack, ['journée', 'journee', 'séminaire', 'seminaire']) ? 'journee' : 'colloque';
    }
}
