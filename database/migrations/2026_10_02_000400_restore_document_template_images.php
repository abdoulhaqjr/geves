<?php

use App\Models\DocumentTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * Les images du concepteur étaient embarquées en `data:` base64 dans le DOM.
 * Elles survivaient à l'instantané de l'éditeur - donc elles restaient visibles
 * dans le concepteur - mais `LayoutSanitizer::src()` refuse `data:` : l'élément
 * était **jeté de la mise en page enregistrée**, et les logos disparaissaient du
 * document délivré, en silence.
 *
 * Le concepteur téléverse désormais ses images. Cette migration répare les
 * modèles **déjà enregistrés** : elle extrait les images de l'instantané, les
 * écrit sur le disque public, remplace leur `src` et **réinjecte** les éléments
 * manquants dans la mise en page, à leur position d'origine.
 *
 * ⚠️ Aucun retour arrière : réembarquer du base64 referait disparaître les logos.
 */
return new class extends Migration
{
    /** Extensions acceptées, par type MIME de l'URI de données. */
    private const EXTENSIONS = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function up(): void
    {
        DocumentTemplate::query()->each(function (DocumentTemplate $tpl): void {
            $state = (array) $tpl->editor_state;
            if ($state === []) {
                return;
            }

            $layout = (array) $tpl->layout;
            $recovered = [];
            $changed = false;

            foreach ((array) ($state['els'] ?? []) as $i => $el) {
                $html = (string) ($el['html'] ?? '');
                $url = $this->storeFirstDataImage($html);
                if ($url === null) {
                    continue;
                }

                $state['els'][$i]['html'] = preg_replace('/src="data:image\/[^"]+"/', 'src="'.$url.'"', $html, 1);
                $changed = true;

                $recovered[] = [
                    'type' => 'image',
                    'src' => $url,
                    'x' => $this->px($el['style']['left'] ?? null),
                    'y' => $this->px($el['style']['top'] ?? null),
                    'w' => $this->px($el['style']['width'] ?? null) ?: 120,
                    'h' => $this->px($el['style']['height'] ?? null) ?: 80,
                ];
            }

            // Fond illustré, perdu de la même façon.
            $bgUrl = $this->storeFirstDataImage((string) ($state['bgImage'] ?? ''));
            if ($bgUrl !== null) {
                $state['bgImage'] = 'url('.$bgUrl.')';
                $layout['bg_image'] = $bgUrl;
                $changed = true;
            }

            if ($recovered !== []) {
                // Les images passent DERRIÈRE le texte déjà en place : un logo
                // réinjecté ne doit pas recouvrir la mise en page existante.
                $layout['elements'] = array_merge($recovered, (array) ($layout['elements'] ?? []));
            }

            if ($changed) {
                $tpl->forceFill(['layout' => $layout, 'editor_state' => $state])->save();
            }
        });
    }

    public function down(): void
    {
        // Rien : réembarquer du base64 referait disparaître les logos.
    }

    /** Écrit la première image `data:` trouvée et rend son adresse publique. */
    private function storeFirstDataImage(string $html): ?string
    {
        if (! preg_match('/data:(image\/[a-z+]+);base64,([A-Za-z0-9+\/=]+)/i', $html, $m)) {
            return null;
        }

        $ext = self::EXTENSIONS[mb_strtolower($m[1])] ?? null;
        $bytes = base64_decode($m[2], true);
        if ($ext === null || $bytes === false) {
            return null;
        }

        // Nom déterministe : deux modèles qui portent le même logo partagent le
        // fichier, et rejouer la migration n'en crée pas un second.
        $path = 'documents/templates/'.md5($bytes).'.'.$ext;
        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, $bytes);
        }

        return Storage::disk('public')->url($path);
    }

    private function px(mixed $value): int
    {
        return (int) round((float) rtrim((string) $value, 'px'));
    }
};
