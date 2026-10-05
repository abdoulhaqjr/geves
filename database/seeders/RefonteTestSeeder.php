<?php

namespace Database\Seeders;

use App\Models\Event;
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
        $event = Event::query()->where('status', 'open')->latest('id')->first()
            ?? Event::query()->latest('id')->first();

        // 1) Super administrateur - toute la plateforme.
        User::updateOrCreate(
            ['email' => 'super@uk.tg'],
            [
                'name' => 'Sara Super', 'first_name' => 'Sara', 'last_name' => 'Super',
                'password' => Hash::make('password'), 'email_verified_at' => now(),
                'access_level' => User::ACCESS_SUPER_ADMIN, 'affiliation' => 'Université de Kara',
            ]
        );

        // 2) Administrateur de colloque - peut créer un colloque (aucun événement
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

        if ($event) {
            Participant::updateOrCreate(
                ['event_id' => $event->id, 'user_id' => $membre->id],
                ['first_name' => 'Mia', 'last_name' => 'Membre', 'email' => 'membre@uk.tg', 'role' => 'author']
            );

            $hasReception = DB::table('event_user')
                ->where('event_id', $event->id)->where('user_id', $membre->id)->where('role', 'reception')->exists();
            if (! $hasReception) {
                $event->team()->attach($membre->id, ['role' => 'reception']);
            }
        }

        $this->command?->info('Comptes de test : super@uk.tg · gerant@uk.tg · membre@uk.tg (mot de passe : password)'
            .($event ? '' : ' - aucun événement trouvé, participant non rattaché.'));
    }
}
