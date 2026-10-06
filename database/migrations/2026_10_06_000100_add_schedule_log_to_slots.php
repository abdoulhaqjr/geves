<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal de reprogrammation d'un créneau de passage. Quand une communication
 * DÉJÀ placée voit son horaire, sa salle ou sa séance changer, le modèle Slot y
 * ajoute une entrée {at, from, to}. C'est ce qui permet à l'assistante publique
 * de dire à un participant « votre communication a été déplacée du … au … ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->json('schedule_log')->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropColumn('schedule_log');
        });
    }
};
