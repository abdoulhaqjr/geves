<?php

namespace App\Http\Controllers\Admin;

use App\Documents\BadgePdfRenderer;
use App\Documents\BadgeSheet;
use App\Documents\BrandedPdf;
use App\Documents\LivretDocx;
use App\Documents\LivretTemplate;
use App\Documents\LivretView;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateBadgeExport;
use App\Models\BadgeExport;
use App\Models\Event;
use App\Services\BadgeExportWorkerLauncher;
use App\Support\BadgeCatalog;
use App\Support\EventSettings;
use App\Support\SessionDocLayout;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $this->prepareBadgeExport();

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
            [['type' => $type, 'people' => $people->all()]],
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
        $this->prepareBadgeExport();

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
            $groups[] = ['type' => (string) $type, 'people' => $lignes->pluck('person')->all()];
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
        $this->prepareBadgeExport();

        $type = (string) $request->input('type', 'default');
        abort_unless(BadgeCatalog::valid($type), 404);

        $refs = array_values(array_unique(array_filter(array_map(
            fn ($ref) => is_string($ref) ? $ref : '',
            (array) $request->input('refs', []),
        ))));

        if ($refs === []) {
            return back()->with('ukc_error', 'Choisissez au moins un destinataire.');
        }

        if (count($refs) > BadgeSheet::LOT) {
            return back()->with('ukc_error', 'La sélection dépasse '.BadgeSheet::LOT.' badges. Utilisez les liens de lots pour imprimer toute la catégorie.');
        }

        $people = BadgeCatalog::recipients($event, $type)
            ->filter(fn (array $person) => in_array($person['ref'], $refs, true))
            ->values();

        if ($people->isEmpty()) {
            return back()->with('ukc_error', 'Aucun des destinataires choisis ne porte de badge à imprimer.');
        }

        return $this->badgeSheetPdf(
            [['type' => $type, 'people' => $people->all()]],
            $event,
            'badges-'.$type.'-selection.pdf',
        );
    }

    /** Prépare tous les badges de la catégorie en arrière-plan, par lots de 48. */
    public function startBadgeExport(Request $request, Event $event, BadgeExportWorkerLauncher $launcher): RedirectResponse
    {
        $type = (string) $request->input('type', 'default');
        abort_unless(BadgeCatalog::valid($type), 404);

        $latest = BadgeExport::query()->where('event_id', $event->id)->where('user_id', $request->user()->id)
            ->where('type', $type)->latest()->first();
        if ($latest && in_array($latest->status, ['queued', 'running'], true) && $latest->updated_at->gt(now()->subMinutes(65))) {
            return back()->with('ukc_status', 'La préparation du PDF complet est déjà en cours.');
        }

        $count = BadgeCatalog::recipients($event, $type)->count();
        if ($count === 0) {
            return back()->with('ukc_error', 'Aucun badge prêt pour cette catégorie.');
        }

        $export = BadgeExport::query()->create([
            'id' => (string) Str::uuid(),
            'event_id' => $event->id,
            'user_id' => $request->user()->id,
            'type' => $type,
            'status' => 'queued',
            'badge_count' => $count,
        ]);

        try {
            GenerateBadgeExport::dispatch($export->id);
            $launcher->launch();
        } catch (\Throwable $exception) {
            $export->update(['status' => 'failed', 'error' => 'Impossible de démarrer le traitement.']);
            report($exception);

            return back()->with('ukc_error', 'Impossible de démarrer l’export. Réessayez dans un instant.');
        }

        return back()->with('ukc_status', 'Le PDF complet est en préparation. Le téléchargement apparaîtra ici une fois terminé.');
    }

    /** État lisible par la page, sans exposer les exports d'un autre gestionnaire. */
    public function badgeExportStatus(Request $request, Event $event, BadgeExport $export): JsonResponse
    {
        $this->authorizeBadgeExport($request, $event, $export);
        if (in_array($export->status, ['queued', 'running'], true) && $export->updated_at->lt(now()->subMinutes(65))) {
            $export->update(['status' => 'failed', 'error' => 'Le traitement a été interrompu. Relancez l’export.']);
        }

        return response()->json([
            'status' => $export->status,
            'processed' => $export->processed_count,
            'total' => $export->badge_count,
            'error' => $export->status === 'failed' ? $export->error : null,
            'downloadUrl' => $export->status === 'ready' ? route('admin.documents.badges.export.download', ['event' => $event, 'export' => $export]) : null,
        ]);
    }

    /** Envoie le fichier privé terminé au seul gestionnaire qui l'a demandé. */
    public function downloadBadgeExport(Request $request, Event $event, BadgeExport $export): Response
    {
        $this->authorizeBadgeExport($request, $event, $export);
        abort_unless($export->status === 'ready' && $export->path && Storage::disk('local')->exists($export->path), 404);

        return Storage::disk('local')->download($export->path, 'badges-'.$export->type.'-complets.pdf');
    }

    private function authorizeBadgeExport(Request $request, Event $event, BadgeExport $export): void
    {
        abort_unless((int) $export->event_id === (int) $event->id && (int) $export->user_id === (int) $request->user()->id, 404);
    }

    /**
     * Assemble les planches en PDF : chaque groupe remplit ses pages, un format par
     * page. ⚠️ Le PDF est produit par dompdf, pas par l'impression du navigateur -
     * c'est lui qui fait foi à l'impression.
     *
     * @param  list<array{type: string, people: list<array<string, mixed>>}>  $groups
     */
    private function badgeSheetPdf(array $groups, Event $event, string $filename): Response
    {
        return app(BadgePdfRenderer::class)->pdf($event, $groups)->download($filename);
    }

    /** Les planches chargent les photos et dessinent jusqu'à 48 badges avant Dompdf. */
    private function prepareBadgeExport(): void
    {
        // Le délai PHP de 30 s coupait le rendu dans Dompdf pour les formats où
        // un seul badge tient par feuille. Le relever AVANT de dessiner les badges.
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');
    }
}
