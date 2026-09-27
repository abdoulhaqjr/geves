<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Phone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rapport des numéros de téléphone partagés par plusieurs comptes.
 *
 * L'index unique empêche désormais d'en créer, mais cette commande reste la
 * pièce à consulter avant une reprise de données (import, fusion de comptes) :
 * elle ne modifie rien et signale aussi les numéros mal formés, qui échappent à
 * l'unicité tant qu'ils ne sont pas normalisés.
 */
class CheckDuplicatePhones extends Command
{
    protected $signature = 'users:phones {--fix : normalise la mise en forme des numéros (ne touche pas aux doublons)}';

    protected $description = 'Signale les téléphones partagés par plusieurs comptes et les numéros non normalisés';

    public function handle(): int
    {
        $duplicates = DB::table('users')
            ->selectRaw('phone, count(*) as total')
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->groupBy('phone')->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicates as $row) {
            $this->error('Numéro partagé : '.$row->phone.' ('.$row->total.' comptes)');
            foreach (User::where('phone', $row->phone)->orderBy('id')->get() as $user) {
                $this->line('    #'.$user->id.' '.$user->email.' - '.$user->full_name);
            }
        }

        $unnormalized = User::whereNotNull('phone')->where('phone', '!=', '')->get()
            ->filter(fn (User $u) => Phone::normalize($u->phone) !== (string) $u->phone);

        foreach ($unnormalized as $user) {
            $this->warn('Numéro non normalisé : #'.$user->id.' « '.$user->phone.' » → « '.Phone::normalize($user->phone).' »');
            if ($this->option('fix')) {
                $user->update(['phone' => $user->phone]); // le mutateur normalise
            }
        }

        if ($duplicates->isEmpty() && $unnormalized->isEmpty()) {
            $this->info('Aucun doublon, aucun numéro mal formé.');
        }

        // Un doublon est une anomalie à traiter : code de sortie non nul pour le CI.
        return $duplicates->isEmpty() ? self::SUCCESS : self::FAILURE;
    }
}
