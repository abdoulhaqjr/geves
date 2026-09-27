<?php

use App\Support\Phone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Le téléphone devient **unique** sur `users`.
 *
 * ⚠️ Il était déjà traité comme un identifiant FORT (rapprochement des auteurs
 * et des formateurs : `AuthorMatcher` fait `where('phone', …)->first()`, puis le
 * serveur impose l'identité du compte trouvé), mais rien ne garantissait son
 * unicité - ni index, ni règle de validation. Deux comptes partageant un numéro
 * faisaient rattacher une communication au mauvais compte, en silence.
 *
 * Trois temps : normaliser la mise en forme, libérer les doublons, poser
 * l'index. Le compte le plus ancien garde le numéro ; les autres le perdent et
 * devront le ressaisir (leur profil redevient incomplet, ce qui les y invite).
 * Les numéros libérés sont journalisés pour que l'organisation puisse suivre.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Forme canonique : sans cela « +228 90 01 52 84 » et « +22890015284 »
        //    échapperaient tous deux à l'index unique.
        DB::table('users')
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->orderBy('id')
            ->select('id', 'phone')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $clean = Phone::normalize($row->phone);
                    if ($clean !== (string) $row->phone) {
                        DB::table('users')->where('id', $row->id)->update(['phone' => $clean ?: null]);
                    }
                }
            });

        // 2) Doublons : le compte le plus ancien conserve le numéro.
        $duplicates = DB::table('users')
            ->selectRaw('phone, count(*) as total')
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->groupBy('phone')
            ->havingRaw('count(*) > 1')
            ->pluck('phone');

        foreach ($duplicates as $phone) {
            $ids = DB::table('users')->where('phone', $phone)->orderBy('id')->pluck('id');
            $released = $ids->slice(1);

            DB::table('users')->whereIn('id', $released)->update(['phone' => null]);

            Log::warning('Téléphone partagé par plusieurs comptes : numéro conservé par le compte le plus ancien.', [
                'phone' => $phone,
                'kept_user_id' => $ids->first(),
                'cleared_user_ids' => $released->values()->all(),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            // Nullable : plusieurs NULL restent permis, seuls les numéros
            // renseignés doivent être distincts.
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
        });
    }
};
