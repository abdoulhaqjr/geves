<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateReport;
use App\Http\Controllers\Admin\Concerns\ScopesEditionAccess;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\EventSession;
use App\Support\EditionReport;
use App\Support\Settings;
use App\Support\XlsxWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Poste de travail du secrétariat (back-office, Blade) : file des rapports de
 * séance à valider, séances en attente de rédaction, rapports validés (onglet
 * « Rapports de séance ») ET un « Secrétariat assisté » qui génère des documents
 * (listes de présence, feuilles d'émargement, affiches directionnelles, ordre du
 * jour…) par prompt ou modèle, via l'IA, ancrés sur les données réelles de
 * l'édition. Scopé aux éditions accessibles à l'utilisateur.
 */
class SecretariatController extends Controller
{
    use ScopesEditionAccess;

    /**
     * Modèles de documents proposés au secrétariat. Chaque preset pré-remplit le
     * prompt, le format de sortie et le jeu de données joint.
     *
     * @var array<string, array{label: string, format: string, dataset: string, prompt: string}>
     */
    public const PRESETS = [
        'presence' => [
            'label' => 'Liste de présence',
            'format' => 'xlsx', 'dataset' => 'presence',
            'prompt' => 'Génère une liste de présence des participants de l’édition, avec un en-tête au nom du colloque, et des colonnes : Nom, Institution, Catégorie, Émargement (case à signer).',
        ],
        'emargement' => [
            'label' => 'Feuille d’émargement',
            'format' => 'pdf', 'dataset' => 'presence',
            'prompt' => 'Génère une feuille d’émargement imprimable (en-tête au nom du colloque, date et séance à compléter) avec les colonnes Nom, Institution, Signature.',
        ],
        'affiche' => [
            'label' => 'Affiche directionnelle',
            'format' => 'pdf', 'dataset' => 'none',
            'prompt' => 'Rédige le contenu d’affiches directionnelles pour orienter les participants vers les salles et ateliers, à partir du programme réel (une affiche par direction, avec de gros intitulés « Atelier → Salle »).',
        ],
        'ordre_jour' => [
            'label' => 'Ordre du jour',
            'format' => 'pdf', 'dataset' => 'none',
            'prompt' => 'Rédige l’ordre du jour d’une journée du colloque à partir du programme réel : séances, horaires, salles, modérateurs et rapporteurs.',
        ],
        'chevalets' => [
            'label' => 'Chevalets nominatifs',
            'format' => 'docx', 'dataset' => 'none',
            'prompt' => 'Génère des chevalets nominatifs (nom et fonction) pour les modérateurs et rapporteurs des séances, un par carte, à partir du programme réel.',
        ],
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $editions = $user->accessibleEditions('editions_read');
        $editionIds = $editions->pluck('id');

        /** @var Collection<int, EventSession> $sessions */
        $sessions = $editionIds->isEmpty()
            ? collect()
            : EventSession::whereIn('edition_id', $editionIds)
                ->whereNotNull('rapporteur_email')->where('rapporteur_email', '!=', '')
                ->with(['edition.colloque', 'atelier', 'report'])
                ->orderBy('starts_at')->orderBy('id')->get();

        $toValidate = $sessions->filter(fn (EventSession $s) => $s->report && $s->report->isSubmitted());
        $awaiting = $sessions->filter(fn (EventSession $s) => $s->hasStarted()
            && (! $s->report || $s->report->status === 'draft'));
        $validated = $sessions->filter(fn (EventSession $s) => $s->report && $s->report->isValidated());

        // Secrétariat assisté : éditions où l'utilisateur peut produire des documents.
        $docEditions = $user->accessibleEditions('documents');
        $currentDoc = $docEditions->firstWhere('status', 'open') ?? $docEditions->first();

        return view('admin.secretariat', [
            'editions' => $editions,
            'toValidate' => $toValidate->values(),
            'awaiting' => $awaiting->values(),
            'validated' => $validated->sortByDesc(fn (EventSession $s) => $s->report?->validated_at)->take(6)->values(),
            'stats' => [
                'to_validate' => $toValidate->count(),
                'validated' => $validated->count(),
                'awaiting' => $awaiting->count(),
            ],
            'docEditions' => $docEditions,
            'currentDocEditionId' => $currentDoc?->id,
            'presets' => self::PRESETS,
            'assistHistory' => array_slice((array) Settings::get('secretariat_ai_history', []), 0, 12),
        ]);
    }

    /** Génère un document du secrétariat via IA (prompt ou modèle joint). */
    /**
     * Rapports de séance d'une édition : lecture et amélioration par le
     * secrétariat, avant validation.
     *
     * ⚠️ Les rapports relèvent du secrétariat et de lui seul - le module
     * Programme n'en porte plus de copie : deux écrans pour le même objet, c'est
     * deux endroits où chercher et un risque d'états divergents.
     */
    public function reports(Request $request): View
    {
        $editions = $this->accessibleEditions($request, 'editions_read');
        $editionId = $request->has('edition_id')
            ? (string) $request->query('edition_id', '')
            : $this->currentEditionId($editions);
        $edition = $editionId !== '' ? $editions->firstWhere('id', (int) $editionId) : null;

        return view('admin.secretariat.reports', [
            'editions' => $editions,
            'edition_id' => $edition ? (string) $edition->id : '',
            'sessions' => $edition
                ? $edition->sessions()->with(['atelier', 'report'])->orderBy('starts_at')->orderBy('id')->get()
                : collect(),
        ]);
    }

    public function generate(Request $request, GenerateReport $action): RedirectResponse
    {
        $data = $request->validate([
            'edition_id' => ['required', 'exists:editions,id'],
            'preset' => ['nullable', 'string'],
            'prompt' => ['nullable', 'string', 'max:2000'],
            'example' => ['nullable', 'file', 'mimes:pdf,txt,doc,docx,xls,xlsx,csv', 'max:10240'],
            'format' => ['nullable', 'in:xlsx,csv,pdf,docx'],
            'dataset' => ['nullable', 'in:presence,none'],
            'aiProvider' => ['nullable', 'in:default,claude,openai,gemini,grok'],
        ]);

        $edition = Edition::findOrFail($data['edition_id']);
        abort_unless($request->user()->canOnEdition('documents', $edition), 403);

        $preset = self::PRESETS[$data['preset'] ?? ''] ?? null;
        $format = $data['format'] ?? ($preset['format'] ?? 'pdf');
        $dataset = $data['dataset'] ?? ($preset['dataset'] ?? 'none');
        $prompt = trim((string) ($data['prompt'] ?? '')) ?: (string) ($preset['prompt'] ?? '');

        $files = [];
        if ($request->hasFile('example')) {
            $file = $request->file('example');
            $files[] = ['mime' => (string) $file->getMimeType(), 'data' => base64_encode((string) $file->get())];
            if ($prompt === '') {
                $prompt = 'Reproduis la structure du modèle joint pour ce colloque.';
            }
        }

        if ($prompt === '' && $files === []) {
            return back()->with('sec_error', 'Décrivez le document souhaité, choisissez un modèle prédéfini ou joignez un exemple.');
        }

        // Contexte du programme réel (salles, horaires, modérateurs/rapporteurs) pour
        // ancrer les documents logistiques (affiches, ordre du jour, chevalets).
        $brief = $prompt."\n\nContexte - programme réel de l’édition :\n".$this->programContext($edition);

        try {
            $doc = $action->handle($edition, $brief, $files, $data['aiProvider'] ?? null, 'document');

            $entry = [
                'id' => (string) Str::uuid(),
                'edition_id' => (string) $edition->id,
                'title' => $doc['title'],
                'sections' => $doc['sections'],
                'format' => $format,
                'dataset' => $dataset,
                'preset' => $data['preset'] ?? null,
                'created_at' => now()->format('d/m/Y · H:i'),
            ];
            $this->pushHistory($entry);

            return back()->with('sec_doc', $entry);
        } catch (\Throwable $e) {
            return back()->with('sec_error', $e->getMessage());
        }
    }

    /** Télécharge un document généré, dans son format d'origine. */
    public function download(Request $request, Edition $edition, string $id): Response
    {
        abort_unless($request->user()->canOnEdition('documents', $edition), 403);

        $entry = collect((array) Settings::get('secretariat_ai_history', []))->firstWhere('id', $id);
        abort_if($entry === null, 404);

        $format = (string) ($entry['format'] ?? 'pdf');
        $base = 'secretariat-'.Str::slug((string) ($entry['title'] ?? 'document')).'-'.$id;

        $report = new EditionReport($edition);
        $datasets = ($entry['dataset'] ?? 'none') === 'presence'
            ? [['Présence', $report->participantHeaders(), $report->participantRows()]]
            : [];

        if ($format === 'xlsx') {
            $writer = new XlsxWriter;
            $narrative = [];
            foreach ((array) ($entry['sections'] ?? []) as $s) {
                $narrative[] = [(string) ($s['heading'] ?? ''), (string) ($s['body'] ?? '')];
            }
            $writer->sheet('Document', ['Section', 'Contenu'], $narrative ?: [['Document', (string) ($entry['title'] ?? '')]]);
            foreach ($datasets as [$name, $headers, $rows]) {
                $writer->sheet($name, $headers, $rows);
            }

            return $writer->download($base.'.xlsx');
        }

        if ($format === 'csv') {
            if ($datasets !== []) {
                [, $headers, $rows] = $datasets[0];
            } else {
                $headers = ['Section', 'Contenu'];
                $rows = array_map(fn ($s) => [(string) ($s['heading'] ?? ''), (string) ($s['body'] ?? '')], (array) ($entry['sections'] ?? []));
            }

            return response()->streamDownload(function () use ($headers, $rows) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, $headers);
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }, $base.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        // pdf / docx → page imprimable (réutilise la vue des rapports IA).
        $view = view('reports.ai', ['edition' => $edition, 'entry' => $entry, 'datasets' => $datasets]);

        if ($format === 'docx') {
            return response($view->render(), 200, [
                'Content-Type' => 'application/msword; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$base.'.doc"',
            ]);
        }

        return response($view->render());
    }

    /** Ajoute une entrée en tête de l'historique (max 30 conservées). */
    protected function pushHistory(array $entry): void
    {
        $history = (array) Settings::get('secretariat_ai_history', []);
        array_unshift($history, $entry);
        Settings::set('secretariat_ai_history', array_slice($history, 0, 30));
    }

    /** Résumé texte du programme réel de l'édition (pour ancrer les documents). */
    protected function programContext(Edition $edition): string
    {
        $lines = EventSession::where('edition_id', $edition->id)
            ->orderBy('starts_at')->orderBy('id')->get()
            ->map(function (EventSession $s) {
                $when = $s->starts_at ? $s->starts_at->translatedFormat('D j M · H:i') : '';
                $bits = array_filter([
                    $s->label,
                    $s->room ? 'Salle : '.$s->room : null,
                    $when ?: null,
                    $s->moderator_name ? 'Modérateur : '.$s->moderator_name : null,
                    $s->rapporteur_name ? 'Rapporteur : '.$s->rapporteur_name : null,
                ]);

                return '- '.implode(' | ', $bits);
            });

        return $lines->isEmpty() ? '(aucune séance programmée)' : $lines->implode("\n");
    }
}
