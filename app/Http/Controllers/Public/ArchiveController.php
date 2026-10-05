<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\Gallery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * Archives publiques des colloques : liste des événements passées et détail d'une
 * événement (thème, présentation, axes, galerie, documents). Vues statiques
 * rendues par contrôleur + Blade (cacheables) ; la galerie/lightbox est du JS
 * client (public.js), alimenté par les médias réels de l'événement.
 */
class ArchiveController extends Controller
{
    /** Liste des événements passées (phase « Événement passé », cf. §2.2). */
    public function index(): View
    {
        $events = Event::query()
            ->with('series')
            ->whereIn('status', ['open', 'closed', 'archived'])
            ->get()
            ->filter(fn (Event $e) => $e->phase()->isPast())
            ->sortByDesc('year')
            ->values();

        return view('public.archives', ['events' => $events]);
    }

    /** Détail d'un événement passé. */
    public function show(string $slug): View
    {
        $event = Event::with('series')->where('slug', $slug)->first();

        abort_unless($event && $event->isPublished(), 404);

        $settings = $event->settings ?? [];

        $documents = [];
        if ($event->booklet_url) {
            $documents[] = ['label' => 'Livre de résumés', 'url' => $event->booklet_url];
        }
        foreach ($event->media()->whereIn('type', ['document', 'livret', 'link'])->orderBy('ordre')->get() as $m) {
            $documents[] = ['label' => $m->title ?: $m->typeLabel(), 'url' => $m->url];
        }

        return view('public.archive', [
            'event' => $event,
            'description' => $settings['intro'] ?? $event->subtitle,
            'highlights' => $settings['highlights'] ?? [],
            'axes' => $settings['tracks'] ?? [],
            'documents' => $documents,
            'gallery' => Gallery::for($event),
            'stats' => [
                'communications' => $event->submissions()->where('status', 'accepted')->count(),
                'participants' => $event->participants()->count(),
                'sessions' => $event->sessions()->count(),
            ],
        ]);
    }

    /** Nature de la manifestation (heuristique pour le filtre de la liste). */
    public static function kind(Event $event): string
    {
        $haystack = Str::lower($event->series->name.' '.$event->title);

        return Str::contains($haystack, ['journée', 'journee', 'séminaire', 'seminaire']) ? 'journee' : 'colloque';
    }
}
