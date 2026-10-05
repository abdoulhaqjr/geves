<?php

namespace App\Http\Requests\Account;

use App\Models\Event;
use App\Models\Submission;
use App\Support\SubmissionFormConfig;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validation du dépôt d'une communication (wizard). Les règles sont dérivées de
 * la configuration du formulaire de l'événement ({@see SubmissionFormConfig}). Les
 * garde-fous métier (profil complet, soumissions ouvertes, quota) sont exprimés
 * comme erreurs sur « abstract ». En cas d'échec, on renvoie le wizard vers
 * l'étape contenant la première erreur (flash `submission_error_step`).
 */
class StoreSubmissionRequest extends FormRequest
{
    private ?Event $editionModel = null;

    private ?SubmissionFormConfig $configModel = null;

    public function authorize(): bool
    {
        return Auth::guard('account')->check();
    }

    /** Événement ciblée (404 si absente ou non publiée). */
    public function event(): Event
    {
        if (! $this->editionModel) {
            $event = Event::where('slug', $this->route('slug'))->first();
            abort_unless($event && $event->isPublished(), 404);
            $this->editionModel = $event;
        }

        return $this->editionModel;
    }

    public function config(): SubmissionFormConfig
    {
        return $this->configModel ??= new SubmissionFormConfig($this->event());
    }

    /** @return array<int, string> */
    private function tracks(): array
    {
        return $this->event()->settings['tracks'] ?? [];
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

        // Auteur correspondant : par défaut = le compte connecté. Quand l'utilisateur
        // se déclare auteur correspondant (« self »), l'identité (nom + prénom + e-mail)
        // est FORCÉE depuis le compte (non falsifiable), quoi que le client ait envoyé.
        $account = Auth::guard('account')->user();
        $corr = (array) $this->input('corresponding', []);
        $self = (string) ($corr['self'] ?? '1') !== '0';
        if ($self && $account) {
            $corr['first_name'] = $account->first_name;
            $corr['last_name'] = $account->last_name;
            $corr['email'] = $account->email;
        }
        $corr['self'] = $self ? '1' : '0';
        $corr['first_name'] = $corr['first_name'] ?? $account?->first_name;
        $corr['last_name'] = $corr['last_name'] ?? $account?->last_name;
        $corr['email'] = $corr['email'] ?? $account?->email;
        $merge['corresponding'] = $corr;

        $this->merge($merge);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $c = $this->config();
        $tracks = $this->tracks();

        $titleRule = ['required', 'string', 'max:'.$c->titleMax()];
        if ($c->titleMin() > 0) {
            $titleRule[] = 'min:'.$c->titleMin();
        }

        $rules = [
            'title' => $titleRule,
            'abstract' => ['required', 'string', 'max:20000', function (string $attr, string $value, \Closure $fail) use ($c): void {
                $words = SubmissionFormConfig::wordCount($value);
                if ($words < $c->abstractMin()) {
                    $fail('Le résumé doit contenir au moins '.$c->abstractMin().' mots.');
                } elseif ($words > $c->abstractMax()) {
                    $fail('Le résumé ne doit pas dépasser '.$c->abstractMax().' mots.');
                }
            }],
            // Auteur correspondant (prérempli avec le compte, éditable). L'identité
            // est toujours transmise (verrouillée sur le compte quand « self »).
            'corresponding' => ['array'],
            'corresponding.self' => ['nullable'],
            'corresponding.first_name' => ['required', 'string', 'max:150'],
            'corresponding.last_name' => ['required', 'string', 'max:150'],
            'corresponding.email' => ['required', 'email', 'max:200'],
            'corresponding.phone' => ['nullable', 'string', 'max:50'],
            'corresponding.affiliation' => ['nullable', 'string', 'max:255'],
            'corresponding.grade' => ['nullable', 'string', 'max:100'],
            'coauthors' => ['array'],
            'coauthors.*.last_name' => ['nullable', 'string', 'max:150'],
            'coauthors.*.first_name' => ['nullable', 'string', 'max:150'],
            'coauthors.*.email' => ['nullable', 'email', 'max:200'],
            'coauthors.*.phone' => ['nullable', 'string', 'max:50'],
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
            $event = $this->event();

            if (! $account->canSubmit()) {
                $v->errors()->add('abstract', 'Votre e-mail doit être vérifié et votre profil complété (affiliation).');
            } elseif (! $event->submission_open) {
                $v->errors()->add('abstract', 'Les soumissions sont fermées pour cet événement.');
            } elseif (Submission::remainingFor($event, $account) <= 0) {
                $v->errors()->add('abstract', 'Vous avez atteint le nombre maximum de communications pour cet événement.');
            }

            // Co-auteurs : nom + e-mail + affiliation obligatoires (pour toute ligne
            // entamée), e-mail valide, unicité (≠ correspondant, pas de doublon).
            $corrEmail = mb_strtolower(trim((string) $this->input('corresponding.email', '')));
            $seen = [];
            foreach ((array) $this->input('coauthors', []) as $co) {
                if (! is_array($co)) {
                    continue;
                }
                $lastName = trim((string) ($co['last_name'] ?? ''));
                $firstName = trim((string) ($co['first_name'] ?? ''));
                $email = mb_strtolower(trim((string) ($co['email'] ?? '')));
                $affiliation = trim((string) ($co['affiliation'] ?? ''));
                $started = $lastName !== '' || $firstName !== '' || $email !== '' || $affiliation !== ''
                    || trim((string) ($co['phone'] ?? '')) !== '' || trim((string) ($co['grade'] ?? '')) !== '';
                if (! $started) {
                    continue; // ligne entièrement vide : ignorée
                }

                if ($lastName === '' || $firstName === '' || $email === '' || $affiliation === '') {
                    $v->errors()->add('coauthors', 'Chaque co-auteur doit avoir un nom, un prénom, un e-mail et une affiliation.');
                    break;
                }
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $v->errors()->add('coauthors', 'Un e-mail de co-auteur est invalide.');
                    break;
                }
                if ($corrEmail !== '' && $email === $corrEmail) {
                    $v->errors()->add('coauthors', 'Un co-auteur ne peut pas être aussi l’auteur correspondant (même e-mail).');
                    break;
                }
                if (isset($seen[$email])) {
                    $v->errors()->add('coauthors', 'Deux co-auteurs ont le même e-mail : chaque auteur doit être unique.');
                    break;
                }
                $seen[$email] = true;
            }

            // Plafond du nombre de co-auteurs (réglage `coauthors_max`, défaut 8).
            $coauthorsMax = (int) ($event->settings['coauthors_max'] ?? 8);
            if (count($seen) > $coauthorsMax) {
                $v->errors()->add('coauthors', 'Nombre maximum de co-auteurs atteint ('.$coauthorsMax.').');
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
            str_starts_with($field, 'coauthors'), str_starts_with($field, 'corresponding') => 2,
            $field === 'abstract', str_starts_with($field, 'customAnswers') => 3,
            $field === 'file' => 4,
            default => 1,
        };
    }
}
