<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateDocumentLayout;
use App\AI\AiManager;
use App\Documents\LayoutSanitizer;
use App\Http\Controllers\Controller;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Models\Edition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        '{event}' => 'Édition',
        '{year}' => 'Année',
        '{location}' => 'Lieu',
        '{date}' => 'Date d’émission',
        '{ref}' => 'Référence',
        '{verify_url}' => 'URL de vérification',
    ];

    public function index(Request $request): View
    {
        $editionId = (string) $request->query('edition_id', '');
        if ($editionId === '') {
            $editionId = (string) (Edition::where('status', 'open')->latest('id')->value('id')
                ?? Edition::latest('id')->value('id') ?? '');
        }
        [$kind, $type] = $this->kindType($request->query('kind'), $request->query('type'));

        $existing = $editionId !== ''
            ? DocumentTemplate::where('edition_id', $editionId)->where('kind', $kind)->where('type', $type)->first()
            : null;

        $layout = $existing?->layout
            ? LayoutSanitizer::sanitize($existing->layout, $kind, $type)
            : LayoutSanitizer::default($kind, $type);

        return view($kind === 'badge' ? 'admin.documents.badge-designer' : 'admin.documents.template-designer', [
            'editions' => Edition::latest('id')->get(),
            'edition_id' => $editionId,
            'kind' => $kind,
            'type' => $type,
            'typeLabel' => $kind === 'badge' ? 'Badge' : (self::ATTESTATION_TYPES[$type] ?? ucfirst($type)),
            'layout' => $layout,
            'editorState' => $existing?->editor_state,
            'vars' => $this->sampleVars($editionId, $type),
            'attestationTypes' => self::ATTESTATION_TYPES,
            'aiAvailable' => app(AiManager::class)->available() !== [],
            'variables' => self::VARIABLES,
        ]);
    }

    /** Génère une mise en page via IA (consigne + image d'inspiration). */
    public function generate(Request $request, GenerateDocumentLayout $action): JsonResponse
    {
        $request->validate([
            'prompt' => ['required', 'string', 'max:1000'],
            'edition_id' => ['nullable'],
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
            'vars' => $this->sampleVars((string) $request->input('edition_id', ''), $type),
        ]);
    }

    /** Persiste la mise en page produite par l'éditeur (sanitizée). */
    public function save(Request $request): JsonResponse
    {
        $editionId = (string) $request->input('edition_id', '');
        if ($editionId === '') {
            return response()->json(['ok' => false, 'error' => 'Choisissez une édition.'], 422);
        }

        [$kind, $type] = $this->kindType($request->input('kind'), $request->input('type'));
        $layout = LayoutSanitizer::sanitize((array) $request->input('layout', []), $kind, $type);

        // Instantané natif de l'éditeur (calques + styles), pour un rechargement fidèle.
        $editorState = $request->input('editor_state');
        $editorState = is_array($editorState) && ! empty($editorState['els']) ? $editorState : null;

        DocumentTemplate::updateOrCreate(
            ['edition_id' => $editionId, 'kind' => $kind, 'type' => $type],
            ['layout' => $layout, 'editor_state' => $editorState]
        );

        return response()->json(['ok' => true, 'layout' => $layout]);
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

    /** @return array<string, string> */
    private function sampleVars(string $editionId, string $type): array
    {
        $edition = $editionId !== '' ? Edition::with('colloque')->find($editionId) : null;

        return [
            'org' => $edition?->colloque?->name ?? 'Colloque de l’Université de Kara',
            'event' => $edition?->title ?? 'Édition 2026',
            'year' => (string) ($edition?->year ?: date('Y')),
            'location' => $edition?->location ?? 'Kara, Togo',
            'name' => 'Awa Kodjo',
            'role' => 'Communicant',
            'institution' => 'Université de Kara',
            'type' => self::ATTESTATION_TYPES[$type] ?? 'participation',
            'date' => now()->format('d/m/Y'),
            'ref' => 'UK-2026-001',
            'verify_url' => url('/verifier/exemple'),
        ];
    }
}
