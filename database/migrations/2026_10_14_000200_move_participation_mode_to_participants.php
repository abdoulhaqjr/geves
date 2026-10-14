<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->string('participation_mode', 20)->nullable()->after('role');
        });

        // Le choix le plus récent reflète une éventuelle correction faite lors
        // d'un dépôt ultérieur. Une réponse importée reste le repli historique.
        DB::table('participants')->orderBy('id')->chunkById(200, function ($participants): void {
            foreach ($participants as $participant) {
                $mode = DB::table('submissions')
                    ->where('participant_id', $participant->id)
                    ->whereIn('participation_mode', ['online', 'onsite'])
                    ->orderByDesc('id')
                    ->value('participation_mode');

                if ($mode === null && $participant->meta !== null) {
                    $meta = json_decode((string) $participant->meta, true);
                    $mode = match ($meta['presentation_mode'] ?? null) {
                        'Distantiel' => 'online',
                        'Présentiel' => 'onsite',
                        default => null,
                    };
                }

                if ($mode !== null) {
                    DB::table('participants')->where('id', $participant->id)->update(['participation_mode' => $mode]);
                }
            }
        });

        Schema::table('submissions', function (Blueprint $table): void {
            $table->dropColumn('participation_mode');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table): void {
            $table->string('participation_mode', 20)->nullable()->after('type');
        });

        DB::table('participants')->whereNotNull('participation_mode')->orderBy('id')->chunkById(200, function ($participants): void {
            foreach ($participants as $participant) {
                DB::table('submissions')->where('participant_id', $participant->id)
                    ->update(['participation_mode' => $participant->participation_mode]);
            }
        });

        Schema::table('participants', function (Blueprint $table): void {
            $table->dropColumn('participation_mode');
        });
    }
};
