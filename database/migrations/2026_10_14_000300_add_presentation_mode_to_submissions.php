<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table): void {
            $table->string('presentation_mode', 20)->nullable()->after('type');
        });

        // Les imports conservent le choix original de chaque résumé dans meta.
        // Pour les autres dépôts historiques, le mode actuel du participant est
        // le seul choix encore connu depuis l'ancienne mise en commun.
        DB::table('submissions')->orderBy('id')->chunkById(200, function ($submissions): void {
            $participantModes = DB::table('participants')
                ->whereIn('id', $submissions->pluck('participant_id')->filter()->unique())
                ->pluck('participation_mode', 'id');

            foreach ($submissions as $submission) {
                $meta = json_decode((string) ($submission->meta ?? ''), true);
                $original = is_array($meta) ? ($meta['presentation_mode'] ?? null) : null;
                $mode = match ($original) {
                    'Distantiel', 'En ligne', 'online' => 'online',
                    'Présentiel', 'En présentiel', 'onsite' => 'onsite',
                    default => $participantModes[$submission->participant_id] ?? null,
                };

                if (in_array($mode, ['online', 'onsite'], true)) {
                    DB::table('submissions')->where('id', $submission->id)
                        ->update(['presentation_mode' => $mode]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table): void {
            $table->dropColumn('presentation_mode');
        });
    }
};
