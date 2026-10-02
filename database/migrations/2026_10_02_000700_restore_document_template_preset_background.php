<?php

use App\Models\DocumentTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Trois fonds du concepteur - « Bandes UK », « Simple » et « Or discret » -
 * étaient des **dégradés CSS** posés par une classe sur la page. Le concepteur
 * les affichait donc, mais la mise en page enregistrée ne retient qu'une image
 * de fond : le document délivré ressortait blanc. Et dompdf ne sait de toute
 * façon pas tracer un dégradé.
 *
 * Ils sont désormais des images, comme les deux autres fonds. Cette migration
 * reprend les modèles **déjà enregistrés** : la classe de la page dit lequel
 * était choisi.
 *
 * ⚠️ Aucun retour arrière : reperdre le fond n'aurait aucun intérêt.
 */
return new class extends Migration
{
    /** Classe posée par le concepteur → image correspondante. */
    private const IMAGES = [
        'bg-bands' => '/img/bg-attestation-bands.png',
        'bg-simple' => '/img/bg-attestation-simple.png',
        'bg-gold' => '/img/bg-attestation-gold.png',
    ];

    public function up(): void
    {
        DocumentTemplate::query()->each(function (DocumentTemplate $tpl): void {
            $layout = (array) $tpl->layout;
            $state = (array) $tpl->editor_state;

            if (filled($layout['bg_image'] ?? '') || filled($state['bgImage'] ?? '')) {
                return;
            }

            $classes = preg_split('/\s+/', (string) ($state['bg'] ?? '')) ?: [];
            foreach ($classes as $classe) {
                if (! isset(self::IMAGES[$classe])) {
                    continue;
                }

                $layout['bg_image'] = self::IMAGES[$classe];
                $state['bgImage'] = "url('".self::IMAGES[$classe]."')";
                $tpl->forceFill(['layout' => $layout, 'editor_state' => $state])->save();

                return;
            }
        });
    }

    public function down(): void
    {
        // Rien : reperdre le fond n'aurait aucun intérêt.
    }
};
