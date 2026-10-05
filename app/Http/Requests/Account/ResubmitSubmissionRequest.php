<?php

namespace App\Http\Requests\Account;

use App\Models\Event;
use App\Models\Submission;
use App\Support\SubmissionFormConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Validation d'une resoumission (version corrigée). L'appartenance et le droit de
 * réviser sont vérifiés dans le contrôleur (route model binding) ; ici on ne
 * valide que le contenu, avec les limites de mots du résumé de l'événement.
 */
class ResubmitSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('account')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $submission = $this->route('submission');
        $event = $submission instanceof Submission ? $submission->event : null;
        if (! $event instanceof Event) {
            return [];  // événement absente (jamais en pratique) : aucune règle
        }
        $c = new SubmissionFormConfig($event);

        return [
            'title' => ['required', 'string', 'max:500'],
            'abstract' => ['required', 'string', 'max:20000', function (string $attr, string $value, \Closure $fail) use ($c): void {
                $words = SubmissionFormConfig::wordCount($value);
                if ($words < $c->abstractMin()) {
                    $fail('Le résumé doit contenir au moins '.$c->abstractMin().' mots.');
                } elseif ($words > $c->abstractMax()) {
                    $fail('Le résumé ne doit pas dépasser '.$c->abstractMax().' mots.');
                }
            }],
            'keywords' => ['nullable', 'string', 'max:500'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:8192'],
        ];
    }
}
