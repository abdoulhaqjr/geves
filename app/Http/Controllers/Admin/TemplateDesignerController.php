<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateDocumentLayout;
use App\AI\AiManager;
use App\Documents\DocumentVars;
use App\Documents\LayoutSanitizer;
use App\Http\Controllers\Controller;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Models\Event;
use App\Support\EventArtwork;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Éditeur visuel de modèles (badges / attestations), contrôleur + Blade. Le
 * canvas drag-drop est piloté par l'Alpine `docEditor` (autoritatif) ; la
 * génération IA et l'enregistrement passent par des endpoints JSON, la mise en
 * page traversant toujours le sanitizer déterministe avant persistance.
 */
class TemplateDesignerController extends Controller
{
    // Aligné sur les types du hub Documents pour que chaque type ait son propre template.
    public const ATTESTATION_TYPES = Attestation::TYPES;

    /** Variables insérables dans les champs texte, avec libellé. */
    public const VARIABLES = [
        '{name}' => 'Nom du bénéficiaire',
        '{role}' => 'Rôle',
        '{institution}' => 'Institution',
        '{type}' => 'Type d’attestation',
        '{org}' => 'Organisateur',
        '{event}' => 'Événement',
        '{year}' => 'Année',
        '{location}' => 'Lieu',
        '{date}' => 'Date d’émission',
        '{ref}' => 'Référence',
        '{verify_url}' => 'URL de vérification',
        '{communication}' => 'Titre de la communication',
        '{communication_ref}' => 'Réf. communication',
        '{communication_type}' => 'Type de communication (orale / affichée)',
        '{formation}' => 'Titre de la formation',
        '{formation_theme}' => 'Thème de la formation',
        '{formateurs}' => 'Formateur(s) de la formation',
    ];

    public function index(Request $request): View
    {
        return $this->renderDesigner($request, false);
    }

    /**
     * Aperçu d'un modèle (attestation / badge) : ouvre le concepteur en mode
     * APERÇU VERROUILLÉ. On réutilise le canevas exact du concepteur (polices,
     * logos, mise en forme) et on remplace les jetons par des valeurs d'exemple,
     * pour voir EXACTEMENT le design du modèle - pas une reconstitution.
     */
    public function preview(Request $request): View
    {
        return $this->renderDesigner($request, true);
    }

    /**
     * Rend le concepteur de documents (attestation/badge). En mode `$preview`, la
     * page s'ouvre verrouillée sur l'aperçu plein écran (chrome d'événement masqué).
     */
    private function renderDesigner(Request $request, bool $preview): View
    {
        $editionId = (string) $request->query('event_id', '');
        if ($editionId === '') {
            $editionId = (string) (Event::where('status', 'open')->latest('id')->value('id')
                ?? Event::latest('id')->value('id') ?? '');
        }
        [$kind, $type] = $this->kindType($request->query('kind'), $request->query('type'));

        $existing = $editionId !== ''
            ? DocumentTemplate::where('event_id', $editionId)->where('kind', $kind)->where('type', $type)->first()
            : null;

        $layout = $existing?->layout
            ? LayoutSanitizer::sanitize($existing->layout, $kind, $type)
            : LayoutSanitizer::default($kind, $type);

        return view($kind === 'badge' ? 'admin.documents.badge-designer' : 'admin.documents.template-designer', [
            'events' => Event::latest('id')->get(),
            'event_id' => $editionId,
            'kind' => $kind,
            'type' => $type,
            'preview' => $preview,
            // Libellé saisi à la création, sinon celui du type.
            'typeLabel' => filled($existing?->title)
                ? (string) $existing->title
                : ($kind === 'badge' ? 'Badge' : (self::ATTESTATION_TYPES[$type] ?? ucfirst($type))),
            'template' => $existing,
            'scope' => $kind === 'badge' ? 'Participant' : (self::SCOPES[$type] ?? 'Participant'),
            'deleteCode' => $existing ? self::deleteCode($existing) : null,
            'layout' => $layout,
            'editorState' => $existing?->editor_state,
            // Alias compris : l'aperçu du concepteur résout [recipient.name]
            // comme le fait le document délivré.
            'vars' => DocumentVars::expand($this->sampleVars($editionId, $type)),
            'attestationTypes' => self::ATTESTATION_TYPES,
            'aiAvailable' => app(AiManager::class)->available() !== [],
            'variables' => self::VARIABLES,
        ]);
    }

    /**
     * Portée d'un modèle d'attestation : déduite du type (elle pilote l'émission,
     * elle ne se choisit donc pas librement). Affichée à la création pour que
     * l'admin sache ce qu'il crée.
     */
    public const SCOPES = [
        'soumission' => 'Par communication',
        'acceptation' => 'Par communication',
        'communication' => 'Par communication',
        'participation' => 'Participant',
        'moderation' => 'Par session',
        'rapporteur' => 'Par session',
        'formation' => 'Par formation',
        'formation_animation' => 'Par formation',
    ];

    /** Formats de badge proposés à la création. */
    public const BADGE_FORMATS = [
        'a6' => 'A6 (105 × 148 mm)',
        'a7' => 'A7 (74 × 105 mm)',
        'cr80' => 'CR80 (85,6 × 54 mm)',
    ];

    /**
     * Création d'un modèle : on renseigne d'abord le libellé, le type et (pour un
     * badge) le format, PUIS on ouvre le concepteur. Sans cette étape, le modèle
     * était créé implicitement au premier enregistrement, sans identité.
     */
    public function create(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'kind' => ['required', 'in:badge,attestation'],
            'type' => ['required', 'string', 'max:30'],
            'title' => ['nullable', 'string', 'max:150'],
            'format' => ['nullable', 'string', 'max:20'],
        ]);

        [$kind, $type] = $this->kindType($data['kind'], $data['type']);

        $defaultTitle = $kind === 'badge'
            ? 'Badge'
            : (self::ATTESTATION_TYPES[$type] ?? ucfirst($type));

        $format = $kind === 'badge' && array_key_exists((string) ($data['format'] ?? ''), self::BADGE_FORMATS)
            ? (string) $data['format']
            : null;

        DocumentTemplate::updateOrCreate(
            ['event_id' => $data['event_id'], 'kind' => $kind, 'type' => $type],
            ['title' => trim((string) ($data['title'] ?? '')) ?: $defaultTitle, 'format' => $format],
        );

        return redirect()->route('admin.documents.templates', [
            'event_id' => $data['event_id'], 'kind' => $kind, 'type' => $type,
        ]);
    }

    /**
     * Code à retaper pour confirmer la suppression d'un modèle. Volontairement
     * dérivé du type (et non aléatoire) : il reste vérifiable côté serveur sans
     * état de session, et oblige à lire ce qu'on supprime.
     */
    public static function deleteCode(DocumentTemplate $template): string
    {
        return mb_strtoupper('SUPPRIMER-'.$template->type);
    }

    /**
     * Supprime un modèle après confirmation explicite (code retapé). La
     * suppression ne retire que la **mise en page** : les documents déjà émis ne
     * sont pas touchés, et le modèle par défaut à la charte reprend la main.
     */
    public function destroy(Request $request, DocumentTemplate $template): RedirectResponse
    {
        $request->validate(['confirm' => ['required', 'string']]);

        if (mb_strtoupper(trim((string) $request->input('confirm'))) !== self::deleteCode($template)) {
            return back()->withErrors([
                'confirm' => 'Code de confirmation incorrect : le modèle n’a pas été supprimé.',
            ]);
        }

        $editionId = $template->event_id;
        $kind = $template->kind;
        $template->delete();

        return redirect()->route('admin.documents.attestations', [
            'event_id' => $editionId,
            'tab' => $kind === 'badge' ? 'badges' : 'attestations',
            'stab' => 'modeles',
        ])->with('ukc_status', 'Modèle supprimé : la mise en page par défaut s’applique de nouveau.');
    }

    /** Génère une mise en page via IA (consigne + image d'inspiration). */
    public function generate(Request $request, GenerateDocumentLayout $action): JsonResponse
    {
        $request->validate([
            'prompt' => ['required', 'string', 'max:1000'],
            'event_id' => ['nullable'],
            'inspiration' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
        ]);

        [$kind, $type] = $this->kindType($request->input('kind'), $request->input('type'));

        $files = [];
        if ($request->hasFile('inspiration')) {
            $file = $request->file('inspiration');
            $files[] = ['mime' => (string) $file->getMimeType(), 'data' => base64_encode((string) $file->get())];
        }

        try {
            $layout = $action->handle($kind, $type, (string) $request->input('prompt'), $files);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Échec de la génération : '.$e->getMessage()]);
        }

        return response()->json([
            'layout' => $layout,
            'vars' => $this->sampleVars((string) $request->input('event_id', ''), $type),
        ]);
    }

    /** Persiste la mise en page produite par l'éditeur (sanitizée). */
    public function save(Request $request): JsonResponse
    {
        $editionId = (string) $request->input('event_id', '');
        if ($editionId === '') {
            return response()->json(['ok' => false, 'error' => 'Choisissez un événement.'], 422);
        }

        [$kind, $type] = $this->kindType($request->input('kind'), $request->input('type'));
        $layout = LayoutSanitizer::sanitize((array) $request->input('layout', []), $kind, $type);

        // Instantané natif de l'éditeur (calques + styles), pour un rechargement fidèle.
        $editorState = $request->input('editor_state');
        $editorState = is_array($editorState) && ! empty($editorState['els']) ? $editorState : null;

        // ⚠️ Le nom du modèle : il était saisissable dans l'en-tête du concepteur,
        // mais ni envoyé ni enregistré - renommer n'avait donc aucun effet, alors
        // que le message de confirmation citait le nouveau nom.
        $title = trim((string) $request->input('title', ''));
        $title = $title !== '' ? mb_substr($title, 0, 150) : null;

        DocumentTemplate::updateOrCreate(
            ['event_id' => $editionId, 'kind' => $kind, 'type' => $type],
            array_filter(
                ['layout' => $layout, 'editor_state' => $editorState, 'title' => $title],
                // Un nom vide ne doit pas EFFACER celui déjà enregistré.
                fn ($v, $k) => $k !== 'title' || $v !== null,
                ARRAY_FILTER_USE_BOTH,
            )
        );

        return response()->json(['ok' => true, 'layout' => $layout, 'title' => $title]);
    }

    /**
     * Téléversement d'une image du concepteur (logo, illustration, fond).
     *
     * ⚠️ Le concepteur embarquait l'image en `data:` base64 dans le DOM. Elle
     * survivait à l'instantané de l'éditeur - donc elle restait visible dans le
     * concepteur - mais `LayoutSanitizer::src()` refuse `data:`, donc l'élément
     * était **jeté de la mise en page enregistrée** : les logos disparaissaient
     * du document délivré, en silence. Le fichier est donc déposé ici, et la
     * mise en page ne retient que son adresse (légère, lisible par dompdf).
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'mimes:png,jpg,jpeg,webp,gif', 'max:'.EventArtwork::maxUploadKb()],
        ]);

        $path = $request->file('file')->store('documents/templates', 'public');

        return response()->json(['ok' => true, 'url' => Storage::disk('public')->url($path)]);
    }

    /** @return array{0: string, 1: string} */
    private function kindType(?string $kind, ?string $type): array
    {
        $kind = in_array($kind, ['attestation', 'badge'], true) ? $kind : 'attestation';
        if ($kind === 'badge') {
            return [$kind, 'default'];
        }

        return [$kind, isset(self::ATTESTATION_TYPES[$type]) ? $type : 'participation'];
    }

    /** @return array<string, string|list<array{name?: string, src?: string}>> */
    private function sampleVars(string $editionId, string $type): array
    {
        $event = $editionId !== '' ? Event::with('series')->find($editionId) : null;

        return [
            'org' => $event?->series?->name ?? 'Événement de l’Université de Kara',
            'event' => $event?->title ?? 'Événement 2026',
            'year' => (string) ($event?->year ?: date('Y')),
            'location' => $event?->location ?? 'Kara, Togo',
            'name' => 'Awa Kodjo',
            'role' => 'Communicant',
            'institution' => 'Université de Kara',
            'type' => self::ATTESTATION_TYPES[$type] ?? 'participation',
            // ⚠️ Valeurs que la palette sait nommer ([edition.theme], [edition.dates]) :
            // sans elles, l'aperçu laissait un blanc après « Thème du colloque : »,
            // ce qui ressemblait à un jeton cassé.
            'theme' => $event?->theme ?: 'Innover pour un avenir durable',
            'dates' => $event?->dateRangeLabel() ?: 'du 06 au 10 octobre 2026',
            'signatory' => 'Pr. Padabo Kadouza',
            'signatory_role' => 'Président du comité d’organisation',
            'date' => now()->format('d/m/Y'),
            'ref' => 'UK-2026-001',
            'verify_url' => url('/attestation/exemple'),
            'communication' => 'L’apport du numérique dans l’enseignement supérieur',
            'communication_ref' => 'CI-001',
            'communication_type' => 'Orale',
            'formation' => 'Rédaction scientifique et publication',
            'formation_theme' => 'Publier dans une revue indexée',
            'formateurs' => 'Pr. Awa Kodjo, Dr. Kofi Mensah',
            // Aperçu du bloc de signatures (sans image : seuls les noms soulignés).
            'signatures' => [
                ['name' => 'Pr. Awa Kodjo', 'src' => ''],
                ['name' => 'Dr. Kofi Mensah', 'src' => ''],
            ],
        ];
    }
}
