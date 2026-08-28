<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Comptes de démonstration reproductibles : équipe du back-office (staff avec
 * rôles UKC réels, pour illustrer la page Utilisateurs et le RBAC) et un compte
 * participant connectable. Idempotent. Données fictives — pas pour la production.
 */
class DemoStaffSeeder extends Seeder
{
    public function run(): void
    {
        // Équipe back-office : nom, e-mail, rôle UKC, IP de dernière connexion, vérifié.
        $staff = [
            ['Prof. Séna Lawson', 's.lawson@univkara.tg', 'scientific', '41.79.112.8', true],
            ['Dr Ama Bamana', 'a.bamana@univkara.tg', 'reviewer', '154.160.22.17', false],
            ['Komla Tchalla', 'm.tchalla@univkara.tg', 'organizer', '196.200.45.90', true],
            ['Afi Agbeko', 'a.agbeko@univkara.tg', 'secretariat', '102.176.88.12', true],
            ['Koffi Akouété', 'k.akouete@univkara.tg', 'accounting', '41.207.133.55', true],
            ['Yawo Seyi', 'y.seyi@univkara.tg', 'support', '105.179.210.3', true],
            ['Fatou Diallo', 'f.diallo@univkara.tg', 'reception', '41.138.66.201', true],
        ];

        foreach ($staff as [$name, $email, $role, $ip, $verified]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'last_login_at' => now()->subDays(random_int(1, 6)),
                    'last_login_ip' => $ip,
                    'email_verified_at' => $verified ? now() : null,
                ]
            );
            $user->syncRoles([$role]);
        }

        // Compte participant connectable, rattaché à l'édition « à venir ».
        $account = Account::updateOrCreate(
            ['email' => 'participant@demo.tg'],
            [
                'first_name' => 'Koffi',
                'last_name' => 'Mensah',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'grade' => 'Doctorant',
                'affiliation' => 'Université de Kara',
            ]
        );

        $edition = Edition::where('slug', 'edition-2026')->first();
        if ($edition) {
            Participant::firstOrCreate(
                ['edition_id' => $edition->id, 'account_id' => $account->id],
                ['first_name' => 'Koffi', 'last_name' => 'Mensah', 'email' => 'participant@demo.tg', 'role' => 'author', 'access_token' => Str::random(64)],
            );
        }
    }
}
