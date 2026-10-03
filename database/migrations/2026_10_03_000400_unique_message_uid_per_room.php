<?php

use App\Models\MeetingMessage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un identifiant de message est unique DANS SA SALLE.
 *
 * ⚠️ `uid` est posé par le client, et c'est lui que visent les citations et les
 * réactions. Deux lignes portant le même identifiant, et une réaction ne sait
 * plus laquelle viser : elle tombe sur la première trouvée. Un réseau qui bégaie
 * suffisait à produire le doublon.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->degonfle();

        Schema::table('meeting_messages', function (Blueprint $table) {
            $table->unique(['meetable_type', 'meetable_id', 'uid']);
        });
    }

    public function down(): void
    {
        Schema::table('meeting_messages', function (Blueprint $table) {
            $table->dropUnique(['meetable_type', 'meetable_id', 'uid']);
        });
    }

    /** Ne garde que le premier message de chaque identifiant dans chaque salle. */
    private function degonfle(): void
    {
        MeetingMessage::query()
            ->orderBy('id')
            ->get()
            ->groupBy(fn (MeetingMessage $m) => $m->meetable_type.'|'.$m->meetable_id.'|'.$m->uid)
            ->each(function ($groupe) {
                $groupe->skip(1)->each(fn (MeetingMessage $m) => $m->delete());
            });
    }
};
