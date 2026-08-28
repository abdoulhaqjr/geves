<?php

namespace App\Http\Controllers;

use App\Models\Colloque;
use App\Models\Edition;
use App\Models\Participant;
use App\Support\Gallery;
use Illuminate\Contracts\View\View;

/**
 * Accueil public de la plateforme (institutionnel, non commercial). Vue statique
 * rendue par contrôleur + Blade (cacheable) ; les parties dynamiques sont des
 * îlots légers (formulaire de contact = POST classique, galerie et menu mobile
 * = JS/Alpine). Répartit les éditions publiées selon les 3 phases publiques
 * (en cours / à venir / passées) et calcule les indicateurs.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        // Éditions publiées, réparties selon la phase du cycle de vie (§2.2) :
        // un simple appel à communications ouvert reste « à venir », pas « en cours ».
        $published = Edition::query()
            ->with('colloque')
            ->whereIn('status', ['open', 'closed', 'archived'])
            ->orderByDesc('date_start')
            ->get();

        $byView = $published->groupBy(fn (Edition $e) => $e->phase()->publicView());
        $ongoing = ($byView->get('ongoing') ?? collect())->values();
        $upcoming = ($byView->get('upcoming') ?? collect())->sortBy('date_start')->values();
        $past = ($byView->get('past') ?? collect())->values();

        // « À la une » : l'édition en cours, sinon la prochaine, sinon la dernière passée.
        $featured = $ongoing->first() ?? $upcoming->first() ?? $past->first();

        $stats = [
            'colloques' => $published->count(),
            'formats' => Colloque::count(),
            'participants' => Participant::count(),
            'etablissements' => Participant::query()
                ->whereNotNull('institution')->where('institution', '!=', '')
                ->distinct()->count('institution'),
        ];

        // Galerie « dernier colloque » : médias de l'édition passée la plus récente.
        $gallery = Gallery::for($past->first());

        return view('home', [
            'featured' => $featured,
            'ongoing' => $ongoing->first(),
            'upcoming' => $upcoming->take(2),
            'past' => $past->take(2),
            'stats' => $stats,
            'galleryEdition' => $past->first(),
            'gallery' => $gallery,
        ]);
    }
}
