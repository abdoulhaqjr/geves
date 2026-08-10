<?php

namespace App\Livewire\Admin\Documents;

use App\Actions\GenerateDocumentLayout;
use App\AI\AiManager;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Models\DocumentTemplate;
use App\Models\Edition;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Éditeur de modèles (badges / attestations) : génération IA d'une mise en page
 * à partir d'une consigne de style, aperçu en direct, ajustements (couleur
 * d'accent, JSON éditable), enregistrement par édition/type.
 */
#[Title('Modèles de documents')]
class TemplateDesigner extends Component
{
    use WithFileUploads;

    public string $edition_id = '';
    public string $kind = 'attestation';
    public string $type = 'participation';

    public string $prompt = '';
    public $inspiration = null;

    public array $layout = [];
    public string $layoutJson = '';
    public string $accent = '#223ea4';

    public const ATTESTATION_TYPES = [
        'participation' => 'Participation',
        'communication' => 'Communication',
        'moderation' => 'Modération',
        'rapporteur' => 'Rapporteur',
    ];

    public function mount(): void
    {
        $this->edition_id = (string) (Edition::where('status', 'open')->latest('id')->value('id')
            ?? Edition::latest('id')->value('id') ?? '');
        $this->loadTemplate();
    }

    public function updated($name): void
    {
        if (in_array($name, ['edition_id', 'kind', 'type'], true)) {
            if ($name === 'kind') {
                $this->type = $this->kind === 'badge' ? 'default' : 'participation';
            }
            $this->loadTemplate();
        }
    }

    public function loadTemplate(): void
    {
        $existing = $this->edition_id !== ''
            ? DocumentTemplate::where('edition_id', $this->edition_id)->where('kind', $this->kind)->where('type', $this->type)->first()
            : null;

        $this->layout = $existing?->layout
            ? LayoutSanitizer::sanitize($existing->layout, $this->kind, $this->type)
            : LayoutSanitizer::default($this->kind, $this->type);

        $this->syncFromLayout();
    }

    public function generate(GenerateDocumentLayout $action): void
    {
        $this->validate(['prompt' => ['required', 'string', 'max:1000']]);

        $files = [];
        if ($this->inspiration) {
            $files[] = ['mime' => $this->inspiration->getMimeType(), 'data' => base64_encode($this->inspiration->get())];
        }

        try {
            $this->layout = $action->handle($this->kind, $this->type, $this->prompt, $files);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('prompt', 'Échec de la génération : ' . $e->getMessage());

            return;
        }

        $this->inspiration = null;
        $this->syncFromLayout();
        session()->flash('ukc_status', 'Mise en page générée. Ajustez si besoin, puis enregistrez.');
    }

    public function applyAccent(): void
    {
        $this->layout['accent'] = LayoutSanitizer::sanitize(['accent' => $this->accent] + $this->layout, $this->kind, $this->type)['accent'];
        foreach ($this->layout['elements'] as $i => $el) {
            if (($el['type'] ?? '') === 'rect') {
                $this->layout['elements'][$i]['color'] = $this->layout['accent'];
            }
        }
        $this->syncJson();
    }

    public function applyJson(): void
    {
        $decoded = json_decode($this->layoutJson, true);
        if (! is_array($decoded)) {
            $this->addError('layoutJson', 'JSON invalide.');

            return;
        }
        $this->layout = LayoutSanitizer::sanitize($decoded, $this->kind, $this->type);
        $this->syncFromLayout();
    }

    public function save(): void
    {
        if ($this->edition_id === '') {
            $this->addError('edition_id', 'Choisissez une édition.');

            return;
        }

        DocumentTemplate::updateOrCreate(
            ['edition_id' => $this->edition_id, 'kind' => $this->kind, 'type' => $this->type],
            ['layout' => $this->layout]
        );

        session()->flash('ukc_status', 'Modèle enregistré.');
    }

    protected function syncFromLayout(): void
    {
        $this->accent = $this->layout['accent'] ?? '#223ea4';
        $this->syncJson();
    }

    protected function syncJson(): void
    {
        $this->layoutJson = json_encode($this->layout, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Données d'exemple pour l'aperçu. */
    protected function sampleVars(): array
    {
        $edition = $this->edition_id !== '' ? Edition::with('colloque')->find($this->edition_id) : null;

        return [
            'org' => $edition?->colloque?->name ?? 'Colloque de l’Université de Kara',
            'event' => $edition?->title ?? 'Édition 2026',
            'year' => (string) ($edition?->year ?: date('Y')),
            'location' => $edition?->location ?? 'Kara, Togo',
            'name' => 'Awa Kodjo',
            'role' => 'Communicant',
            'institution' => 'Université de Kara',
            'type' => self::ATTESTATION_TYPES[$this->type] ?? 'participation',
            'date' => now()->format('d/m/Y'),
            'ref' => 'UK-2026-001',
            'verify_url' => url('/verifier/exemple'),
        ];
    }

    public function render()
    {
        return view('livewire.admin.documents.template-designer', [
            'editions' => Edition::latest('id')->get(),
            'attestationTypes' => self::ATTESTATION_TYPES,
            'aiAvailable' => app(AiManager::class)->available() !== [],
            'preview' => LayoutRenderer::render($this->layout, $this->sampleVars()),
        ])->layout('components.layouts.admin', ['title' => 'Modèles de documents']);
    }
}
