<?php

namespace App\Http\Controllers\Account;

use App\Actions\LinkSubmissionAuthors;
use App\Http\Controllers\Controller;
use App\Support\AuthorMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vérification discrète, pendant la saisie d'un auteur/co-auteur, de l'existence
 * d'un compte correspondant — POUR GUIDER le déposant sans révéler l'identité
 * d'autrui :
 * - e-mail / téléphone (fournis par le déposant) → on confirme s'ils
 *   correspondent à un membre (il sera associé & notifié) ou non (il sera invité) ;
 * - nom seul → aucune divulgation d'existence (respect de la vie privée) ;
 *   on propose seulement une mise en forme du nom.
 *
 * Le rapprochement réel (y compris par nom + affiliation + IA) est fait au dépôt
 * par {@see LinkSubmissionAuthors} ; ici on ne fait qu'informer.
 */
class AuthorLookupController extends Controller
{
    public function check(Request $request, AuthorMatcher $matcher): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $email = AuthorMatcher::normEmail($data['email'] ?? null);
        $phone = AuthorMatcher::normPhone($data['phone'] ?? null);
        $name = trim((string) ($data['name'] ?? ''));

        $hint = null;
        $type = 'muted';
        $account = null;

        // Indice fondé UNIQUEMENT sur des identifiants fournis par le déposant.
        if ($email !== '' || $phone !== '') {
            $match = $matcher->match(['email' => $email, 'phone' => $phone]);
            if ($match['user']) {
                $u = $match['user'];
                $hint = __('Un compte correspond : la personne sera associée à la communication et notifiée.');
                $type = 'ok';
                // L'identifiant a été fourni par le déposant → on peut pré-remplir
                // les champs à partir du profil (nom, affiliation, grade).
                $account = [
                    'name' => $u->full_name ?: (string) $u->name,
                    'affiliation' => (string) ($u->affiliation ?? ''),
                    'grade' => (string) ($u->grade ?? ''),
                ];
            } else {
                $hint = __('Aucun compte pour l’instant : la personne sera invitée à créer le sien pour retrouver la communication.');
                $type = 'info';
            }
        } elseif ($name !== '') {
            $hint = __('Si un compte du même nom existe, il sera associé automatiquement (la personne pourra décliner).');
            $type = 'muted';
        }

        // Suggestion de mise en forme du nom (ne modifie rien tant que non acceptée).
        $suggestion = null;
        if ($name !== '') {
            $formatted = AuthorMatcher::formatName($name);
            if ($formatted !== '' && $formatted !== $name) {
                $suggestion = $formatted;
            }
        }

        return response()->json(['hint' => $hint, 'type' => $type, 'suggestion' => $suggestion, 'account' => $account]);
    }
}
