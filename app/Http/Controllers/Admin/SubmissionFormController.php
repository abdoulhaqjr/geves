<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Support\SubmissionFormConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Éditeur du formulaire de soumission d'une édition (contrôleur + Blade) :
 * libellés/aides des champs standard, options requis/activé, limites du résumé,
 * et champs personnalisés (ajout/suppression/réordonnancement pilotés par
 * Alpine). Écrit dans settings.submission_form.
 */
class SubmissionFormController extends Controller
{
    public function edit(Edition $edition): View
    {
        $config = new SubmissionFormConfig($edition);

        $fields = [];
        foreach (array_keys(SubmissionFormConfig::STANDARD_DEFAULTS) as $key) {
            $fields[$key] = [
                'label' => $config->label($key),
                'help' => $config->help($key),
                'enabled' => $config->enabled($key),
                'required' => $config->required($key),
            ];
        }
        $fields['abstract']['min'] = $config->abstractMin();
        $fields['abstract']['max'] = $config->abstractMax();

        $custom = [];
        foreach ($config->customFields() as $field) {
            $custom[] = [
                'key' => $field['key'],
                'label' => $field['label'],
                'type' => $field['type'],
                'required' => $field['required'],
                'help' => $field['help'],
                'options_text' => implode("\n", $field['options']),
            ];
        }

        return view('admin.editions.submission-form-editor', [
            'edition' => $edition,
            'fields' => $fields,
            'custom' => $custom,
            'customTypes' => SubmissionFormConfig::CUSTOM_TYPES,
            'rules' => $config->rules(),
        ]);
    }

    public function update(Request $request, Edition $edition): RedirectResponse
    {
        $request->validate([
            'fields.title.label' => ['required', 'string', 'max:120'],
            'fields.abstract.label' => ['required', 'string', 'max:120'],
            'custom.*.label' => ['nullable', 'string', 'max:120'],
            'custom.*.type' => ['required', 'in:'.implode(',', array_keys(SubmissionFormConfig::CUSTOM_TYPES))],
        ]);

        $input = (array) $request->input('fields', []);

        $fields = [];
        foreach (array_keys(SubmissionFormConfig::STANDARD_DEFAULTS) as $key) {
            $conf = (array) ($input[$key] ?? []);
            $enabledToggle = in_array($key, ['type', 'keywords', 'file'], true);
            $requiredToggle = ! in_array($key, ['title', 'abstract'], true);

            $entry = [
                'label' => trim((string) ($conf['label'] ?? '')),
                'help' => trim((string) ($conf['help'] ?? '')),
                'enabled' => $enabledToggle ? $request->boolean("fields.$key.enabled") : true,
                'required' => $requiredToggle ? $request->boolean("fields.$key.required") : false,
            ];
            $fields[$key] = $entry;
        }

        $custom = [];
        foreach ((array) $request->input('custom', []) as $c) {
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

        $rules = [
            'title_min' => max(0, (int) $request->input('rules.title_min', 0)),
            'title_max' => max(1, (int) $request->input('rules.title_max', 256)),
            'word_min' => max(0, (int) $request->input('rules.word_min', 50)),
            'word_limit' => max(1, (int) $request->input('rules.word_limit', 300)),
            'keywords_min' => max(0, (int) $request->input('rules.keywords_min', 3)),
            'keywords_max' => max(1, (int) $request->input('rules.keywords_max', 6)),
            'max_authors' => max(1, (int) $request->input('rules.max_authors', 8)),
            'file_max_mb' => max(1, (int) $request->input('rules.file_max_mb', 5)),
            'file_formats' => trim((string) $request->input('rules.file_formats', 'pdf, docx')) ?: 'pdf, docx',
            'require_account' => $request->boolean('rules.require_account'),
            'no_duplicate' => $request->boolean('rules.no_duplicate'),
            'live_counter' => $request->boolean('rules.live_counter'),
            'captcha' => $request->boolean('rules.captcha'),
        ];

        $settings = $edition->settings ?? [];
        $settings['submission_form'] = ['fields' => $fields, 'custom' => $custom, 'rules' => $rules];
        $edition->update(['settings' => $settings]);

        return back()->with('ukc_status', 'Formulaire de soumission enregistré.');
    }
}
