<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Formation;
use App\Support\Fees;
use Illuminate\Contracts\View\View;

/**
 * Page publique d'une édition de colloque (accessible seulement si publiée).
 *
 * Vue statique en lecture seule rendue par contrôleur + Blade : la page ne porte
 * aucune interactivité serveur (elle est donc cacheable en HTTP). Le formulaire
 * de contact est un simple POST (`public.contact`) ; le compte à rebours et le
 * scrollspy sont gérés côté client par Alpine.
 */
class ColloqueController extends Controller
{
    public function __invoke(string $slug): View
    {
        $edition = Edition::with('colloque')->where('slug', $slug)->first();

        abort_unless($edition && $edition->isPublished(), 404);

        $settings = $edition->settings ?? [];

        // Communications programmées, groupées par session (via les créneaux).
        $commsBySession = $edition->slots()
            ->where('submission_id', '>', 0)
            ->with('submission')
            ->orderBy('starts_at')
            ->get()
            ->groupBy('session_id');

        return view('public.colloque', [
            'edition' => $edition,
            'intro' => $settings['intro'] ?? null,
            // Programme synthétique saisi dans l'onglet « Programme » du module.
            'programSynthesis' => $settings['program_synthesis'] ?? ['title' => '', 'days' => []],
            'tracks' => $settings['tracks'] ?? [],
            // Grille normalisée (les entrées fees malformées sont ignorées).
            'fees' => Fees::grid($edition),
            'feesNote' => $settings['fees_note'] ?? null,
            'stats' => $settings['stats'] ?? [],
            'workshops' => $settings['workshops'] ?? [],
            'speakers' => $settings['speakers'] ?? [],
            'map' => $settings['map'] ?? [],
            'partners' => $settings['partners'] ?? [],
            'faq' => $settings['faq'] ?? [],
            'contact' => $settings['contact'] ?? [],
            'heroImage' => $settings['hero_image'] ?? null,
            // ⚠️ La vidéo n'est servie que si la case « Activer la vidéo de fond »
            // l'est : sans ce test, l'interrupteur de l'onglet Apparence ne
            // faisait rien et toute URL enregistrée se jouait.
            'heroVideo' => ($settings['appearance']['hero_video_enabled'] ?? false)
                ? ($settings['hero_video'] ?? null)
                : null,
            'heroVideoMuted' => (bool) ($settings['appearance']['hero_video_muted'] ?? true),
            'promo' => $settings['promo'] ?? [],
            'practical' => $settings['practical'] ?? [],
            // Formations publiques (si le module est activé pour l'édition).
            'formations' => $edition->formationEnabled()
                ? Formation::with('trainers')
                    ->where('edition_id', $edition->id)
                    ->where('published', true)->where('public', true)
                    ->orderByRaw('starts_at is null, starts_at')->get()
                : collect(),
            'plenaries' => $edition->sessions()->where('type', 'plenary')->orderBy('ordre')->orderBy('starts_at')->get(),
            'ateliers' => $edition->ateliers()->with(['sessions' => fn ($q) => $q->orderBy('ordre')])->orderBy('ordre')->get(),
            'commsBySession' => $commsBySession,
            'committees' => $edition->committeeMembers()->orderBy('ordre')->get()->groupBy('role'),
            // Autres éditions publiées du même colloque (éditions précédentes).
            'siblings' => $edition->colloque_id
                ? Edition::where('colloque_id', $edition->colloque_id)
                    ->whereKeyNot($edition->id)
                    ->whereIn('status', ['open', 'closed', 'archived'])
                    ->orderByDesc('year')
                    ->limit(4)
                    ->get()
                : collect(),
        ]);
    }
}
