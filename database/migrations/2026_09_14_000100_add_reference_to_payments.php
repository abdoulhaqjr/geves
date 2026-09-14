<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Référence unique de chaque paiement (manuel comme en ligne), attribuée dès la
 * création. Distincte du numéro de reçu (émis à la confirmation). Les paiements
 * existants sont rétro-référencés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('reference', 40)->nullable()->after('id')->index();
        });

        // Rétro-référencement des paiements déjà enregistrés.
        Payment::whereNull('reference')->orWhere('reference', '')->get()->each(function (Payment $p) {
            $year = $p->edition?->year ?: $p->created_at?->year ?: date('Y');
            do {
                $ref = 'PAY-'.$year.'-'.Str::upper(Str::random(6));
            } while (Payment::where('reference', $ref)->exists());
            $p->forceFill(['reference' => $ref])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['reference']);
            $table->dropColumn('reference');
        });
    }
};
