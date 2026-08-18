<?php

namespace App\Http\Controllers;

use App\Models\Colloque;
use App\Models\Edition;
use App\Models\Participant;
use App\Support\Gallery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

/**
 * Accueil public de la plateforme (institutionnel, non commercial). Vue statique
 * rendue par contrôleur + Blade (cacheable, sans snapshot Livewire) ; les parties
 * dynamiques sont des îlots (formulaire de contact = composant Livewire, galerie
 * et menu mobile = JS client). Répartit les éditions publiées selon les 3 phases
 * publiques (en cours / à venir / passées) et calcule les indicateurs.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        $today = Carbon::today();

        // Éditions publiées, réparties par phase à partir des dates de l'événement.
        $published = Edition::query()
            ->with('colloque')
            ->whereIn('status', ['open', 'closed', 'archived'])
            ->orderByDesc('date_start')
            ->get();

        $ongoing = $published->filter(fn (Edition $e) => $e->date_start && $e->date_end
            && $e->date_start->lte($today) && $e->date_end->gte($today))->values();

        $upcoming = $published->filter(fn (Edition $e) => $e->date_start && $e->date_start->gt($today))
            ->sortBy('date_start')->values();

        $past = $published->filter(fn (Edition $e) => $e->status === 'archived'
            || ($e->date_end && $e->date_end->lt($today)))->values();

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
