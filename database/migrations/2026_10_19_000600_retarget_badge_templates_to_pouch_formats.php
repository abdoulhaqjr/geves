<?php

use App\Documents\LayoutSanitizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ramène les modèles de badge jamais personnalisés sur un format de PORTE-BADGE.
 *
 * ⚠️ Les badges se dessinaient sur la taille de BASE de l'éditeur, **105 × 148 mm**
 * (397 × 560 px) - une demi-feuille A4, pas un porte-badge. Depuis la refonte des
 * formats, seuls cinq formats sont proposés (303, 304, 106, A6 porte-badge, CR80),
 * et celui-là n'en fait pas partie : les modèles déjà enregistrés gardaient donc
 * une taille que plus personne ne peut choisir, et sortaient à **1 par feuille A4**
 * (2 × 105 = 210 mm, soit la feuille entière, sans rien pour les marges).
 *
 * ⚠️ **On ne retouche QUE ce que personne n'a ouvert** (`editor_state IS NULL`) -
 * même règle que `consolidate_participant_badge_models` : un modèle retravaillé par
 * un gestionnaire ne doit pas changer sous ses pieds. Et on laisse intact tout
 * modèle déjà posé sur un format offert.
 */
return new class extends Migration
{
    public function up(): void
    {
        $offerts = [];
        foreach (LayoutSanitizer::BADGE_FORMATS as [$w, $h]) {
            $offerts[$w.'x'.$h] = true;
        }

        DB::table('document_templates')
            ->where('kind', 'badge')
            ->whereNull('editor_state')
            ->orderBy('id')
            ->chunkById(100, function ($modeles) use ($offerts): void {
                foreach ($modeles as $modele) {
                    $layout = json_decode((string) $modele->layout, true);
                    if (! is_array($layout)) {
                        continue;
                    }

                    $taille = (int) ($layout['width'] ?? 0).'x'.(int) ($layout['height'] ?? 0);
                    if (isset($offerts[$taille])) {
                        continue; // déjà sur un porte-badge : rien à faire (et rejeu sans effet)
                    }

                    DB::table('document_templates')->where('id', $modele->id)->update([
                        'layout' => json_encode(
                            LayoutSanitizer::default('badge', (string) $modele->type),
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                        ),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Vide : rendre une taille de feuille à un badge qui entre dans une pochette
        // ne répare rien, et le concepteur ne propose plus ce format.
    }
};
