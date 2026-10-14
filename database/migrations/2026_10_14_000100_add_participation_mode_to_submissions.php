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
            $table->string('participation_mode', 20)->nullable()->after('type');
        });

        // L'import du colloque conserve déjà ce choix dans meta. Le reporter
        // sans déduire de mode pour les autres soumissions historiques.
        DB::table('submissions')->whereNotNull('meta')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $source = json_decode((string) $row->meta, true);
                $mode = match ($source['presentation_mode'] ?? null) {
                    'Distantiel' => 'online',
                    'Présentiel' => 'onsite',
                    default => null,
                };
                if ($mode !== null) {
                    DB::table('submissions')->where('id', $row->id)->update(['participation_mode' => $mode]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table): void {
            $table->dropColumn('participation_mode');
        });
    }
};
