<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\ColloqueDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recrée le jeu de données de DÉMONSTRATION des colloques (événements et données
 * connexes) SANS `migrate:fresh` : vide le domaine « colloque » puis rejoue les
 * seeders de colloque.
 *
 * ⚠️ Ne touche JAMAIS à la BASE que possède le super-administrateur : les
 * réglages (`settings`), les rôles/permissions, le compte super-admin, les
 * guides de prise en main, les modèles de documents, les e-mails, les médias et
 * les témoignages sont volontairement HORS de la liste des tables vidées. C'est
 * tout l'intérêt par rapport à `migrate:fresh --seed`, qui réinitialise tout.
 *
 *   php artisan colloques:reseed
 *   php artisan colloques:reseed --force          # sans confirmation / en prod
 *   php artisan colloques:reseed --keep-accounts  # ne pas purger les comptes de démo
 */
class ReseedColloques extends Command
{
    protected $signature = 'colloques:reseed
        {--force : Exécuter sans confirmation (et autoriser en production)}
        {--keep-accounts : Conserver les comptes de démonstration au lieu de les purger}
        {--class= : Seeder de colloque à rejouer (défaut : le groupe de démonstration)}';

    protected $description = 'Recrée les données de colloque (événements et connexes) sans toucher aux réglages, aux rôles ni au super-administrateur.';

    /**
     * Tables du domaine « colloque », vidées de l'ENFANT vers le PARENT.
     *
     * ⚠️ N'inclut JAMAIS : settings, users (purgés à part, comptes de démo
     * seulement), rôles/permissions, tutorial_guides, tutorial_video_requests,
     * document_templates, emails, media, testimonials, notifications - ce sont
     * des données de configuration/design du super-admin, pas de la démo.
     */
    private const EVENT_TABLES = [
        'attestations',
        'claims',
        'meeting_message_reactions',
        'meeting_messages',
        'session_poll_votes',
        'session_polls',
        'session_question_votes',
        'session_questions',
        'session_attendances',
        'session_reports',
        'reviews',
        'reviewer_bids',
        'slots',
        'submission_authors',
        'submissions',
        'formation_enrollments',
        'formation_trainers',
        'formations',
        'payments',
        'committee_members',
        'project_task_comments',
        'project_tasks',
        'project_milestones',
        'participants',
        'ateliers',
        'event_sessions',
        'event_user',
        'events',
        'event_series',
    ];

    /**
     * Motifs d'e-mail des comptes créés PAR les seeders de colloque. ⚠️ Jamais
     * le super-admin (`admin@univkara.tg`) ni un vrai compte : seuls ces domaines
     * de test sont semés (modérateurs, communicants, volume, co-auteurs externes).
     */
    private const DEMO_EMAIL_PATTERNS = ['%@test.tg', '%@avenir-durable.tg', '%@example.org'];

    public function handle(): int
    {
        if ($this->getLaravel()->isProduction() && ! $this->option('force')) {
            $this->error('Refusé en production : les données de colloque y sont réelles. Relancez avec --force si vous êtes certain.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            'Vider les données de colloque (événements, participants, soumissions, paiements, séances…) puis les recréer ? '
            .'Les réglages, les rôles et le super-administrateur sont CONSERVÉS.'
        )) {
            $this->info('Annulé.');

            return self::SUCCESS;
        }

        $this->wipeEventDomain();

        if (! $this->option('keep-accounts')) {
            $this->pruneDemoAccounts();
        }

        $class = $this->option('class') ?: ColloqueDemoSeeder::class;
        $this->call('db:seed', ['--class' => $class, '--force' => true]);

        $this->info('Données de colloque recréées. Réglages, rôles et super-administrateur intacts.');

        return self::SUCCESS;
    }

    /** Vide les tables du domaine « colloque », contraintes de clés étrangères désactivées. */
    private function wipeEventDomain(): void
    {
        Schema::withoutForeignKeyConstraints(function (): void {
            foreach (self::EVENT_TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }
        });
    }

    /** Supprime les comptes de démonstration (hors super-admin et vrais comptes). */
    private function pruneDemoAccounts(): void
    {
        $demo = User::query()
            ->where('email', '!=', 'admin@univkara.tg')
            ->where(function ($q): void {
                foreach (self::DEMO_EMAIL_PATTERNS as $pattern) {
                    $q->orWhere('email', 'like', $pattern);
                }
            });

        $count = (clone $demo)->count();

        // ⚠️ forceDelete : User utilise SoftDeletes, mais la purge doit être
        // DÉFINITIVE - un simple delete() laisserait les lignes (et leurs e-mails
        // uniques), et le re-seed échouerait sur la contrainte users.email.
        Schema::withoutForeignKeyConstraints(function () use ($demo): void {
            $demo->forceDelete();
        });

        $this->line("Comptes de démonstration purgés : {$count}.");
    }
}
