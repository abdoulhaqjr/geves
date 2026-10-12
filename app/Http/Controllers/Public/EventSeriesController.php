<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Formation;
use App\Support\Fees;
use App\Support\SiteBanners;
use Illuminate\Contracts\View\View;

/**
 * Page publique d'un événement de colloque (accessible seulement si publiée).
 *
 * Vue statique en lecture seule rendue par contrôleur + Blade : la page ne porte
 * aucune interactivité serveur (elle est donc cacheable en HTTP). Le formulaire
 * de contact est un simple POST (`public.contact`) ; le compte à rebours et le
 * scrollspy sont gérés côté client par Alpine.
 */
class EventSeriesController extends Controller
{
    public function __invoke(string $slug): View
    {
        $event = Event::with('series')->where('slug', $slug)->first();

        abort_unless($event && $event->isPublished(), 404);

        $settings = $event->settings ?? [];

        return view('public.colloque', [
            'event' => $event,
            'intro' => $settings['intro'] ?? null,
            // Programme synthétique saisi dans l'onglet « Programme » du module.
            'programSynthesis' => $settings['program_synthesis'] ?? ['title' => '', 'days' => []],
            'tracks' => $settings['tracks'] ?? [],
            // Grille normalisée (les entrées fees malformées sont ignorées).
            'fees' => Fees::grid($event),
            'feesNote' => $settings['fees_note'] ?? null,
            'stats' => $settings['stats'] ?? [],
            'workshops' => $settings['workshops'] ?? [],
            'speakers' => $settings['speakers'] ?? [],
            'map' => $settings['map'] ?? [],
            'partners' => $settings['partners'] ?? [],
            'faq' => $settings['faq'] ?? [],
            'contact' => $settings['contact'] ?? [],
            // Repli commun quand l'événement n'a pas téléversé son propre
            // bandeau (Réglages › Bannières). ⚠️ Le choix de l'événement
            // passe toujours devant : le repli ne s'applique qu'à défaut.
            'heroImage' => ($settings['hero_image'] ?? null) ?: SiteBanners::pageDefault(),
            // ⚠️ La vidéo n'est servie que si la case « Activer la vidéo de fond »
            // l'est : sans ce test, l'interrupteur de l'onglet Apparence ne
            // faisait rien et toute URL enregistrée se jouait.
            'heroVideo' => ($settings['appearance']['hero_video_enabled'] ?? false)
                ? ($settings['hero_video'] ?? null)
                : null,
            'heroVideoMuted' => (bool) ($settings['appearance']['hero_video_muted'] ?? true),
            'promo' => $settings['promo'] ?? [],
            'practical' => $settings['practical'] ?? [],
            // Formations publiques (si le module est activé pour l'événement).
            'formations' => $event->formationEnabled()
                ? Formation::with('trainers')
                    ->where('event_id', $event->id)
                    ->where('published', true)->where('public', true)
                    ->orderByRaw('starts_at is null, starts_at')->get()
                : collect(),
            'plenaries' => $event->sessions()->where('type', 'plenary')->orderBy('ordre')->orderBy('starts_at')->get(),
            'ateliers' => $event->ateliers()->with(['sessions' => fn ($q) => $q->orderBy('ordre')])->orderBy('ordre')->get(),

            'committees' => $event->committeeMembers()->orderBy('ordre')->get()->groupBy('role'),
            // Autres événements publiées du même colloque (événements précédentes).
            'siblings' => $event->event_series_id
                ? Event::where('event_series_id', $event->event_series_id)
                    ->whereKeyNot($event->id)
                    ->whereIn('status', ['open', 'closed', 'archived'])
                    ->orderByDesc('year')
                    ->limit(4)
                    ->get()
                : collect(),
        ]);
    }
}
