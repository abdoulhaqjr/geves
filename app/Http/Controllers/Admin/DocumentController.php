<?php

namespace App\Http\Controllers\Admin;

use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Documents\LivretDocx;
use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\Edition;
use App\Support\EditionSettings;
use App\Support\SessionDocLayout;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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

    /** PV / procès-verbaux imprimables : entête + communications + synthèse du rapport. */
    public function sessionPv(Edition $edition): View
    {
        $sessions = $edition->sessions()
            ->with(['atelier', 'report'])
            ->orderBy('session_day')->orderBy('ordre')->get();

        $commsBySession = $edition->slots()
            ->where('submission_id', '>', 0)
            ->with('submission')
            ->orderBy('starts_at')->get()
            ->groupBy('session_id');

        return view('documents.session-pv', [
            'edition' => $edition,
            'sessions' => $sessions,
            'commsBySession' => $commsBySession,
        ]);
    }

    /** Éditeur de mise en page (entêtes + colonnes) des documents de séance. */
    public function sessionLayout(Edition $edition): View
    {
        return view('admin.documents.session-layout', [
            'edition' => $edition,
            'columns' => SessionDocLayout::COLUMNS,
            'headerFields' => SessionDocLayout::HEADER_FIELDS,
            'pvSections' => SessionDocLayout::PV_SECTIONS,
            'sel' => [
                'sheet_columns' => array_column(SessionDocLayout::sheetColumns($edition), 'key'),
                'sheet_header' => array_column(SessionDocLayout::sheetHeader($edition), 'key'),
                'pv_columns' => array_column(SessionDocLayout::pvColumns($edition), 'key'),
                'pv_header' => array_column(SessionDocLayout::pvHeader($edition), 'key'),
                'pv_sections' => array_column(SessionDocLayout::pvSections($edition), 'key'),
            ],
            'labels' => [
                'sheet_columns' => (array) EditionSettings::get($edition, 'session_sheets.columns_labels', []),
                'sheet_header' => (array) EditionSettings::get($edition, 'session_sheets.header_labels', []),
                'pv_columns' => (array) EditionSettings::get($edition, 'session_pv.columns_labels', []),
                'pv_header' => (array) EditionSettings::get($edition, 'session_pv.header_labels', []),
                'pv_sections' => (array) EditionSettings::get($edition, 'session_pv.sections_labels', []),
            ],
        ]);
    }

    /** Enregistre la mise en page (colonnes/entêtes/sections) dans les réglages d'édition. */
    public function saveSessionLayout(Request $request, Edition $edition): RedirectResponse
    {
        $values = array_merge(
            $this->layoutGroup($request, 'sheet_columns', 'session_sheets.columns', SessionDocLayout::COLUMNS),
            $this->layoutGroup($request, 'sheet_header', 'session_sheets.header', SessionDocLayout::HEADER_FIELDS),
            $this->layoutGroup($request, 'pv_columns', 'session_pv.columns', SessionDocLayout::COLUMNS),
            $this->layoutGroup($request, 'pv_header', 'session_pv.header', SessionDocLayout::HEADER_FIELDS),
            $this->layoutGroup($request, 'pv_sections', 'session_pv.sections', SessionDocLayout::PV_SECTIONS),
        );
        EditionSettings::setMany($edition, $values);

        return back()->with('ukc_status', 'Mise en page des documents de séance enregistrée.');
    }

    /**
     * Construit la sélection (clés cochées, dans l'ordre du catalogue) + libellés
     * personnalisés d'un groupe du formulaire de mise en page.
     *
     * @param  array<string, string>  $catalog
     * @return array<string, mixed>
     */
    private function layoutGroup(Request $request, string $field, string $key, array $catalog): array
    {
        $checked = array_map('strval', (array) $request->input($field, []));
        $selected = array_values(array_filter(array_keys($catalog), fn ($k) => in_array($k, $checked, true)));

        $labelsInput = (array) $request->input($field.'_label', []);
        $labels = [];
        foreach ($selected as $k) {
            $v = trim((string) ($labelsInput[$k] ?? ''));
            if ($v !== '' && $v !== $catalog[$k]) {
                $labels[$k] = $v;
            }
        }

        return [$key => $selected, $key.'_labels' => $labels];
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
