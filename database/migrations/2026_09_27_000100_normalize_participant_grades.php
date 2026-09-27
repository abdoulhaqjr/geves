<?php

use App\Support\ParticipantTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normalise les grades saisis via l'ancienne liste du formulaire d'inscription,
 * qui divergeait de {@see ParticipantTypes} : « Doctorant / étudiant » ne
 * correspondait à aucune catégorie tarifaire, le montant restait donc
 * « à préciser » au moment de payer.
 *
 * Seules les valeurs de cette liste sont converties : on ne touche pas aux
 * grades libres saisis ailleurs, qu'on ne peut pas rattacher sans risque de
 * facturer le mauvais tarif.
 */
return new class extends Migration
{
    /** @return array<string, string> ancienne valeur => grade de référence */
    private function map(): array
    {
        return [
            'Enseignant-chercheur' => 'Enseignant / Chercheur',
            'Doctorant / étudiant' => 'Doctorant',
            'Professionnel' => 'Autre participant',
            'Autre' => 'Autre participant',
        ];
    }

    public function up(): void
    {
        foreach ($this->map() as $old => $new) {
            if (! in_array($new, ParticipantTypes::all(), true)) {
                continue; // garde-fou : la cible doit exister dans la liste de référence
            }
            DB::table('users')->where('grade', $old)->update(['grade' => $new]);
            DB::table('participants')->where('title', $old)->update(['title' => $new]);
        }
    }

    public function down(): void
    {
        // Irréversible sans perte : « Professionnel » et « Autre » convergent vers
        // la même valeur, on ne peut pas les redistinguer.
    }
};
