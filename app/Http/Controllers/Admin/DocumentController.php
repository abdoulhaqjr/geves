<?php

namespace App\Http\Controllers\Admin;

use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Documents\LivretDocx;
use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\Edition;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Documents imprimables d'une édition : livret des résumés et planche de badges.
 * (Imprimer / Enregistrer en PDF depuis le navigateur.)
 */
class DocumentController extends Controller
{
    /** Livret des résumés : communications acceptées, groupées par axe. */
    public function livret(Edition $edition, Request $request): View|Response
    {
        $groups = $edition->submissions()
            ->where('revision_of', 0)
            ->where('status', 'accepted')
            ->orderBy('track')
            ->get()
            ->groupBy(fn ($s) => $s->track ?: 'Autres');

        if ($request->query('format') === 'docx') {
            return (new LivretDocx($edition, $groups, $request->boolean('harmonize')))
                ->download('livret-'.($edition->slug ?: 'edition-'.$edition->id).'.docx');
        }

        return view('documents.livret', [
            'edition' => $edition,
            'groups' => $groups,
        ]);
    }

    /** Fiches de session imprimables : chaque session avec ses communications. */
    public function sessionSheets(Edition $edition): View
    {
        $sessions = $edition->sessions()
            ->with('atelier')
            ->orderBy('session_day')
            ->orderBy('ordre')
            ->get();

        $commsBySession = $edition->slots()
            ->where('submission_id', '>', 0)
            ->with('submission')
            ->orderBy('starts_at')
            ->get()
            ->groupBy('session_id');

        return view('documents.session-sheets', [
            'edition' => $edition,
            'sessions' => $sessions,
            'commsBySession' => $commsBySession,
        ]);
    }

    /** Planche de badges (A4, 2 colonnes), couleur par rôle. */
    public function badges(Edition $edition, Request $request): View
    {
        $query = $edition->participants();

        if ($request->boolean('paid')) {
            $query->whereHas('payments', fn ($q) => $q->where('status', 'paid'));
        }

        $participants = $query->orderBy('last_name')->get();

        // Modèle de badge personnalisé de l'édition ?
        $template = DocumentTemplate::where('edition_id', $edition->id)
            ->where('kind', 'badge')->where('type', 'default')->first();

        if ($template && $template->layout) {
            $layout = LayoutSanitizer::sanitize($template->layout, 'badge');
            $badges = $participants->map(fn ($p) => LayoutRenderer::render($layout, [
                'org' => $edition->colloque?->name ?? '',
                'event' => $edition->title ?? '',
                'year' => (string) ($edition->year ?: ''),
                'name' => $p->full_name,
                'role' => $p->roleLabel(),
                'institution' => (string) $p->institution,
                'ref' => $edition->id.'-'.$p->id,
            ]));

            return view('documents.badges-template', [
                'edition' => $edition,
                'badges' => $badges,
                'width' => $layout['width'],
            ]);
        }

        return view('documents.badges', [
            'edition' => $edition,
            'participants' => $participants,
        ]);
    }
}
