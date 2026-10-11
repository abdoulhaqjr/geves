<?php

namespace App\Http\Controllers;

use App\Models\Attestation;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Submission;
use App\Models\Testimonial;
use App\Support\Gallery;
use Illuminate\Contracts\View\View;

/**
 * Accueil public de la plateforme (institutionnel, non commercial). Vue statique
 * rendue par contrôleur + Blade (cacheable) ; les parties dynamiques sont des
 * îlots légers (formulaire de contact = POST classique, galerie et menu mobile
 * = JS/Alpine). Répartit les événements publiées selon les 3 phases publiques
 * (en cours / à venir / passées) et calcule les indicateurs.
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        // Événements publiées, réparties selon la phase du cycle de vie (§2.2) :
        // un simple appel à communications ouvert reste « à venir », pas « en cours ».
        $published = Event::query()
            ->with('series')
            ->whereIn('status', ['open', 'closed', 'archived'])
            ->orderByDesc('date_start')
            ->get();

        $byView = $published->groupBy(fn (Event $e) => $e->phase()->publicView());
        $ongoing = ($byView->get('ongoing') ?? collect())->values();
        $upcoming = ($byView->get('upcoming') ?? collect())->sortBy('date_start')->values();
        $past = ($byView->get('past') ?? collect())->values();

        // « À la une » : l'événement en cours, sinon la prochaine, sinon la dernière passée.
        $featured = $ongoing->first() ?? $upcoming->first() ?? $past->first();

        // ⚠️ **Deux de ces quatre chiffres étaient faux ou inutiles**, et les
        // clés mentaient : `event_series` portait des ÉVÉNEMENTS, `formats` des
        // séries.
        //
        // ⚠️ « Établissements représentés » comptait des **chaînes libres
        // distinctes**, pas des établissements. Mesuré sur le colloque de l'eau :
        // 44 inscrits, 44 chaînes distinctes - parce que chacun saisit son
        // LABORATOIRE (« Laboratoire Hydrosciences Lavoisier, Université de
        // Bangui »), et que deux laboratoires d'une même université comptaient
        // pour deux. Le chiffre suivait donc le nombre d'inscrits : l'accueil
        // affichait « 44 participants » à côté de « 44 établissements ». Compter
        // de vrais établissements demanderait de rapprocher ces textes libres
        // d'un référentiel (ROR est déjà là, cf. `RorClient`) : c'est une
        // fonctionnalité, pas un compteur.
        //
        // ⚠️ « Colloques & séries » comptait les séries, ce que le visiteur ne
        // distingue pas des événements - et le mot « colloque » contredit le
        // modèle typé (séminaires, journées scientifiques).
        //
        // Les quatre chiffres racontent maintenant le parcours de la plateforme :
        // des événements, des communications, des gens, des documents.
        $stats = [
            'evenements' => $published->count(),
            'communications' => Submission::query()->canonical()->count(),
            'participants' => Participant::count(),
            'attestations' => Attestation::count(),
        ];

        // Galerie « dernier colloque » : médias de l'événement passé le plus récent.
        $gallery = Gallery::for($past->first());

        return view('home', [
            'featured' => $featured,
            'ongoing' => $ongoing->first(),
            'upcoming' => $upcoming->take(2),
            'past' => $past->take(2),
            'stats' => $stats,
            'galleryEdition' => $past->first(),
            'gallery' => $gallery,
            // Témoignages « à la une » (approuvés + mis en avant, ordonnés).
            'testimonials' => Testimonial::forHome()->take(6)->get(),
        ]);
    }
}
