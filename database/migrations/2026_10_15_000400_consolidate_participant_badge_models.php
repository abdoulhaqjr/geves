<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Si seule l'ancienne variante à photo a été personnalisée, conserver
        // ce travail comme design du modèle participant unique.
        DB::table('document_templates')->where('kind', 'badge')->where('type', 'participant_photo')
            ->whereNotNull('editor_state')->orderBy('id')->chunkById(100, function ($photoTemplates): void {
                foreach ($photoTemplates as $photo) {
                    $default = DB::table('document_templates')
                        ->where('event_id', $photo->event_id)->where('kind', 'badge')->where('type', 'default')->first();

                    if ($default && $default->editor_state !== null) {
                        continue;
                    }

                    if ($default) {
                        DB::table('document_templates')->where('id', $default->id)->update([
                            'layout' => $photo->layout,
                            'editor_state' => $photo->editor_state,
                            'format' => $photo->format,
                        ]);
                    } else {
                        DB::table('document_templates')->insert([
                            'event_id' => $photo->event_id,
                            'kind' => 'badge',
                            'type' => 'default',
                            'title' => 'Badge participant',
                            'format' => $photo->format,
                            'layout' => $photo->layout,
                            'editor_state' => $photo->editor_state,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Ne pas effacer les modifications effectuées ensuite sur le modèle unique.
    }
};
