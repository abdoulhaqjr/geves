<?php

namespace App\Http\Controllers\Admin;

use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Documents\LivretDocx;
use App\Documents\LivretTemplate;
use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\Event;
use App\Models\Submission;
use App\Support\EventSettings;
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
 * Documents imprimables d'un événement : livret des résumés et planche de badges.
 * (Imprimer / Enregistrer en PDF depuis le navigateur.)
 */
class DocumentController extends Controller
{
    /** Livret des résumés : communications acceptées, groupées par axe. */
    public function livret(Event $event, Request $request): View|Response
    {
        // ⚠️ Source unique de « ce que le livre contient » : le programme ci-dessous
        // s'y aligne aussi, sans quoi il annoncerait des communications absentes
        // de la section Résumés (cf. Submission::scopeForBooklet).
        $groups = $event->submissions()
            ->acceptedCanonical()
            ->orderBy('track')
            ->get()
            ->groupBy(fn ($s) => $s->track ?: 'Autres');

        if ($request->query('format') === 'docx') {
            $base = 'livret-'.($event->slug ?: 'edition-'.$event->id).'.docx';
            // Modèle .docx personnalisé sélectionné ? On remplit ses balises ; sinon
            // on retombe sur le générateur intégré paramétrable.
            if ($template = $this->selectedLivretTemplate($event)) {
                return (new LivretTemplate($event, $groups, (string) $template['path'], $request->boolean('harmonize')))
                    ->download($base);
            }

            return (new LivretDocx($event, $groups, $request->boolean('harmonize'), $this->livretStyle($event)))->download($base);
        }

        if ($request->query('format') === 'pdf') {
            $pdf = Pdf::loadView('documents.livret-pdf', $this->livretViewData($event, $groups))
                ->setPaper('a4');
            $file = 'livret-'.($event->slug ?: 'edition-'.$event->id).'.pdf';

            // `inline` → aperçu dans le navigateur (vrai rendu final) ; sinon téléchargement.
            return $request->boolean('inline') ? $pdf->stream($file) : $pdf->download($file);
        }

        return view('documents.livret', [
            'event' => $event,
            'groups' => $groups,
        ]);
    }

    /**
     * Modèle .docx personnalisé sélectionné pour cet événement, ou null (générateur
     * intégré). Un modèle n'est retenu que si son fichier existe réellement.
     *
     * @return array{name?: string, path?: string}|null
     */
    private function selectedLivretTemplate(Event $event): ?array
    {
        $selected = (string) EventSettings::get($event, 'livret.template', 'default');
        // 'default'/'base'/'fss' = styles intégrés (générateur LivretDocx), pas un
        // modèle .docx importé.
        if (in_array($selected, ['', 'default', 'base', ...array_keys(LivretDocx::STYLES)], true)) {
            return null;
        }

        $templates = EventSettings::get($event, 'livret.templates');
        foreach (is_array($templates) ? $templates : [] as $tpl) {
            if (($tpl['name'] ?? null) === $selected
                && ! empty($tpl['path'])
                && Storage::disk('public')->exists($tpl['path'])) {
                return $tpl;
            }
        }

        return null;
    }

    /** Style intégré actif pour le .docx : 'fss' si sélectionné, sinon 'default'. */
    private function livretStyle(Event $event): string
    {
        $selected = (string) EventSettings::get($event, 'livret.template', 'default');

        return array_key_exists($selected, LivretDocx::STYLES) ? $selected : 'default';
    }

    /**
     * Données partagées du livret (page de garde, avant-propos, comités, programme,
     * index des auteurs, options) pour les rendus PDF (dompdf) et HTML.
     *
     * @param  Collection<int|string, \Illuminate\Database\Eloquent\Collection<int, Submission>>  $groups
     * @return array<string, mixed>
     */
    private function livretViewData(Event $event, Collection $groups): array
    {
        $g = fn (string $key, mixed $default = null): mixed => EventSettings::get($event, $key, $default);
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
        // ⚠️ **Un créneau dont la communication n'est pas au livre n'y figure pas
        // non plus** : planifiée puis refusée, elle gardait son créneau et le
        // programme imprimé annonçait son titre et ses auteurs, introuvables dans
        // la section Résumés. On l'écarte, on ne la replie pas sur l'intitulé de
        // sa séance - la ligne n'existait que pour elle, et les autres créneaux
        // de la même séance la représentent déjà.
        $programme = $event->slots()->with(['submission', 'session'])
            ->whereNotNull('starts_at')->orderBy('starts_at')->get()
            ->filter(fn ($sl) => $sl->submission
                ? $sl->submission->isAcceptedCanonical()
                : trim((string) $sl->session_label) !== '')
            ->groupBy(fn ($sl) => $sl->starts_at?->format('Y-m-d') ?? '')
            ->sortKeys();

        // Ordre de référence UK/UP : supervision, organisation, scientifique.
        $committeeRoles = [
            'supervision' => 'Comité de supervision',
            'organisation' => 'Comité d’organisation',
            'scientifique' => 'Comité scientifique',
        ];

        $synthesis = $event->settings['program_synthesis'] ?? null;

        return [
            'event' => $event,
            'groups' => $groups,
            'authorIndex' => $authorIndex,
            'programme' => $programme,
            'programSynthesis' => is_array($synthesis) ? $synthesis : ['title' => '', 'days' => []],
            'committees' => $event->committeeMembers()->orderBy('ordre')->get()->groupBy('role'),
            'committeeRoles' => $committeeRoles,
            'dayLabel' => fn (string $d) => $d !== '' ? ucfirst(Carbon::parse($d)->translatedFormat('l j F Y')) : 'À programmer',
            'opts' => [
                'foreword_title' => trim((string) $g('livret.foreword_title', 'Avant-propos')) ?: 'Avant-propos',
                'foreword_text' => trim((string) $g('livret.foreword_text', '')),
                'signature' => trim((string) $g('livret.signature', '')),
                // Sections de tête génériques (rendues seulement si remplies).
                'patronage' => trim((string) $g('livret.patronage', '')),
                'ack_title' => trim((string) $g('livret.ack_title', 'Remerciements')) ?: 'Remerciements',
                'acknowledgements' => trim((string) $g('livret.acknowledgements', '')),
                'welcome_title' => trim((string) $g('livret.welcome_title', 'Mot de bienvenue')) ?: 'Mot de bienvenue',
                'welcome_text' => trim((string) $g('livret.welcome_text', '')),
                'welcome_signature' => trim((string) $g('livret.welcome_signature', '')),
                'president_title' => trim((string) $g('livret.president_title', 'Mot du président')) ?: 'Mot du président',
                'president_text' => trim((string) $g('livret.president_text', '')),
                'president_signature' => trim((string) $g('livret.president_signature', '')),
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
    public function sessionSheets(Event $event): View
    {
        $sessions = $event->sessions()
            ->with('atelier')
            ->orderBy('session_day')
            ->orderBy('ordre')
            ->get();

        $commsBySession = $event->slots()
            ->where('submission_id', '>', 0)
            ->with('submission')
            ->orderBy('starts_at')
            ->get()
            ->groupBy('session_id');

        return view('documents.session-sheets', [
            'event' => $event,
            'sessions' => $sessions,
            'commsBySession' => $commsBySession,
        ]);
    }

    /** PV / procès-verbaux imprimables : entête + communications + synthèse du rapport. */
    public function sessionPv(Event $event): View
    {
        $sessions = $event->sessions()
            ->with(['atelier', 'report'])
            // Présents RÉELLEMENT pointés par le modérateur / le rapporteur : le PV
            // doit préférer un comptage à l'affluence estimée à la main. En
            // `withCount` pour ne pas rouvrir une requête par séance.
            ->withCount(['attendances as present_count' => fn ($q) => $q->where('status', 'present')])
            ->orderBy('session_day')->orderBy('ordre')->get();

        $commsBySession = $event->slots()
            ->where('submission_id', '>', 0)
            ->with('submission')
            ->orderBy('starts_at')->get()
            ->groupBy('session_id');

        return view('documents.session-pv', [
            'event' => $event,
            'sessions' => $sessions,
            'commsBySession' => $commsBySession,
        ]);
    }

    /** Éditeur de mise en page (entêtes + colonnes) des documents de séance. */
    public function sessionLayout(Event $event): View
    {
        return view('admin.documents.session-layout', [
            'event' => $event,
            'columns' => SessionDocLayout::COLUMNS,
            'headerFields' => SessionDocLayout::HEADER_FIELDS,
            'pvSections' => SessionDocLayout::PV_SECTIONS,
            'sel' => [
                'sheet_columns' => array_column(SessionDocLayout::sheetColumns($event), 'key'),
                'sheet_header' => array_column(SessionDocLayout::sheetHeader($event), 'key'),
                'pv_columns' => array_column(SessionDocLayout::pvColumns($event), 'key'),
                'pv_header' => array_column(SessionDocLayout::pvHeader($event), 'key'),
                'pv_sections' => array_column(SessionDocLayout::pvSections($event), 'key'),
            ],
            'labels' => [
                'sheet_columns' => (array) EventSettings::get($event, 'session_sheets.columns_labels', []),
                'sheet_header' => (array) EventSettings::get($event, 'session_sheets.header_labels', []),
                'pv_columns' => (array) EventSettings::get($event, 'session_pv.columns_labels', []),
                'pv_header' => (array) EventSettings::get($event, 'session_pv.header_labels', []),
                'pv_sections' => (array) EventSettings::get($event, 'session_pv.sections_labels', []),
            ],
        ]);
    }

    /** Enregistre la mise en page (colonnes/entêtes/sections) dans les réglages d'événement. */
    public function saveSessionLayout(Request $request, Event $event): RedirectResponse
    {
        $values = array_merge(
            $this->layoutGroup($request, 'sheet_columns', 'session_sheets.columns', SessionDocLayout::COLUMNS),
            $this->layoutGroup($request, 'sheet_header', 'session_sheets.header', SessionDocLayout::HEADER_FIELDS),
            $this->layoutGroup($request, 'pv_columns', 'session_pv.columns', SessionDocLayout::COLUMNS),
            $this->layoutGroup($request, 'pv_header', 'session_pv.header', SessionDocLayout::HEADER_FIELDS),
            $this->layoutGroup($request, 'pv_sections', 'session_pv.sections', SessionDocLayout::PV_SECTIONS),
        );
        EventSettings::setMany($event, $values);

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
    public function badges(Event $event, Request $request): View
    {
        $query = $event->participants();

        if ($request->boolean('paid')) {
            $query->whereHas('participationPayments', fn ($q) => $q->where('status', 'paid'));
        }

        $participants = $query->orderBy('last_name')->get();

        // Toujours un modèle enregistré (créé au besoin) : plus de planche de repli
        // que l'admin ne pouvait ni voir ni modifier.
        $template = DocumentTemplate::ensureFor((int) $event->id, 'badge');

        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'badge');
        $badges = $participants->map(fn ($p) => LayoutRenderer::render($layout, [
            'org' => $event->series?->name ?? '',
            'event' => $event->title ?? '',
            'year' => (string) ($event->year ?: ''),
            'theme' => (string) ($event->theme ?? ''),
            'dates' => $event->dateRangeLabel() ?? '',
            'location' => (string) ($event->location ?? ''),
            'name' => $p->full_name,
            'role' => $p->roleLabel(),
            'institution' => (string) $p->institution,
            'ref' => $event->id.'-'.$p->id,
        ]));

        return view('documents.badges-template', [
            'event' => $event,
            'badges' => $badges,
            'width' => $layout['width'],
        ]);
    }
}
