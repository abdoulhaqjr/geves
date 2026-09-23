<?php

namespace App\Http\Controllers\Admin;

use App\AI\AiManager;
use App\AI\Exceptions\AiUnavailableException;
use App\AI\ScoreTestimonials;
use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gestion des témoignages (niveau plateforme, capacité `testimonials`) :
 * modération (approuver / refuser), saisie manuelle, mise en avant sur l'accueil
 * et ordre, plus une proposition de sélection assistée par IA (à la demande).
 * L'IA ne fait que suggérer ; l'admin garde le dernier mot.
 */
class TestimonialController extends Controller
{
    public function index(AiManager $ai): View
    {
        $testimonials = Testimonial::orderByDesc('id')->get();

        return view('admin.testimonials.index', [
            'pending' => $testimonials->where('status', 'pending')->values(),
            'approved' => $testimonials->where('status', 'approved')
                ->sortBy([['featured', 'desc'], ['position', 'asc'], ['impact_score', 'desc']])->values(),
            'rejected' => $testimonials->where('status', 'rejected')->values(),
            'statuses' => Testimonial::STATUSES,
            'aiAvailable' => $ai->available() !== [],
        ]);
    }

    /** Saisie manuelle par l'admin (approuvé par défaut : l'admin en répond). */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:160'],
            'author_role' => ['nullable', 'string', 'max:160'],
            'author_affiliation' => ['nullable', 'string', 'max:200'],
            'quote' => ['required', 'string', 'min:20', 'max:2000'],
            'featured' => ['nullable', 'boolean'],
        ]);

        Testimonial::create([
            'author_name' => $data['author_name'],
            'author_role' => $data['author_role'] ?? null,
            'author_affiliation' => $data['author_affiliation'] ?? null,
            'quote' => $data['quote'],
            'status' => 'approved',
            'featured' => (bool) ($data['featured'] ?? false),
            'consent' => true,
            'moderated_at' => now(),
        ]);

        return back()->with('ukc_status', 'Témoignage ajouté.');
    }

    public function approve(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update(['status' => 'approved', 'moderated_at' => now()]);

        return back()->with('ukc_status', 'Témoignage approuvé.');
    }

    public function reject(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update(['status' => 'rejected', 'featured' => false, 'moderated_at' => now()]);

        return back()->with('ukc_status', 'Témoignage refusé.');
    }

    /** Bascule la mise en avant sur l'accueil (uniquement pour un témoignage approuvé). */
    public function toggleFeatured(Testimonial $testimonial): RedirectResponse
    {
        if (! $testimonial->isApproved()) {
            return back()->with('ukc_error', 'Seul un témoignage approuvé peut être mis en avant.');
        }

        $testimonial->update(['featured' => ! $testimonial->featured]);

        return back()->with('ukc_status', $testimonial->featured ? 'Mis en avant sur l’accueil.' : 'Retiré de l’accueil.');
    }

    /** Réordonne les témoignages mis en avant (liste d'ids dans l'ordre voulu). */
    public function reorder(Request $request): RedirectResponse
    {
        $ids = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ])['order'];

        foreach (array_values($ids) as $position => $id) {
            Testimonial::where('id', (int) $id)->update(['position' => $position]);
        }

        return back()->with('ukc_status', 'Ordre enregistré.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return back()->with('ukc_status', 'Témoignage supprimé.');
    }

    /** Proposition de sélection « à la une » par l'IA (à la demande). */
    public function propose(ScoreTestimonials $action): RedirectResponse
    {
        try {
            $report = $action->handle();
        } catch (AiUnavailableException $e) {
            return back()->with('ukc_error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('ukc_error', 'Échec de l’analyse IA : '.$e->getMessage());
        }

        if ($report['scored'] === 0) {
            return back()->with('ukc_error', 'Aucun témoignage approuvé à analyser.');
        }

        return back()->with('ukc_status', sprintf(
            'IA : %d témoignage(s) noté(s), %d proposé(s) à la une - ajustez si besoin.',
            $report['scored'],
            $report['featured'],
        ));
    }
}
