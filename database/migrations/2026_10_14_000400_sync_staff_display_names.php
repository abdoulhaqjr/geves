<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Le compte initial porte encore ce libellé alors que son profil contient
        // désormais une identité complète. Ne pas toucher aux noms personnalisés.
        DB::table('users')
            ->whereIn('name', ['Administrateur', 'Administrator'])
            ->whereNotNull('first_name')
            ->whereNotNull('last_name')
            ->select(['id', 'first_name', 'last_name'])
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $name = trim(((string) $user->first_name).' '.((string) $user->last_name));

                    if ($name !== '') {
                        DB::table('users')->where('id', $user->id)
                            ->update(['name' => mb_substr($name, 0, 255)]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Le nom générique d'origine n'est pas conservé : retour arrière sans
        // altération d'une identité qui a depuis pu être modifiée.
    }
};
