<?php

namespace App\Livewire\Admin\Editions;

use App\Models\Edition;
use App\Support\SubmissionFormConfig;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Éditeur du formulaire de soumission d'une édition : libellés/aides des champs
 * standard, options requis/activé, limites du résumé, et champs personnalisés
 * (texte, liste, e-mail, nombre, case). Écrit dans settings.submission_form.
 */
#[Title('Formulaire de soumission')]
class SubmissionFormEditor extends Component
{
    public Edition $edition;

    /** @var array<string, array<string, mixed>> */
    public array $fields = [];

    /** @var array<int, array<string, mixed>> */
    public array $custom = [];

    public function mount(Edition $edition): void
    {
        $this->edition = $edition;
        $config = new SubmissionFormConfig($edition);

        foreach (array_keys(SubmissionFormConfig::STANDARD_DEFAULTS) as $key) {
            $this->fields[$key] = [
                'label' => $config->label($key),
                'help' => $config->help($key),
                'enabled' => $config->enabled($key),
                'required' => $config->required($key),
            ];
        }
        $this->fields['abstract']['min'] = $config->abstractMin();
        $this->fields['abstract']['max'] = $config->abstractMax();

        foreach ($config->customFields() as $field) {
            $this->custom[] = [
                'key' => $field['key'],
                'label' => $field['label'],
                'type' => $field['type'],
                'required' => $field['required'],
                'help' => $field['help'],
                'options_text' => implode("\n", $field['options']),
            ];
        }
    }

    public function addCustom(): void
    {
        $this->custom[] = [
            'key' => 'custom_'.Str::lower(Str::random(6)),
            'label' => '',
            'type' => 'text',
            'required' => false,
            'help' => '',
            'options_text' => '',
        ];
    }

    public function removeCustom(int $i): void
    {
        unset($this->custom[$i]);
        $this->custom = array_values($this->custom);
    }

    /** Déplace un champ personnalisé dans l'ordre d'affichage ($dir = -1 haut, +1 bas). */
    public function moveCustom(int $i, int $dir): void
    {
        $j = $i + $dir;
        if (! isset($this->custom[$i], $this->custom[$j])) {
            return;
        }

        [$this->custom[$i], $this->custom[$j]] = [$this->custom[$j], $this->custom[$i]];
        $this->custom = array_values($this->custom);
    }

    public function save(): void
    {
        $this->validate([
            'fields.title.label' => ['required', 'string', 'max:120'],
            'fields.abstract.label' => ['required', 'string', 'max:120'],
            'fields.abstract.min' => ['required', 'integer', 'min:0', 'max:100000'],
            'fields.abstract.max' => ['required', 'integer', 'min:1', 'max:100000'],
            'custom.*.label' => ['nullable', 'string', 'max:120'],
            'custom.*.type' => ['required', 'in:'.implode(',', array_keys(SubmissionFormConfig::CUSTOM_TYPES))],
        ]);

        $fields = [];
        foreach ($this->fields as $key => $conf) {
            $entry = [
                'label' => trim((string) ($conf['label'] ?? '')),
                'help' => trim((string) ($conf['help'] ?? '')),
                'enabled' => (bool) ($conf['enabled'] ?? true),
                'required' => (bool) ($conf['required'] ?? false),
            ];
            if ($key === 'abstract') {
                $min = max(0, (int) ($conf['min'] ?? 50));
                $max = max($min + 1, (int) ($conf['max'] ?? 8000));
                $entry['min'] = $min;
                $entry['max'] = $max;
            }
            $fields[$key] = $entry;
        }

        $custom = [];
        foreach ($this->custom as $c) {
            $label = trim((string) ($c['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $options = collect(preg_split('/\r\n|\r|\n/', (string) ($c['options_text'] ?? '')))
                ->map(fn ($o) => trim($o))
                ->filter()
                ->values()
                ->all();

            $custom[] = [
                'key' => (string) ($c['key'] ?? ('custom_'.Str::lower(Str::random(6)))),
                'label' => $label,
                'type' => in_array($c['type'] ?? 'text', array_keys(SubmissionFormConfig::CUSTOM_TYPES), true) ? $c['type'] : 'text',
                'required' => (bool) ($c['required'] ?? false),
                'help' => trim((string) ($c['help'] ?? '')),
                'options' => $options,
            ];
        }

        $settings = $this->edition->settings ?? [];
        $settings['submission_form'] = ['fields' => $fields, 'custom' => $custom];
        $this->edition->update(['settings' => $settings]);

        session()->flash('ukc_status', 'Formulaire de soumission enregistré.');
    }

    public function render()
    {
        return view('livewire.admin.editions.submission-form-editor', [
            'customTypes' => SubmissionFormConfig::CUSTOM_TYPES,
        ])->layout('components.layouts.admin', ['title' => 'Formulaire — '.($this->edition->title ?: $this->edition->year)]);
    }
}
