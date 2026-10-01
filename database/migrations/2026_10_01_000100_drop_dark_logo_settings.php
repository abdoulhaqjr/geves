<?php

use App\Support\Settings;
use Illuminate\Database\Migrations\Migration;

/**
 * Les versions « fond sombre » des logos ne sont plus téléversées mais calculées
 * (blanchiment CSS, cf. App\Support\Branding). Les anciennes clés ne sont plus
 * lues : les laisser en base donnerait l'illusion d'un réglage encore actif.
 *
 * ⚠️ On oublie le **réglage**, pas le fichier : supprimer une image téléversée
 * est irréversible, et elle ne coûte que sa place sur le disque.
 */
return new class extends Migration
{
    private const LEGACY = ['app_logo_white', 'app_logo_mark_white'];

    public function up(): void
    {
        foreach (self::LEGACY as $key) {
            Settings::set($key, '');
        }
    }

    public function down(): void
    {
        // Rien à rétablir : la valeur ne désignait plus un réglage utilisé.
    }
};
