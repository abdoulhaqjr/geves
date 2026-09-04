<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Submission;
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

        // Deuxième colloque : le même compte participe aussi à une AUTRE édition
        // (colloque différent), pour illustrer le sélecteur « basculer de colloque ».
        $this->enrollSecondColloque($account);
    }

    /**
     * Inscrit le compte de démo à un second colloque (édition d'un autre colloque
     * que « edition-2026 »), avec une communication, pour que le sélecteur de
     * colloque affiche au moins deux entrées avec du contenu.
     */
    private function enrollSecondColloque(Account $account): void
    {
        // Colloques auxquels le compte participe déjà (pour en choisir un AUTRE).
        $myColloqueIds = Participant::where('account_id', $account->id)
            ->with('edition:id,colloque_id')
            ->get()
            ->pluck('edition.colloque_id')
            ->filter()
            ->unique()
            ->all();

        $edition = Edition::with('colloque')
            ->whereNotIn('colloque_id', $myColloqueIds ?: [0])
            ->whereIn('status', ['open', 'closed', 'archived'])
            ->orderByDesc('id')
            ->first();

        if (! $edition) {
            return;
        }

        $participant = Participant::firstOrCreate(
            ['edition_id' => $edition->id, 'account_id' => $account->id],
            ['first_name' => 'Koffi', 'last_name' => 'Mensah', 'email' => 'participant@demo.tg', 'role' => 'author', 'access_token' => Str::random(64)],
        );

        Submission::firstOrCreate(
            ['edition_id' => $edition->id, 'participant_id' => $participant->id, 'revision_of' => 0],
            [
                'ref_code' => 'UKC-'.$edition->year.'-DEMO2',
                'title' => 'Approches interdisciplinaires de la donnée en santé publique',
                'type' => 'oral',
                'track' => $edition->settings['tracks'][0] ?? 'Sciences de la santé',
                'status' => 'under_review',
                'abstract' => 'Cette communication propose une lecture interdisciplinaire de l’usage des données '
                    .'en santé publique, croisant épidémiologie, sciences sociales et informatique. Elle met en '
                    .'évidence les apports d’une gouvernance partagée de la donnée pour la décision sanitaire.',
                'keywords' => 'santé publique, données, interdisciplinarité, gouvernance',
                'authors' => [
                    ['name' => 'Koffi Mensah', 'affiliation' => 'Université de Kara', 'corresponding' => true],
                ],
            ],
        );
    }
}
