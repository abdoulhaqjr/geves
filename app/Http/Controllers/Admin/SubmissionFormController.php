<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ScopesEditionAccess;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\SubmissionFormConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Éditeur du formulaire de soumission d'une événement (contrôleur + Blade) :
 * libellés/aides des champs standard, options requis/activé, limites du résumé,
 * et champs personnalisés (ajout/suppression/réordonnancement pilotés par
 * Alpine). Écrit dans settings.submission_form.
 */
class SubmissionFormController extends Controller
{
    use ScopesEditionAccess;

    public function edit(Request $request, Event $event): View
    {
        $config = new SubmissionFormConfig($event);

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

        return view('admin.evenements.submission-form-editor', [
            'event' => $event,
            // Sélecteur + sous-navigation communs au module Soumissions.
            'events' => $this->accessibleEditions($request, 'submissions_list'),
            'event_id' => (string) $event->id,
            'fields' => $fields,
            'custom' => $custom,
            'customTypes' => SubmissionFormConfig::CUSTOM_TYPES,
            'rules' => $config->rules(),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse|JsonResponse
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

        $settings = $event->settings ?? [];
        $settings['submission_form'] = [
            'fields' => $fields,
            'custom' => $custom,
            // Les règles ne sont pas éditées ici : on conserve celles en place.
            'rules' => (new SubmissionFormConfig($event))->rules(),
        ];
        $event->update(['settings' => $settings]);

        // Enregistrement automatique (réordonnancement / ajout / événement d'un champ
        // via l'éditeur Alpine) : réponse JSON, pas de rechargement de page.
        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok', 'custom' => $custom]);
        }

        return back()->with('ukc_status', 'Formulaire de soumission enregistré.');
    }

    /**
     * Règles & limites du dépôt, éditées depuis l'onglet **Options** du module
     * Soumissions. ⚠️ Endpoint séparé : `update()` reconstruit les champs et les
     * champs personnalisés à partir de la requête, donc y poster les seules
     * règles les effacerait.
     */
    public function updateRules(Request $request, Event $event): RedirectResponse
    {
        $config = new SubmissionFormConfig($event);

        $settings = $event->settings ?? [];
        $settings['submission_form'] = [
            'fields' => $settings['submission_form']['fields'] ?? [],
            'custom' => $settings['submission_form']['custom'] ?? [],
            'rules' => $this->rulesFrom($request),
        ];
        $event->update(['settings' => $settings]);

        return redirect()->route('admin.submissions.options', ['event_id' => $event->id])
            ->with('ukc_status', 'Règles du formulaire enregistrées.');
    }

    /**
     * Règles & limites normalisées depuis la requête.
     *
     * @return array<string, mixed>
     */
    private function rulesFrom(Request $request): array
    {
        return [
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
    }
}
