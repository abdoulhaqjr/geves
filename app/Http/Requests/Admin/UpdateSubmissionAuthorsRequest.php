<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Correction de la liste des auteurs par le gestionnaire.
 *
 * ⚠️ L'autorisation est portée par la route (`edition.cap:editions_write`) :
 * c'est la capacité du gestionnaire et du co-gestionnaire, pas celle qui ouvre
 * la LISTE des soumissions - que portent aussi l'évaluateur, le secrétariat et
 * la comptabilité.
 */
class UpdateSubmissionAuthorsRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'auteurs' => ['required', 'array', 'min:1', 'max:60'],
            'auteurs.*.origine' => ['nullable', 'integer', 'min:0'],
            'auteurs.*.last_name' => ['required', 'string', 'max:150'],
            'auteurs.*.first_name' => ['nullable', 'string', 'max:150'],
            'auteurs.*.email' => ['nullable', 'email', 'max:200'],
            'auteurs.*.phone' => ['nullable', 'string', 'max:50'],
            'auteurs.*.affiliation' => ['nullable', 'string', 'max:255'],
            'auteurs.*.grade' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'auteurs.required' => 'Une communication garde au moins son auteur correspondant.',
            'auteurs.*.last_name.required' => 'Le nom de famille est obligatoire pour chaque auteur.',
            'auteurs.*.email.email' => 'L’adresse d’un auteur n’est pas valide.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $auteurs = array_values((array) $this->input('auteurs', []));
            if ($auteurs === []) {
                return;
            }

            // ⚠️⚠️ **L'AUTEUR CORRESPONDANT RESTE, ET IL RESTE EN TÊTE.** Il est
            // l'ancre de la communication : `Submission::authorName()` et
            // `authorEmail()` lisent l'index 0, et c'est lui que l'organisation
            // contacte. Le retirer ou le reléguer par ce formulaire laisserait
            // une communication sans destinataire, sans rien casser de visible.
            $origine = $auteurs[0]['origine'] ?? null;
            if (! is_numeric($origine) || (int) $origine !== 0) {
                $v->errors()->add('auteurs.0', 'L’auteur correspondant doit rester en première position. Pour le remplacer, passez par la fiche du participant.');
            }

            // Un même ancien auteur ne peut pas occuper deux places : les index
            // sont des pointeurs, et deux lignes sur la même place en perdraient
            // une en silence à la relecture.
            $origines = array_filter(array_column($auteurs, 'origine'), 'is_numeric');
            if (count($origines) !== count(array_unique($origines))) {
                $v->errors()->add('auteurs', 'Un même auteur figure deux fois dans la liste.');
            }
        });
    }
}
