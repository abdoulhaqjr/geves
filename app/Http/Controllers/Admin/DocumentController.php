<?php

namespace App\Http\Controllers\Admin;

use App\Documents\BadgeLayout;
use App\Documents\BadgeSheet;
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

    /** Planche PDF des badges d'UNE catégorie, tuilée A4 avec gouttières. */
    public function badges(Request $request, Event $event): Response|RedirectResponse
    {
        $type = (string) $request->query('type', 'default');
        if ($type === 'participant_photo') {
            $type = 'default'; // Ancien lien : un seul modèle participant désormais.
        }
        abort_unless(BadgeCatalog::valid($type), 404);

        $lot = max(1, (int) $request->query('lot', 1));
        $people = BadgeCatalog::recipients($event, $type)->forPage($lot, BadgeSheet::LOT)->values();

        if ($people->isEmpty()) {
            return back()->with('ukc_error', 'Aucun destinataire pour cette catégorie de badge.');
        }

        return $this->badgeSheetPdf(
            [$this->renderGroup($event, $type, $people->all())],
            $event,
            'badges-'.$type.($lot > 1 ? '-lot'.$lot : '').'.pdf',
        );
    }

    /**
     * Planche PDF de TOUS les badges prêts, toutes catégories confondues, groupés
     * par format : un bloc de tuilage par format. On ne mélange pas deux formats
     * sur une même feuille (cf. le lot d'attestations).
     */
    public function badgesAll(Request $request, Event $event): Response|RedirectResponse
    {
        // Une liste à plat (catégorie, destinataire) : le lot se découpe sur
        // l'ensemble, sinon un plafond par catégorie en laisserait en arrière.
        $paires = [];
        foreach (array_keys(BadgeCatalog::TYPES) as $type) {
            foreach (BadgeCatalog::recipients($event, $type) as $person) {
                $paires[] = ['type' => $type, 'person' => $person];
            }
        }

        $lot = max(1, (int) $request->query('lot', 1));
        $paires = collect($paires)->forPage($lot, BadgeSheet::LOT);

        if ($paires->isEmpty()) {
            return back()->with('ukc_error', 'Aucun badge prêt pour cet événement.');
        }

        $groups = [];
        foreach ($paires->groupBy('type') as $type => $lignes) {
            $groups[] = $this->renderGroup($event, (string) $type, $lignes->pluck('person')->all());
        }

        return $this->badgeSheetPdf($groups, $event, 'badges-tous'.($lot > 1 ? '-lot'.$lot : '').'.pdf');
    }

    /**
     * Planche PDF des badges CHOISIS : on coche des destinataires dans « Badges
     * prêts » et on n'imprime qu'eux. La référence stable du badge
     * (`BadgeCatalog::reference()`) sert de clé - elle est déjà celle du QR.
     */
    public function badgesSelection(Request $request, Event $event): Response|RedirectResponse
    {
        $type = (string) $request->input('type', 'default');
        abort_unless(BadgeCatalog::valid($type), 404);

        $refs = array_values(array_filter(array_map(
            fn ($ref) => is_string($ref) ? $ref : '',
            (array) $request->input('refs', []),
        )));

        if ($refs === []) {
            return back()->with('ukc_error', 'Choisissez au moins un destinataire.');
        }

        $people = BadgeCatalog::recipients($event, $type)
            ->filter(fn (array $person) => in_array($person['ref'], $refs, true))
            ->take(BadgeSheet::LOT)
            ->values();

        if ($people->isEmpty()) {
            return back()->with('ukc_error', 'Aucun des destinataires choisis ne porte de badge à imprimer.');
        }

        return $this->badgeSheetPdf(
            [$this->renderGroup($event, $type, $people->all())],
            $event,
            'badges-'.$type.'-selection.pdf',
        );
    }

    /**
     * Une catégorie rendue à la taille de cellule du tuilage, prête à être posée
     * sur la planche.
     *
     * ⚠️ Les destinataires arrivent en TABLEAU, pas en `Collection` : celle-ci
     * n'est pas covariante pour l'analyse, donc une liste dont chaque entrée est
     * décrite champ par champ (ce que rend `BadgeCatalog::recipients()`) ne « vaut »
     * pas une liste de `array<string, mixed>`. Même remède que `roleUsageLines` :
     * tableau dans l'aide, `->all()` au point d'appel.
     *
     * @param  list<array<string, mixed>>  $people
     * @return array{label: string, plan: array<string, mixed>, badges: list<string>}
     */
    private function renderGroup(Event $event, string $type, array $people): array
    {
        // Toujours un modèle enregistré (créé au besoin) : plus de planche de repli
        // que l'admin ne pouvait ni voir ni modifier.
        $template = DocumentTemplate::ensureFor((int) $event->id, 'badge', $type, BadgeCatalog::TYPES[$type]['title']);
        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'badge', $type);

        $plan = BadgeSheet::plan((int) $layout['width'], (int) $layout['height']);

        // ⚠️ Plus de valeur de repli : `BadgeCatalog::recipients()` pose TOUJOURS
        // la photo (chaîne vide à défaut) et son mode (« with » à défaut). Un `??`
        // ici laisserait croire que la préférence peut manquer - et c'est en la
        // croyant absente qu'on finit par ne jamais la respecter.
        $badges = array_map(function (array $person) use ($event, $type, $layout, $plan): string {
            // La variante (avec / sans photo) se choisit sur la maquette NATIVE,
            // puis seulement on la redessine à la taille de la cellule.
            $recipientLayout = BadgeLayout::forRecipient($layout, $type, $person['photo'], $person['photo_mode'] !== 'without');

            return LayoutRenderer::render(
                BadgeSheet::scaleLayout($recipientLayout, $plan['scale'], $plan['cellW'], $plan['cellH']),
                $this->badgeVars($event, $type, $person),
                pdf: true,
                badge: true,
            );
        }, $people);

        return ['label' => BadgeCatalog::label($type), 'plan' => $plan, 'badges' => $badges];
    }

    /**
     * Jetons PLATS du badge (le concepteur de badges n'emploie pas la palette
     * `[espace.cle]`).
     *
     * @param  array<string, mixed>  $person
     * @return array<string, string>
     */
    private function badgeVars(Event $event, string $type, array $person): array
    {
        return [
            'org' => $event->series?->name ?? '',
            'event' => (string) ($event->title ?? ''),
            'year' => (string) ($event->year ?: ''),
            'theme' => (string) ($event->theme ?? ''),
            'dates' => $event->dateRangeLabel() ?? '',
            'location' => (string) ($event->location ?? ''),
            'name' => (string) $person['name'],
            'role' => BadgeCatalog::role($type),
            'institution' => (string) $person['institution'],
            'ref' => (string) $person['ref'],
            'verify_url' => url('/verifier?ref='.urlencode((string) $person['ref']).'&ed='.$event->id),
            // Photo de profil pour le modèle « Participant avec photo » (élément photo).
            'recipient.photo' => (string) $person['photo'],
        ];
    }

    /**
     * Assemble les planches en PDF : chaque groupe remplit ses pages, un format par
     * page. ⚠️ Le PDF est produit par dompdf, pas par l'impression du navigateur -
     * c'est lui qui fait foi à l'impression.
     *
     * @param  list<array{label: string, plan: array<string, mixed>, badges: list<string>}>  $groups
     */
    private function badgeSheetPdf(array $groups, Event $event, string $filename): Response
    {
        // ⚠️ MESURÉ : environ 2,5 Mo de mémoire par badge (48 badges, 12 feuilles,
        // 6,9 s). Le défaut de 128 Mo tombait AU MILIEU du rendu - page blanche
        // après l'attente. On borne le lot (BadgeSheet::LOT) ET on relève la
        // limite : les deux, parce qu'un serveur peut être plus avare que le nôtre.
        @ini_set('memory_limit', '512M');

        $sheets = [];
        foreach ($groups as $group) {
            $plan = $group['plan'];
            foreach (array_chunk($group['badges'], max(1, (int) $plan['perPage'])) as $page) {
                $sheets[] = [
                    'cols' => (int) $plan['cols'],
                    'cellW' => (int) $plan['cellW'],
                    'cellH' => (int) $plan['cellH'],
                    'gutter' => (int) $plan['gutter'],
                    'sheetW' => (int) $plan['sheetW'],
                    'sheetH' => (int) $plan['sheetH'],
                    'marginPx' => (int) $plan['marginPx'],
                    'pageW' => (int) $plan['pageW'],
                    'pageH' => (int) $plan['pageH'],
                    'offsetLeft' => (int) $plan['offsetLeft'],
                    'offsetTop' => (int) $plan['offsetTop'],
                    'badges' => $page,
                ];
            }
        }

        $pdf = Pdf::loadView('pdf.badges-sheet', [
            'sheets' => $sheets,
            'marginMm' => (int) ($groups[0]['plan']['marginMm'] ?? 6),
        ])->setPaper('a4', 'portrait');

        return BrandedPdf::stamp($pdf, 'Badges - '.$event->title)->download($filename);
    }
}
