<?php

namespace App\Http\Requests\Account;

use App\Models\Edition;
use App\Models\Submission;
use App\Support\SubmissionFormConfig;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validation du dépôt d'une communication (wizard). Les règles sont dérivées de
 * la configuration du formulaire de l'édition ({@see SubmissionFormConfig}). Les
 * garde-fous métier (profil complet, soumissions ouvertes, quota) sont exprimés
 * comme erreurs sur « abstract ». En cas d'échec, on renvoie le wizard vers
 * l'étape contenant la première erreur (flash `submission_error_step`).
 */
class StoreSubmissionRequest extends FormRequest
{
    private ?Edition $editionModel = null;

    private ?SubmissionFormConfig $configModel = null;

    public function authorize(): bool
    {
        return Auth::guard('account')->check();
    }

    /** Édition ciblée (404 si absente ou non publiée). */
    public function edition(): Edition
    {
        if (! $this->editionModel) {
            $edition = Edition::where('slug', $this->route('slug'))->first();
            abort_unless($edition && $edition->isPublished(), 404);
            $this->editionModel = $edition;
        }

        return $this->editionModel;
    }

    public function config(): SubmissionFormConfig
    {
        return $this->configModel ??= new SubmissionFormConfig($this->edition());
    }

    /** @return array<int, string> */
    private function tracks(): array
    {
        return $this->edition()->settings['tracks'] ?? [];
    }

    /** Valeurs par défaut envoyées par le wizard : type « oral » et premier axe. */
    protected function prepareForValidation(): void
    {
        $c = $this->config();
        $tracks = $this->tracks();
        $merge = [];
        if ($c->enabled('type') && ! $this->filled('type')) {
            $merge['type'] = 'oral';
        }
        if (count($tracks) && ! $this->filled('track')) {
            $merge['track'] = $tracks[0];
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $c = $this->config();
        $tracks = $this->tracks();

        $rules = [
            'title' => ['required', 'string', 'max:500'],
            'abstract' => ['required', 'string', 'max:20000', function (string $attr, string $value, \Closure $fail) use ($c): void {
                $words = SubmissionFormConfig::wordCount($value);
                if ($words < $c->abstractMin()) {
                    $fail('Le résumé doit contenir au moins '.$c->abstractMin().' mots.');
                } elseif ($words > $c->abstractMax()) {
                    $fail('Le résumé ne doit pas dépasser '.$c->abstractMax().' mots.');
                }
            }],
            'coauthors' => ['array'],
            'coauthors.*.name' => ['nullable', 'string', 'max:200'],
            'coauthors.*.email' => ['nullable', 'email', 'max:200'],
            'coauthors.*.affiliation' => ['nullable', 'string', 'max:255'],
            'coauthors.*.grade' => ['nullable', 'string', 'max:100'],
        ];

        $rules['track'] = count($tracks)
            ? [$c->required('track') ? 'required' : 'nullable', 'string', 'max:150']
            : ['nullable', 'string', 'max:150'];

        if ($c->enabled('type')) {
            $rules['type'] = ['required', 'in:oral,poster'];
        }

        if ($c->enabled('keywords')) {
            $rules['keywords'] = [$c->required('keywords') ? 'required' : 'nullable', 'string', 'max:500',
                function (string $attr, string $value, \Closure $fail) use ($c): void {
                    $n = count(array_filter(array_map('trim', preg_split('/[,;\n]+/', $value)), fn ($k) => $k !== ''));
                    if ($n > 0 && $n < $c->keywordsMin()) {
                        $fail('Indiquez au moins '.$c->keywordsMin().' mots-clés.');
                    } elseif ($n > $c->keywordsMax()) {
                        $fail('Indiquez au plus '.$c->keywordsMax().' mots-clés.');
                    }
                }];
        }

        if ($c->enabled('file')) {
            $rules['file'] = [$c->required('file') ? 'required' : 'nullable', 'file', 'mimes:pdf,doc,docx', 'max:8192'];
        }

        foreach ($c->customFields() as $field) {
            $key = 'customAnswers.'.$field['key'];

            if ($field['type'] === 'checkbox') {
                $rules[$key] = $field['required'] ? ['accepted'] : ['nullable', 'boolean'];

                continue;
            }

            $rule = [$field['required'] ? 'required' : 'nullable'];
            $rule[] = match ($field['type']) {
                'email' => 'email',
                'number' => 'numeric',
                'date' => 'date',
                default => 'string',
            };
            $rule[] = 'max:2000';
            if ($field['type'] === 'select' && $field['options'] !== []) {
                $rule[] = Rule::in($field['options']);
            }
            $rules[$key] = $rule;
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attrs = [];
        foreach ($this->config()->customFields() as $field) {
            $attrs['customAnswers.'.$field['key']] = mb_strtolower($field['label']);
        }

        return $attrs;
    }

    /** Garde-fous métier ajoutés après les règles de champ (erreurs sur « abstract »). */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $account = Auth::guard('account')->user();
            $edition = $this->edition();

            if (! $account->canSubmit()) {
                $v->errors()->add('abstract', 'Votre e-mail doit être vérifié et votre profil complété (affiliation).');
            } elseif (! $edition->submission_open) {
                $v->errors()->add('abstract', 'Les soumissions sont fermées pour cette édition.');
            } elseif (Submission::remainingFor($edition, $account) <= 0) {
                $v->errors()->add('abstract', 'Vous avez atteint le nombre maximum de communications pour cette édition.');
            }
        });
    }

    /** En cas d'échec, renvoie le wizard vers l'étape de la première erreur. */
    protected function failedValidation(Validator $validator): void
    {
        $step = $this->stepForField((string) array_key_first($validator->errors()->toArray()));

        throw new HttpResponseException(
            back()->withInput()->withErrors($validator, $this->errorBag)->with('submission_error_step', $step)
        );
    }

    /** Étape du wizard (1..5) contenant un champ donné. */
    private function stepForField(string $field): int
    {
        return match (true) {
            str_starts_with($field, 'coauthors') => 2,
            $field === 'abstract', str_starts_with($field, 'customAnswers') => 3,
            $field === 'file' => 4,
            default => 1,
        };
    }
}
