<?php

namespace Database\Seeders;

use App\Models\Edition;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Comptes de démonstration pour valider la refonte comptes & rôles (option B).
 * Idempotent (updateOrCreate) : peut être relancé sans réinitialiser la base.
 *
 *   php artisan db:seed --class=RefonteTestSeeder
 *
 * Tous les mots de passe : « password ».
 */
class RefonteTestSeeder extends Seeder
{
    public function run(): void
    {
        $edition = Edition::query()->where('status', 'open')->latest('id')->first()
            ?? Edition::query()->latest('id')->first();

        // 1) Super administrateur - toute la plateforme.
        User::updateOrCreate(
            ['email' => 'super@uk.tg'],
            [
                'name' => 'Sara Super', 'first_name' => 'Sara', 'last_name' => 'Super',
                'password' => Hash::make('password'), 'email_verified_at' => now(),
                'access_level' => User::ACCESS_SUPER_ADMIN, 'affiliation' => 'Université de Kara',
            ]
        );

        // 2) Administrateur de colloque - peut créer un colloque (aucune édition
        //    au départ, pour tester la création + la gérance automatique).
        User::updateOrCreate(
            ['email' => 'gerant@uk.tg'],
            [
                'name' => 'Gabin Gérant', 'first_name' => 'Gabin', 'last_name' => 'Gérant',
                'password' => Hash::make('password'), 'email_verified_at' => now(),
                'access_level' => User::ACCESS_COLLOQUE_ADMIN, 'affiliation' => 'Université de Kara',
            ]
        );

        // 3) Utilisateur « participant cumulable » : Communicant ET Accueil sur
        //    le colloque (montre le cumul des natures scientifique + organisation).
        $membre = User::updateOrCreate(
            ['email' => 'membre@uk.tg'],
            [
                'name' => 'Mia Membre', 'first_name' => 'Mia', 'last_name' => 'Membre',
                'password' => Hash::make('password'), 'email_verified_at' => now(),
                'access_level' => User::ACCESS_USER, 'affiliation' => 'ENS Atakpamé',
            ]
        );

        if ($edition) {
            Participant::updateOrCreate(
                ['edition_id' => $edition->id, 'user_id' => $membre->id],
                ['first_name' => 'Mia', 'last_name' => 'Membre', 'email' => 'membre@uk.tg', 'role' => 'author']
            );

            $hasReception = DB::table('edition_user')
                ->where('edition_id', $edition->id)->where('user_id', $membre->id)->where('role', 'reception')->exists();
            if (! $hasReception) {
                $edition->team()->attach($membre->id, ['role' => 'reception']);
            }
        }

        $this->command?->info('Comptes de test : super@uk.tg · gerant@uk.tg · membre@uk.tg (mot de passe : password)'
            .($edition ? '' : ' - aucune édition trouvée, participant non rattaché.'));
    }
}
