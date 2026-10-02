<?php

use App\Models\DocumentTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Le fond de page n'était pas enregistré dans la mise en page : seule la
 * couleur l'était. Une attestation dessinée sur un fond illustré - le cadre
 * couleurs UK, par exemple - ressortait sur du blanc sur le document délivré,
 * alors que le concepteur l'affichait.
 *
 * Le concepteur enregistre désormais `bg_image`. Cette migration reprend les
 * modèles **déjà enregistrés** en récupérant le fond depuis l'instantané de
 * l'éditeur, seul endroit où il avait survécu.
 *
 * ⚠️ Aucun retour arrière : reperdre le fond n'aurait aucun intérêt.
 */
return new class extends Migration
{
    public function up(): void
    {
        DocumentTemplate::query()->each(function (DocumentTemplate $tpl): void {
            $layout = (array) $tpl->layout;
            if (filled($layout['bg_image'] ?? '')) {
                return;
            }

            $src = $this->urlIn((string) (((array) $tpl->editor_state)['bgImage'] ?? ''));
            if ($src === null) {
                return;
            }

            $layout['bg_image'] = $src;
            $tpl->forceFill(['layout' => $layout])->save();
        });
    }

    public function down(): void
    {
        // Rien : reperdre le fond n'aurait aucun intérêt.
    }

    /** Adresse contenue dans une déclaration CSS `url(...)`, servable par le site. */
    private function urlIn(string $css): ?string
    {
        if (! preg_match('/url\((["\']?)(.*?)\1\)/', $css, $m)) {
            return null;
        }

        $src = trim($m[2]);
        if ($src === '' || str_starts_with($src, 'data:')) {
            return null;
        }

        // Anciens chemins relatifs des fonds livrés (« bg-attestation.png »,
        // « admin/bg-attestation-uk.png ») : ils vivent dans /img.
        if (! str_starts_with($src, '/') && ! str_contains($src, '://')) {
            $src = '/img/'.ltrim((string) preg_replace('#^(?:\./)?(?:admin/)?#', '', $src), '/');
        }

        return $src;
    }
};
