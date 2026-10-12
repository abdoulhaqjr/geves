<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute `audience` (libellé de l'audience ciblée) et `batch_id` (identifiant de
 * campagne) aux e-mails, pour regrouper les envois par campagne dans le
 * back-office « E-mails - Programmés » (fidèle au mockup admin-emails.html).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table): void {
            $table->string('audience', 40)->nullable()->after('template');
            $table->string('batch_id', 40)->nullable()->index()->after('audience');
        });
    }

    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table): void {
            $table->dropColumn(['audience', 'batch_id']);
        });
    }
};
