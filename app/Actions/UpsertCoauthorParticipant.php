<?php

namespace App\Actions;

use App\Models\Edition;
use App\Models\Participant;

/**
 * Crée ou retrouve le participant « co-auteur » d'une édition, identifié par
 * e-mail (sans compte). Utilisé par le parcours de paiement des co-auteurs.
 *
 * Règles (alignées sur le plugin WordPress) :
 * - **Identité figée** : si le participant existe déjà, son nom n'est pas
 *   écrasé (seul le grade, qui pilote le tarif, est mis à jour).
 * - **Rôle non rétrogradé** : un participant existant garde son rôle (on ne
 *   force « coauthor » qu'à la création).
 */
class UpsertCoauthorParticipant
{
    public function handle(Edition $edition, string $email, string $name, ?string $affiliation, ?string $grade): Participant
    {
        $email = trim($email);

        $participant = Participant::where('edition_id', $edition->id)
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->first();

        if (! $participant) {
            [$first, $last] = $this->splitName($name);

            // access_token généré par ParticipantObserver à la création.
            $participant = new Participant([
                'edition_id' => $edition->id,
                'email' => $email,
                'first_name' => $first,
                'last_name' => $last,
                'institution' => $affiliation ?: null,
                'role' => 'coauthor',
            ]);
        }

        // Le grade est corrigeable par le co-auteur (il détermine le tarif).
        if (filled($grade)) {
            $participant->title = $grade;
        }

        if (blank($participant->role)) {
            $participant->role = 'coauthor';
        }

        $participant->save();

        return $participant;
    }

    /** @return array{0:string,1:string} [prénom, nom] */
    protected function splitName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        if ($name === '') {
            return ['', ''];
        }

        $parts = explode(' ', $name, 2);

        return [$parts[0], $parts[1] ?? ''];
    }
}
