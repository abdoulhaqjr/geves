<?php

namespace App\Http\Controllers\Admin;

use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Documents\LivretDocx;
use App\Documents\LivretTemplate;
use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\Edition;
use App\Models\Submission;
use App\Support\EditionSettings;
use App\Support\SessionDocLayout;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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
            $base = 'livret-'.($edition->slug ?: 'edition-'.$edition->id).'.docx';
            // Modèle .docx personnalisé sélectionné ? On remplit ses balises ; sinon
            // on retombe sur le générateur intégré paramétrable.
            if ($template = $this->selectedLivretTemplate($edition)) {
                return (new LivretTemplate($edition, $groups, (string) $template['path'], $request->boolean('harmonize')))
                    ->download($base);
            }

            return (new LivretDocx($edition, $groups, $request->boolean('harmonize')))->download($base);
        }

        if ($request->query('format') === 'pdf') {
            $pdf = Pdf::loadView('documents.livret-pdf', $this->livretViewData($edition, $groups))
                ->setPaper('a4');

            return $pdf->download('livret-'.($edition->slug ?: 'edition-'.$edition->id).'.pdf');
        }

        return view('documents.livret', [
            'edition' => $edition,
            'groups' => $groups,
        ]);
    }

    /**
     * Modèle .docx personnalisé sélectionné pour cette édition, ou null (générateur
     * intégré). Un modèle n'est retenu que si son fichier existe réellement.
     *
     * @return array{name?: string, path?: string}|null
     */
    private function selectedLivretTemplate(Edition $edition): ?array
    {
        $selected = (string) EditionSettings::get($edition, 'livret.template', 'default');
        if (in_array($selected, ['', 'default', 'base'], true)) {
            return null;
        }

        $templates = EditionSettings::get($edition, 'livret.templates');
        foreach (is_array($templates) ? $templates : [] as $tpl) {
            if (($tpl['name'] ?? null) === $selected
                && ! empty($tpl['path'])
                && Storage::disk('public')->exists($tpl['path'])) {
                return $tpl;
            }
        }

        return null;
    }

    /**
     * Données partagées du livret (page de garde, avant-propos, comités, programme,
     * index des auteurs, options) pour les rendus PDF (dompdf) et HTML.
     *
     * @param  Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, Submission>>  $groups
     * @return array<string, mixed>
     */
    private function livretViewData(Edition $edition, Collection $groups): array
    {
        $g = fn (string $key, mixed $default = null): mixed => EditionSettings::get($edition, $key, $default);
        // Alignement (réglage FR) -> valeur CSS text-align.
        $css = fn (mixed $v): string => match ((string) $v) {
            'Gauche' => 'left', 'Droite' => 'right', 'Justifié' => 'justify', default => 'center',
        };

        // Index des auteurs : nom => références des résumés (renvoi), même règle que le .docx.
        $authorIndex = [];
        $n = 0;
        foreach ($groups as $submissions) {
            foreach ($submissions as $s) {
                $n++;
                $ref = trim((string) $s->ref_code) ?: '#'.$n;
                foreach (collect($s->authors ?? []) as $a) {
                    // Nom en MAJUSCULES (convention du livret).
                    $name = mb_strtoupper(trim((string) ($a['name'] ?? '')), 'UTF-8');
                    if ($name !== '') {
                        $authorIndex[$name][] = $ref;
                    }
                }
            }
        }
        ksort($authorIndex, SORT_NATURAL | SORT_FLAG_CASE);
        $authorIndex = array_map(fn ($refs) => implode(', ', array_values(array_unique($refs))), $authorIndex);

        // Programme : communications placées (créneaux) regroupées par jour.
        $programme = $edition->slots()->with(['submission', 'session'])
            ->whereNotNull('starts_at')->orderBy('starts_at')->get()
            ->filter(fn ($sl) => $sl->submission || trim((string) $sl->session_label) !== '')
            ->groupBy(fn ($sl) => $sl->starts_at?->format('Y-m-d') ?? '')
            ->sortKeys();

        // Ordre de référence UK/UP : supervision, organisation, scientifique.
        $committeeRoles = [
            'supervision' => 'Comité de supervision',
            'organisation' => 'Comité d’organisation',
            'scientifique' => 'Comité scientifique',
        ];

        $synthesis = $edition->settings['program_synthesis'] ?? null;

        return [
            'edition' => $edition,
            'groups' => $groups,
            'authorIndex' => $authorIndex,
            'programme' => $programme,
            'programSynthesis' => is_array($synthesis) ? $synthesis : ['title' => '', 'days' => []],
            'committees' => $edition->committeeMembers()->orderBy('ordre')->get()->groupBy('role'),
            'committeeRoles' => $committeeRoles,
            'dayLabel' => fn (string $d) => $d !== '' ? ucfirst(Carbon::parse($d)->translatedFormat('l j F Y')) : 'À programmer',
            'opts' => [
                'foreword_title' => trim((string) $g('livret.foreword_title', 'Avant-propos')) ?: 'Avant-propos',
                'foreword_text' => trim((string) $g('livret.foreword_text', '')),
                'signature' => trim((string) $g('livret.signature', '')),
                'cover_mention' => trim((string) $g('livret.cover_mention', '')) ?: 'LIVRE DES RÉSUMÉS',
                'show_theme' => (bool) $g('livret.show_theme', true),
                'show_dates' => (bool) $g('livret.show_dates', true),
                'number' => (bool) $g('livret.number_abstracts', false),
                'author_index' => (bool) $g('livret.author_index', true),
                'color_primary' => trim((string) $g('livret.color_primary', '#223ea4')) ?: '#223ea4',
                'align_body' => $g('livret.align_body', 'Justifié') === 'Gauche' ? 'left' : 'justify',
                'align_titles' => $css($g('livret.align_titles', 'Centré')),
                'align_axis' => $css($g('livret.align_axis', 'Centré')),
                'align_authors' => $css($g('livret.align_authors', 'Centré')),
            ],
        ];
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
            $query->whereHas('participationPayments', fn ($q) => $q->where('status', 'paid'));
        }

        $participants = $query->orderBy('last_name')->get();

        // Toujours un modèle enregistré (créé au besoin) : plus de planche de repli
        // que l'admin ne pouvait ni voir ni modifier.
        $template = DocumentTemplate::ensureFor((int) $edition->id, 'badge');

        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'badge');
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
}
