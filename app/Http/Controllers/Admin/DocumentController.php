<?php

namespace App\Http\Controllers\Admin;

use App\Documents\BadgeLayout;
use App\Documents\BrandedPdf;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Documents\LivretDocx;
use App\Documents\LivretTemplate;
use App\Documents\LivretView;
use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\Event;
use App\Models\Submission;
use App\Support\BadgeCatalog;
use App\Support\EventSettings;
use App\Support\SessionDocLayout;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        if ($request->query('format') === 'pdf' && $request->boolean('inline') && ! $request->boolean('raw')) {
            return view('documents.pdf-preview', [
                'title' => 'Livre des résumés - '.$event->title,
                'pdfUrl' => route('admin.documents.livret', ['event' => $event, 'format' => 'pdf', 'inline' => 1, 'raw' => 1]),
                'downloadUrl' => route('admin.documents.livret', ['event' => $event, 'format' => 'pdf']),
            ]);
        }

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

            return (new LivretDocx($event, $groups, $request->boolean('harmonize'), LivretView::style($event)))->download($base);
        }

        if ($request->query('format') === 'pdf') {
            $pdf = BrandedPdf::stamp(
                Pdf::loadView('documents.livret-pdf', LivretView::data($event, $groups))->setPaper('a4'),
                'Livre des résumés - '.$event->title,
            );
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

    /** Planche A4 du type de badge demandé, depuis ses destinataires réels. */
    public function badges(Request $request, Event $event): View
    {
        $type = (string) $request->query('type', 'default');
        if ($type === 'participant_photo') {
            $type = 'default'; // Ancien lien : un seul modèle participant désormais.
        }
        abort_unless(BadgeCatalog::valid($type), 404);

        $rendered = $this->renderBadges($event, $type);

        return view('documents.badges-template', [
            'event' => $event,
            'badges' => $rendered['badges'],
            'badgeType' => BadgeCatalog::label($type),
            'width' => $rendered['layout']['width'],
            'height' => $rendered['layout']['height'],
        ]);
    }

    /**
     * Planche de TOUS les badges prêts, toutes catégories confondues, groupés par
     * format : un bloc de tuilage A4 par format (saut de page entre groupes). On
     * ne mélange pas deux formats sur une même feuille (cf. le lot d'attestations).
     */
    public function badgesAll(Event $event): View
    {
        $groups = [];
        foreach (array_keys(BadgeCatalog::TYPES) as $type) {
            $rendered = $this->renderBadges($event, $type);
            if ($rendered['badges']->isEmpty()) {
                continue;
            }
            $key = $rendered['layout']['width'].'×'.$rendered['layout']['height'];
            $groups[$key] ??= [
                'width' => (int) $rendered['layout']['width'],
                'height' => (int) $rendered['layout']['height'],
                'labels' => [],
                'badges' => collect(),
            ];
            $groups[$key]['labels'][] = BadgeCatalog::label($type);
            $groups[$key]['badges'] = $groups[$key]['badges']->concat($rendered['badges']);
        }

        return view('documents.badges-all', [
            'event' => $event,
            'groups' => array_values($groups),
            'totalBadges' => array_sum(array_map(fn ($g) => $g['badges']->count(), $groups)),
        ]);
    }

    /**
     * Rend chaque destinataire d'une catégorie de badge en HTML, depuis le modèle
     * enregistré (créé au besoin). Source unique de la planche par catégorie et de
     * la planche « tous les badges prêts ».
     *
     * @return array{layout: array<string, mixed>, badges: Collection<int, string>}
     */
    private function renderBadges(Event $event, string $type): array
    {
        $people = BadgeCatalog::recipients($event, $type);

        // Toujours un modèle enregistré (créé au besoin) : plus de planche de repli
        // que l'admin ne pouvait ni voir ni modifier.
        $template = DocumentTemplate::ensureFor((int) $event->id, 'badge', $type, BadgeCatalog::TYPES[$type]['title']);
        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'badge', $type);

        // ⚠️ Plus de valeur de repli : `BadgeCatalog::recipients()` pose TOUJOURS
        // la photo (chaîne vide à défaut) et son mode (« with » à défaut). Un `??`
        // ici laisserait croire que la préférence peut manquer - et c'est en la
        // croyant absente qu'on finit par ne jamais la respecter.
        $badges = $people->map(fn (array $person) => LayoutRenderer::render(BadgeLayout::forRecipient($layout, $type, $person['photo'], $person['photo_mode'] !== 'without'), [
            'org' => $event->series?->name ?? '',
            'event' => $event->title ?? '',
            'year' => (string) ($event->year ?: ''),
            'theme' => (string) ($event->theme ?? ''),
            'dates' => $event->dateRangeLabel() ?? '',
            'location' => (string) ($event->location ?? ''),
            'name' => $person['name'],
            'role' => BadgeCatalog::role($type),
            'institution' => $person['institution'],
            'ref' => $person['ref'],
            'verify_url' => url('/verifier?ref='.urlencode($person['ref']).'&ed='.$event->id),
            // Photo de profil pour le modèle « Participant avec photo » (élément photo).
            'recipient.photo' => $person['photo'],
        ], badge: true));

        return ['layout' => $layout, 'badges' => $badges];
    }
}
